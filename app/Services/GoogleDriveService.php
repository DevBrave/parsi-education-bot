<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
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
                $client->refreshToken($refreshToken);
                $this->driveService = new GoogleDrive($client);

                return;
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

            return $uploadedFile->id;
        } catch (\Throwable $e) {
            Log::error('Google Drive file upload failed: '.$e->getMessage(), [
                'filename' => $filename,
            ]);

            return null;
        }
    }
}
