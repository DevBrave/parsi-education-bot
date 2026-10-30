<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected ?GoogleClient $client = null;

    protected ?GoogleDrive $driveService = null;

    protected ?string $folderId = null;

    public function __construct()
    {
        $this->folderId = config('google.folder_id') ?: null;

        $clientId = config('google.client_id');
        $clientSecret = config('google.client_secret');
        $refreshToken = config('google.refresh_token');

        // 1. OAuth 2.0 (for personal Gmail accounts)
        if ($clientId && $clientSecret && $refreshToken) {
            try {
                $client = new GoogleClient;
                $client->setClientId($clientId);
                $client->setClientSecret($clientSecret);
                $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

                if (! empty($token['error'])) {
                    bot_log('drive')->warning('Google Drive refresh token invalid or expired: ' . ($token['error_description'] ?? $token['error']));
                } else {
                    $this->client = $client;
                    $this->driveService = new GoogleDrive($client);

                    return;
                }
            } catch (\Throwable $e) {
                bot_log('drive')->error('Failed to initialize Google Drive OAuth client: '.$e->getMessage());
            }
        }

        // 2. Service Account fallback (for Workspace Shared Drives)
        $credentialsPath = config('google.service_account_json');
        if ($credentialsPath && file_exists($credentialsPath)) {
            try {
                $client = new GoogleClient;
                $client->setAuthConfig($credentialsPath);
                $client->addScope(GoogleDrive::DRIVE);
                $this->client = $client;
                $this->driveService = new GoogleDrive($client);
            } catch (\Throwable $e) {
                bot_log('drive')->error('Failed to initialize Google Drive client: '.$e->getMessage());
            }
        }
    }

    /**
     * Check if Google Drive service is configured and ready.
     */
    public function isConfigured(): bool
    {
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

        $parent = $parentFolderId ?? $this->folderId;

        try {
            $cleanName = str_replace("'", "\\'", $folderName);
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
        } catch (\Throwable $e) {
            bot_log('drive')->warning("Failed to get or create Google Drive folder '{$folderName}': ".$e->getMessage());

            return $parent;
        }
    }

    /**
     * Upload a file to Google Drive.
     * Returns the Google Drive file ID on success, or null on failure.
     */
    public function uploadFile(string $filePath, string $filename, string $mimeType, ?string $folderId = null): ?string
    {
        // Prevent script timeout and memory exhaustion during large file uploads to Google Drive
        @set_time_limit(0);
        @ini_set('max_execution_time', '1800');
        @ini_set('memory_limit', '1024M');

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
            $fileMetadata = new DriveFile([
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
                    $media = new \Google\Http\MediaFileUpload(
                        $this->client,
                        $request,
                        $mimeType,
                        null,
                        true,
                        $chunkSizeBytes
                    );
                    $media->setFileSize($fileSize);

                    $status = false;
                    $handle = fopen($filePath, 'rb');
                    while (! $status && ! feof($handle)) {
                        $chunk = fread($handle, $chunkSizeBytes);
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

            // Grant direct read permission to anyone with the link so students can view/download
            try {
                $permission = new Permission([
                    'type' => 'anyone',
                    'role' => 'reader',
                ]);
                $this->driveService->permissions->create($uploadedFileId, $permission, [
                    'supportsAllDrives' => true,
                ]);
            } catch (\Throwable $permError) {
                bot_log('drive')->warning('Could not set public view permission on Google Drive file: '.$permError->getMessage());
            }

            return $uploadedFileId;
        } catch (\Throwable $e) {
            bot_log('drive')->error('Google Drive file upload failed: '.$e->getMessage(), [
                'filename' => $filename,
                'file_size' => $fileSize,
            ]);

            return null;
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

            $this->log('info', 'Google Drive file moved to trash', ['fileId' => $fileId]);

            return true;
        } catch (\Throwable $trashError) {
            $this->log('error', 'Google Drive trash fallback also failed: '.$trashError->getMessage(), ['fileId' => $fileId]);

            return false;
        }
    }

    /**
     * Log to the dedicated drive channel.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        bot_log('drive')->$level($message, $context);
    }
}
