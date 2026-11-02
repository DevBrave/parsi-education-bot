<?php

namespace App\Services;

use App\Models\ResourceFile;
use Illuminate\Http\UploadedFile;
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
    public function sync(ResourceFile $resourceFile, string $storedPath, ?string $caption = null): bool
    {
        // Allow up to 10 minutes for slow network transfers
        set_time_limit(600);

        $fullPath = Storage::disk('local')->path($storedPath);

        if (! file_exists($fullPath)) {
            bot_log('telegram')->error("Resource file does not exist on disk: {$fullPath}");

            return false;
        }

        try {
            $storageChannelId = config('telegram.storage_channel_id');
            $courseName = $resourceFile->resource?->course?->name ?? 'Course Material';
            $categoryName = $resourceFile->resource?->resourceType?->display_name ?? 'Material';
            $title = $caption ?: ($resourceFile->resource?->title ?? '');
            $cleanTitle = trim(preg_replace('/^📚\s*/u', '', $title));

            $submitter = $resourceFile->resource?->submitter;
            $submitterName = $submitter?->username
                ? "@{$submitter->username}"
                : trim("{$submitter?->first_name} {$submitter?->last_name}");
            if (empty($submitterName) && $submitter?->telegram_user_id) {
                $submitterName = "ID: {$submitter->telegram_user_id}";
            }

            $formattedCaption = '📚 <b>'.e($courseName).'</b>';
            if (! empty($cleanTitle)) {
                $formattedCaption .= "\n📌 <b>".e($cleanTitle).'</b>';
            }
            $formattedCaption .= "\n📄 ".e($resourceFile->original_filename);
            $formattedCaption .= "\n🏷️ <b>Category:</b> ".e($categoryName);
            if (! empty($submitterName)) {
                $formattedCaption .= "\n👤 <b>Sent by:</b> ".e($submitterName);
            }

            $fileSizeBytes = file_exists($fullPath) ? filesize($fullPath) : 0;
            $tgResult = null;

            // Refresh model to check if an earlier worker or manual sync already uploaded to Telegram
            $freshFile = $resourceFile->fresh() ?? $resourceFile;
            $hasTgFile = ! empty($freshFile->telegram_file_id);

            $maxTelegramUploadBytes = ((int) config('telegram.max_upload_size_mb', 50)) * 1024 * 1024;

            // Only upload to Telegram if not already uploaded
            if (! $hasTgFile) {
                if ($fileSizeBytes > 0 && $fileSizeBytes <= $maxTelegramUploadBytes) {
                    $tgResult = $this->telegramService->uploadDocument(
                        chatId: $storageChannelId,
                        filePath: $fullPath,
                        filename: $resourceFile->original_filename,
                        caption: $formattedCaption,
                    );
                    $hasTgFile = ! empty($tgResult['file_id']);
                    if ($hasTgFile) {
                        // Publish Telegram status before potentially slow Drive authentication/upload.
                        $resourceFile->update([
                            'telegram_file_id' => $tgResult['file_id'],
                            'telegram_file_unique_id' => $tgResult['file_unique_id'] ?? null,
                            'telegram_message_id' => $tgResult['message_id'] ?? null,
                        ]);
                    }
                } else {
                    $limitMb = round($maxTelegramUploadBytes / (1024 * 1024));
                    bot_log('telegram')->info("ResourceFile #{$resourceFile->id} is {$fileSizeBytes} bytes (>{$limitMb}MB), skipping Telegram Bot API upload and routing directly to Google Drive.");
                }
            }

            if (! $hasTgFile) {
                bot_log('telegram')->warning("Telegram upload omitted or failed for ResourceFile #{$resourceFile->id}, proceeding to Google Drive fallback.");
            }

            // 2. Upload to Google Drive (only if approved and not already synced)
            $isApproved = ($resourceFile->resource?->status === 'approved');
            $driveFileId = ($resourceFile->fresh() ?? $resourceFile)->drive_file_id;

            $driveStarted = microtime(true);
            bot_log('drive')->info('Resource Drive stage started', ['resource_file_id' => $resourceFile->id]);
            try {
                if (! $driveFileId && $isApproved && $this->googleDriveService->isConfigured()) {
                    try {
                        $courseName = $resourceFile->resource?->course?->name ?? 'General';
                        $courseType = $resourceFile->resource?->resourceType?->display_name ?? 'Materials';
                        // 1. Find or create the Course folder (e.g., "Algorithms")
                        $courseFolderId = $this->googleDriveService->getOrCreateFolder($courseName);
                        // 2. Find or create the ResourceType subfolder INSIDE the Course folder (e.g., "Past Exams")
                        $targetFolderId = $courseFolderId
                            ? $this->googleDriveService->getOrCreateFolder($courseType, $courseFolderId)
                            : null;
                        $driveFileId = $this->googleDriveService->uploadFile(
                            filePath: $fullPath,
                            filename: $resourceFile->original_filename,
                            mimeType: $resourceFile->mime_type,
                            folderId: $targetFolderId,
                        );
                    } catch (\Throwable $driveError) {
                        bot_log('drive')->warning("Google Drive backup failed for ResourceFile #{$resourceFile->id}: {$driveError->getMessage()}");
                    }
                }

            } finally {
                bot_log('drive')->info('Resource Drive stage finished', [
                    'resource_file_id' => $resourceFile->id,
                    'duration_ms' => (int) ((microtime(true) - $driveStarted) * 1000),
                    'uploaded' => ! empty($driveFileId),
                ]);
            }

            // If neither Telegram nor Google Drive received the file, mark rejected and abort
            if (! $hasTgFile && ! $driveFileId) {
                bot_log('telegram')->error("Failed to sync ResourceFile #{$resourceFile->id}: both Telegram and Google Drive were unavailable.");
                $resourceFile->resource?->update(['status' => 'rejected']);

                return false;
            }

            // 3. Update ResourceFile with external IDs and clear local path
            $resourceFile->update([
                'telegram_file_id' => $tgResult['file_id'] ?? $freshFile->telegram_file_id,
                'telegram_file_unique_id' => $tgResult['file_unique_id'] ?? $freshFile->telegram_file_unique_id,
                'telegram_message_id' => $tgResult['message_id'] ?? $freshFile->telegram_message_id,
                'drive_file_id' => $driveFileId,
                'local_path' => $driveFileId ? null : $storedPath,
            ]);

            // 4. Remove temporary file from local VPS disk to conserve storage
            if ($driveFileId) {
                Storage::disk('local')->delete($storedPath);
            }

            bot_log('telegram')->info("Resource #{$resourceFile->resource_id} successfully synced (Telegram: ".($hasTgFile ? 'yes' : 'no').', Drive: '.($driveFileId ? 'yes' : 'no').').');

            return true;
        } catch (\Throwable $e) {
            bot_log('telegram')->error("Error syncing ResourceFile #{$resourceFile->id}: {$e->getMessage()}");
            $resourceFile->resource?->update(['status' => 'rejected']);
            // Retain the source so a failed external upload can be recovered.

            return false;
        }
    }

    /**
     * Store the file locally and sync it to Telegram and Google Drive.
     * Returns an array with external file IDs on success, or null on failure.
     *
     * @param  UploadedFile  $file  The uploaded file from the request.
     * @param  string  $caption  Caption for the Telegram message.
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
    protected function uploadToGoogleDrive(UploadedFile $file, string $fullPath, ?string $courseName = null): ?string
    {
        if (! $this->googleDriveService->isConfigured() || ! file_exists($fullPath)) {
            return null;
        }

        $folderId = $courseName
            ? $this->googleDriveService->getOrCreateFolder($courseName)
            : null;

        return $this->googleDriveService->uploadFile(
            filePath: $fullPath,
            filename: $file->getClientOriginalName(),
            mimeType: $file->getClientMimeType(),
            folderId: $folderId,
        );
    }
}
