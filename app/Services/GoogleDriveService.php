<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
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
                    Log::warning('Google Drive refresh token invalid or expired: ' . ($token['error_description'] ?? $token['error']));
                } else {
                    $this->driveService = new GoogleDrive($client);

                    return;
                }
            } catch (\Throwable $e) {
                Log::error('Failed to initialize Google Drive OAuth client: '.$e->getMessage());
            }
        }

        // 2. Service Account fallback (for Workspace Shared Drives)
        $credentialsPath = config('google.service_account_json');
        if ($credentialsPath && file_exists($credentialsPath)) {
            try {
                $client = new GoogleClient;
                $client->setAuthConfig($credentialsPath);
                $client->addScope(GoogleDrive::DRIVE);
                $this->driveService = new GoogleDrive($client);
            } catch (\Throwable $e) {
                Log::error('Failed to initialize Google Drive client: '.$e->getMessage());
            }
        }
    }

    /**
     * Check if Google Drive service is configured and ready.
     */
    public function isConfigured(): bool
    {
        return $this->driveService !== null;
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
            Log::warning("Failed to get or create Google Drive folder '{$folderName}': ".$e->getMessage());

            return $parent;
        }
    }

    /**
     * Upload a file to Google Drive.
     * Returns the Google Drive file ID on success, or null on failure.
     */
    public function uploadFile(string $filePath, string $filename, string $mimeType, ?string $folderId = null): ?string
    {
        if (! $this->isConfigured()) {
            Log::warning('Google Drive is not configured. Skipping Drive upload for: '.$filename);

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

            // Grant direct read permission to anyone with the link so students can view/download
            try {
                $permission = new Permission([
                    'type' => 'anyone',
                    'role' => 'reader',
                ]);
                $this->driveService->permissions->create($uploadedFile->id, $permission, [
                    'supportsAllDrives' => true,
                ]);
            } catch (\Throwable $permError) {
                Log::warning('Could not set public view permission on Google Drive file: '.$permError->getMessage());
            }

            return $uploadedFile->id;
        } catch (\Throwable $e) {
            Log::error('Google Drive file upload failed: '.$e->getMessage(), [
                'filename' => $filename,
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
            Log::warning('Google Drive permanent delete failed, attempting trash fallback: '.$e->getMessage(), ['fileId' => $fileId]);
        }

        // 2. Fallback: Move to Trash (useful when account is editor or lacks hard delete privileges in Shared Drives)
        try {
            $trashUpdate = new DriveFile(['trashed' => true]);
            $this->driveService->files->update($fileId, $trashUpdate, [
                'supportsAllDrives' => true,
            ]);

            return true;
        } catch (\Throwable $trashError) {
            Log::error('Google Drive trash fallback also failed: '.$trashError->getMessage(), ['fileId' => $fileId]);

            return false;
        }
    }
}
