<?php

namespace App\Services;

use App\Models\ResourceFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Handles the full upload pipeline for a file:
 * local storage → Telegram channel → Google Drive.
 */
class ResourceUploadService
{
    public function __construct(
        protected TelegramService $telegramService,
        protected GoogleDriveService $googleDriveService,
    ) {}

    /**
     * Process background upload for an already stored file and update database records.
     * Called by UploadResourceFileJob.
     */
    public function sync(ResourceFile $resourceFile, string $storedPath, string $caption = ''): bool
    {
        // Allow up to 10 minutes for slow network transfers
        set_time_limit(600);

        $fullPath = Storage::disk('local')->path($storedPath);

        if (! file_exists($fullPath)) {
            Log::error("Resource file does not exist on disk: {$fullPath}");
            $resourceFile->resource?->delete();
            return false;
        }

        try {
            // 1. Upload to Telegram storage channel
            $storageChannelId = config('telegram.storage_channel_id');
            $tgResult = $this->telegramService->uploadDocument(
                chatId: $storageChannelId,
                filePath: $fullPath,
                filename: $resourceFile->original_filename,
                caption: $caption,
            );

            if (! $tgResult || empty($tgResult['file_id'])) {
                Log::error("Failed to upload ResourceFile #{$resourceFile->id} to Telegram");
                $resourceFile->resource?->delete();
                return false;
            }

            // 2. Upload to Google Drive (essential backup)
            $driveFileId = null;
            if ($this->googleDriveService->isConfigured()) {
                $driveFileId = $this->googleDriveService->uploadFile(
                    filePath: $fullPath,
                    filename: $resourceFile->original_filename,
                    mimeType: $resourceFile->mime_type,
                );
            }

            if (! $driveFileId) {
                Log::error("Failed to upload ResourceFile #{$resourceFile->id} to Google Drive");
                $resourceFile->resource?->delete();
                return false;
            }

            // 3. Update ResourceFile with external IDs
            $resourceFile->update([
                'telegram_file_id' => $tgResult['file_id'],
                'telegram_file_unique_id' => $tgResult['file_unique_id'] ?? null,
                'drive_file_id' => $driveFileId,
            ]);

            // 4. Mark Resource as approved so students can now see and download it
            $resourceFile->resource?->update([
                'status' => 'approved',
            ]);

            Log::info("Resource #{$resourceFile->resource_id} successfully synced to Telegram and approved.");

            return true;
        } catch (\Throwable $e) {
            Log::error("Error syncing ResourceFile #{$resourceFile->id}: {$e->getMessage()}");
            $resourceFile->resource?->delete();
            return false;
        } finally {
            // 5. Always delete local temporary file after processing
            Storage::disk('local')->delete($storedPath);
        }
    }

    /**
     * Store the file locally and sync it to Telegram and Google Drive.
     * Returns an array with external file IDs on success, or null on failure.
     *
     * @param  UploadedFile  $file     The uploaded file from the request.
     * @param  string        $caption  Caption for the Telegram message.
     * @return array{telegram_file_id: string, telegram_file_unique_id: ?string, drive_file_id: ?string}|null
     */
    public function upload(UploadedFile $file, string $caption = ''): ?array
    {
        // 1. Store file temporarily to local disk
        $storedPath = $file->store('resources');
        $fullPath = Storage::disk('local')->path($storedPath);

        try {
            // 2. Upload to Telegram storage channel (primary)
            $tgResult = $this->uploadToTelegram($file, $fullPath, $caption);

            if (! $tgResult || empty($tgResult['file_id'])) {
                return null;
            }

            // 3. Upload to Google Drive (backup/mirror)
            $driveFileId = $this->uploadToGoogleDrive($file, $fullPath);

            return [
                'telegram_file_id' => $tgResult['file_id'],
                'telegram_file_unique_id' => $tgResult['file_unique_id'] ?? null,
                'drive_file_id' => $driveFileId,
            ];
        } finally {
            // 4. Always clean up local temporary file even if something fails
            Storage::disk('local')->delete($storedPath);
        }
    }

    /**
     * Upload file to the Telegram storage channel.
     */
    protected function uploadToTelegram(UploadedFile $file, string $fullPath, string $caption): ?array
    {
        $storageChannelId = config('telegram.storage_channel_id');

        if (! $storageChannelId || ! file_exists($fullPath)) {
            return null;
        }

        return $this->telegramService->uploadDocument(
            chatId: $storageChannelId,
            filePath: $fullPath,
            filename: $file->getClientOriginalName(),
            caption: $caption,
        );
    }

    /**
     * Upload file to Google Drive.
     */
    protected function uploadToGoogleDrive(UploadedFile $file, string $fullPath): ?string
    {
        if (! $this->googleDriveService->isConfigured() || ! file_exists($fullPath)) {
            return null;
        }

        return $this->googleDriveService->uploadFile(
            filePath: $fullPath,
            filename: $file->getClientOriginalName(),
            mimeType: $file->getClientMimeType(),
        );
    }
}
