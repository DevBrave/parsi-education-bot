<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Http\MediaFileUpload;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Google\Service\Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    protected ?GoogleClient $client = null;

    protected ?GoogleDrive $driveService = null;

    protected ?string $folderId = null;

    protected bool $initialized = false;

    protected ?int $retryInitializationAt = null;

    public function __construct()
    {
        $this->folderId = config('google.folder_id') ?: null;
    }

    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $this->retryInitializationAt = null;

        $clientId = config('google.client_id');
        $clientSecret = config('google.client_secret');
        $refreshToken = config('google.refresh_token');

        // 1. OAuth 2.0 (for personal Gmail accounts)
        if ($clientId || $clientSecret || $refreshToken) {
            if (! $clientId || ! $clientSecret || ! $refreshToken) {
                bot_log('drive')->error('Google Drive OAuth configuration is incomplete. Service account fallback disabled; complete the OAuth settings to resume Drive uploads.');

                return;
            }
            try {
                $client = $this->createClient();
                $client->setClientId($clientId);
                $client->setClientSecret($clientSecret);
                $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

                if (! empty($token['error'])) {
                    if (in_array($token['error'], ['temporarily_unavailable', 'server_error'], true)) {
                        $this->retryInitializationAt = now()->getTimestamp() + 30;
                    }
                    bot_log('drive')->warning('Google Drive refresh token invalid or expired: '.($token['error_description'] ?? $token['error']));
                } else {
                    $this->client = $client;
                    $this->driveService = new GoogleDrive($client);

                    return;
                }
            } catch (\Throwable $e) {
                $this->retryInitializationAt = now()->getTimestamp() + 30;
                bot_log('drive')->error('Failed to initialize Google Drive OAuth client: '.$e->getMessage());
            }

            bot_log('drive')->warning('Google Drive OAuth unavailable. Service account fallback disabled; Drive uploads remain unsynced until OAuth succeeds.');

            return;
        }

        // Service accounts are used only when no OAuth settings are supplied.
        $credentialsPath = config('google.service_account_json');
        if ($credentialsPath && file_exists($credentialsPath)) {
            try {
                $client = $this->createClient();
                $client->setAuthConfig($credentialsPath);
                $client->addScope(GoogleDrive::DRIVE);
                $this->client = $client;
                $this->driveService = new GoogleDrive($client);
            } catch (\Throwable $e) {
                bot_log('drive')->error('Failed to initialize Google Drive client: '.$e->getMessage());
            }
        }
    }

    protected function createClient(): GoogleClient
    {
        $client = new GoogleClient(['retry' => ['retries' => 3]]);
        $client->setHttpClient(new Client(['connect_timeout' => 10, 'timeout' => 120]));

        return $client;
    }

    /**
     * Check if Google Drive service is configured and ready.
     */
    public function isConfigured(): bool
    {
        if ($this->retryInitializationAt !== null && now()->getTimestamp() >= $this->retryInitializationAt) {
            $this->initialized = false;
        }
        $started = microtime(true);
        if (! $this->initialized) {
            try {
                $this->initialize();
            } finally {
                bot_log('drive')->info('Google Drive initialization finished', [
                    'duration_ms' => (int) ((microtime(true) - $started) * 1000),
                    'configured' => $this->driveService !== null && $this->client !== null,
                ]);
            }
        }

        return $this->driveService !== null && $this->client !== null;
    }

    /**
     * Find or create a subfolder by name inside a parent folder.
     * Useful for automatically organizing files by Course.
     */
    public function getOrCreateFolder(string $folderName, ?string $parentFolderId = null): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $parent = $parentFolderId ?? $this->folderId ?? 'root';

        try {
            $lockKey = 'drive:folder:'.hash('sha256', $this->accountKey().$parent.'\0'.$folderName);

            return Cache::lock($lockKey, 600)->block(5, function () use ($folderName, $parent) {
                $cleanName = str_replace(['\\', "'"], ['\\\\', "\\'"], $folderName);
                $query = "mimeType = 'application/vnd.google-apps.folder' and name = '{$cleanName}' and trashed = false";
                if ($parent) {
                    $query .= " and '{$parent}' in parents";
                }

                $results = $this->driveService->files->listFiles([
                    'q' => $query,
                    'spaces' => 'drive',
                    'fields' => 'files(id, name)',
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true,
                    'pageSize' => 1,
                ]);

                $files = $results->getFiles();
                if (! empty($files)) {
                    return $files[0]->getId();
                }

                $folderMetadata = new DriveFile([
                    'name' => $folderName,
                    'mimeType' => 'application/vnd.google-apps.folder',
                ]);

                if ($parent) {
                    $folderMetadata->setParents([$parent]);
                }

                $createdFolder = $this->driveService->files->create($folderMetadata, [
                    'fields' => 'id',
                    'supportsAllDrives' => true,
                ]);

                return $createdFolder->id;
            });
        } catch (\Throwable $e) {
            bot_log('drive')->warning("Failed to get or create Google Drive folder '{$folderName}': ".$e->getMessage());

            return null;
        }
    }

    /**
     * Upload a file to Google Drive.
     * Returns the file ID only after public sharing succeeds.
     * Reserved IDs survive retries on the local disk; all workers must share that disk.
     */
    public function uploadFile(string $filePath, string $filename, string $mimeType, ?string $folderId = null): ?string
    {
        if (! $this->isConfigured()) {
            bot_log('drive')->warning('Google Drive is not configured. Skipping Drive upload for: '.$filename);

            return null;
        }

        if (! file_exists($filePath)) {
            bot_log('drive')->error("Google Drive upload failed: File not found at {$filePath}");

            return null;
        }

        $fileSize = filesize($filePath);
        if ($fileSize <= 0) {
            bot_log('drive')->warning("Skipping Google Drive upload for empty file: {$filename}");

            return null;
        }

        try {
            $hash = hash_file('sha256', $filePath);
            if ($hash === false) {
                throw new \RuntimeException('Unable to hash upload source.');
            }
            $key = hash('sha256', json_encode([$this->accountKey(), $folderId ?? $this->folderId ?? 'root', $filename, $mimeType, $hash], JSON_THROW_ON_ERROR));

            return Cache::lock('drive:upload:'.$key, 3600)->block(5, function () use ($key, $filePath, $filename, $mimeType, $folderId, $fileSize) {
                $journal = 'drive/uploads/'.$key.'.json';
                $disk = Storage::disk('local');
                $state = $disk->exists($journal) ? json_decode($disk->get($journal), true, 512, JSON_THROW_ON_ERROR) : null;
                $fileId = $state['id'] ?? null;
                if ($fileId) {
                    try {
                        $existing = $this->driveService->files->get($fileId, ['fields' => 'id,trashed', 'supportsAllDrives' => true]);
                        if ($existing->getTrashed()) {
                            throw new \RuntimeException('Previously uploaded file is in the trash; restore it before retrying.');
                        }

                        return $this->makePublic($fileId) ? $fileId : null;
                    } catch (Exception $exception) {
                        if ($exception->getCode() !== 404) {
                            throw $exception;
                        }
                        // A reserved ID may not have been uploaded yet. Reuse it after ambiguous failures.
                    }
                } else {
                    $fileId = $this->driveService->files->generateIds(['count' => 1, 'space' => 'drive'])->getIds()[0] ?? null;
                    if (! $fileId || ! $disk->put($journal, json_encode(['id' => $fileId], JSON_THROW_ON_ERROR))) {
                        throw new \RuntimeException('Unable to persist reserved Drive upload ID.');
                    }
                }

                return $this->uploadContents($filePath, $filename, $mimeType, $folderId, $fileSize, $fileId);
            });
        } catch (\Throwable $e) {
            bot_log('drive')->error('Google Drive upload failed: '.$e->getMessage(), ['filename' => $filename]);

            return null;
        }
    }

    protected function accountKey(): string
    {
        return hash('sha256', json_encode([config('google.client_id'), config('google.refresh_token'), config('google.service_account_json')], JSON_THROW_ON_ERROR));
    }

    protected function uploadContents(string $filePath, string $filename, string $mimeType, ?string $folderId, int $fileSize, string $fileId): ?string
    {
        try {
            $fileMetadata = new DriveFile([
                'id' => $fileId,
                'name' => $filename,
            ]);

            $targetFolder = $folderId ?? $this->folderId;
            if ($targetFolder) {
                $fileMetadata->setParents([$targetFolder]);
            }

            $uploadedFileId = null;

            // For files >= 5MB, use chunked resumable upload to avoid memory exhaustion (no base64 buffering in RAM)
            if ($fileSize >= 5 * 1024 * 1024) {
                $this->client->setDefer(true);
                $handle = null;
                try {
                    $request = $this->driveService->files->create(
                        $fileMetadata,
                        [
                            'fields' => 'id, webViewLink, webContentLink',
                            'supportsAllDrives' => true,
                        ]
                    );

                    $chunkSizeBytes = 2 * 1024 * 1024; // 2MB chunk (must be multiple of 256KB)
                    $media = new MediaFileUpload(
                        $this->client,
                        $request,
                        $mimeType,
                        '',
                        true,
                        $chunkSizeBytes
                    );
                    $media->setFileSize($fileSize);

                    $status = false;
                    $handle = fopen($filePath, 'rb');
                    if ($handle === false) {
                        throw new \RuntimeException('Unable to open upload source.');
                    }
                    while (! $status && ! feof($handle)) {
                        $chunk = fread($handle, $chunkSizeBytes);
                        if ($chunk === false || ($chunk === '' && ! feof($handle))) {
                            throw new \RuntimeException('Unable to read upload source.');
                        }
                        if ($chunk === '') {
                            break;
                        }
                        $status = $media->nextChunk($chunk);
                    }

                    if ($status instanceof DriveFile) {
                        $uploadedFileId = $status->id;
                    } elseif (is_object($status) && isset($status->id)) {
                        $uploadedFileId = $status->id;
                    } elseif (is_array($status) && isset($status['id'])) {
                        $uploadedFileId = $status['id'];
                    }
                } finally {
                    if (is_resource($handle)) {
                        fclose($handle);
                    }
                    $this->client->setDefer(false);
                }
            } else {
                // For files < 5MB, standard multipart upload is faster
                $content = file_get_contents($filePath);
                if ($content === false) {
                    throw new \RuntimeException('Unable to read upload source.');
                }

                $uploadedFile = $this->driveService->files->create(
                    $fileMetadata,
                    [
                        'data' => $content,
                        'mimeType' => $mimeType,
                        'uploadType' => 'multipart',
                        'fields' => 'id, webViewLink, webContentLink',
                        'supportsAllDrives' => true,
                    ]
                );

                $uploadedFileId = $uploadedFile->id ?? null;
            }

            if (! $uploadedFileId) {
                bot_log('drive')->error("Google Drive upload completed but returned no file ID for: {$filename}");

                return null;
            }

            return $this->makePublic($uploadedFileId) ? $uploadedFileId : null;
        } catch (\Throwable $e) {
            bot_log('drive')->error('Google Drive file upload failed: '.$e->getMessage(), [
                'filename' => $filename,
                'file_size' => $fileSize,
            ]);

            return null;
        }
    }

    protected function makePublic(string $fileId): bool
    {
        try {
            $permission = new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $this->driveService->permissions->create($fileId, $permission, [
                'supportsAllDrives' => true,
            ]);

            return true;
        } catch (\Throwable $permError) {
            bot_log('drive')->warning('Could not set public view permission on Google Drive file: '.$permError->getMessage());

            return false;
        }
    }

    /**
     * Delete a file from Google Drive by its file ID.
     */
    public function deleteFile(string $fileId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        // 1. Attempt permanent deletion first
        try {
            $this->driveService->files->delete($fileId, ['supportsAllDrives' => true]);
            $this->forgetUploadReservation($fileId);

            return true;
        } catch (\Throwable $e) {
            bot_log('drive')->warning('Google Drive permanent delete failed, attempting trash fallback: '.$e->getMessage(), ['fileId' => $fileId]);
        }

        // 2. Fallback: Move to Trash (useful when account is editor or lacks hard delete privileges in Shared Drives)
        try {
            $trashUpdate = new DriveFile(['trashed' => true]);
            $this->driveService->files->update($fileId, $trashUpdate, [
                'supportsAllDrives' => true,
            ]);
            $this->forgetUploadReservation($fileId);

            $this->log('info', 'Google Drive file moved to trash', ['fileId' => $fileId]);

            return true;
        } catch (\Throwable $trashError) {
            $this->log('error', 'Google Drive trash fallback also failed: '.$trashError->getMessage(), ['fileId' => $fileId]);

            return false;
        }
    }

    protected function forgetUploadReservation(string $fileId): void
    {
        try {
            $disk = Storage::disk('local');
            foreach ($disk->files('drive/uploads') as $path) {
                $state = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
                if (($state['id'] ?? null) === $fileId) {
                    $disk->delete($path);
                }
            }
        } catch (\Throwable $exception) {
            bot_log('drive')->warning('Could not remove Drive upload reservation', ['file_id' => $fileId]);
        }
    }

    /**
     * Log to the dedicated drive channel.
     *
     * @param  array<string, mixed>  $context
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        bot_log('drive')->$level($message, $context);
    }
}
