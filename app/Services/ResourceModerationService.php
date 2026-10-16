<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResourceModerationService
{
    public function __construct(
        protected TelegramService $telegram,
        protected GoogleDriveService $driveService
    ) {}

    /**
     * Approve a resource, mark it as verified, and notify the submitting student.
     */
    public function approve(Resource $resource, User $reviewer): bool
    {
        $resource->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        

        Log::info("Resource #{$resource->id} approved by user #{$reviewer->id} ({$reviewer->username})");
        
        // 1. Send approved files to Telegram storage channel
        $this->sendToStorageChannel($resource);
        
        // 2. Sync to Google Drive if needed
        $this->syncDrive($resource);
        
        // 3. Notify the submitting student via Telegram
        $this->notifySubmitter($resource, 'approved');

        return true;
    }

    /**
     * Reject a resource and send a courteous notification to the submitting student.
     */
    public function reject(Resource $resource, User $reviewer, ?string $reason = null): bool
    {
        $resource->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        Log::info("Resource #{$resource->id} rejected by user #{$reviewer->id} ({$reviewer->username})");

        // Notify the submitting student via Telegram
        $this->notifySubmitter($resource, 'rejected');

        return true;
    }

    /**
     * Notify the submitter in their preferred language.
     */
    protected function notifySubmitter(Resource $resource, string $action): void
    {
        $submitter = $resource->submitter;

        if (! $submitter || ! $submitter->telegram_user_id) {
            return;
        }

        $lang = $submitter->preferred_language ?? 'en';
        $courseName = $resource->course?->name ?? 'Course';

        $key = $action === 'approved'
            ? 'bot.submit_material.approved_notification'
            : 'bot.submit_material.rejected_notification';

        $message = __($key, ['course' => e($courseName)], $lang);

        try {
            $this->telegram->sendMessage($submitter->telegram_user_id, $message, null, 'HTML');
        } catch (\Throwable $e) {
            Log::warning("Failed to send moderation notification to submitter #{$submitter->id}: {$e->getMessage()}");
        }
    }

    /**
     * Sync approved resource files to Google Drive if configured.
     * Supports both files on local disk and files fetched from Telegram.
     */
    protected function syncDrive(Resource $resource): void
    {
        if (! $this->driveService->isConfigured()) {
            return;
        }

        try {
            $courseName = $resource->course?->name ?? 'General';
            $typeName = $resource->resourceType?->display_name ?? 'Notes';

            // Find or create Course folder, and Category subfolder in Drive
            $courseFolderId = $this->driveService->getOrCreateFolder($courseName);
            $targetFolderId = $this->driveService->getOrCreateFolder($typeName, $courseFolderId);

            foreach ($resource->files as $file) {
                if ($file->drive_file_id) {
                    continue;
                }

                $tempPath = null;
                $sourcePath = null;

                // Case 1: File is already stored locally on disk
                if ($file->local_path && Storage::exists($file->local_path)) {
                    $sourcePath = Storage::path($file->local_path);
                }
                // Case 2: File was uploaded via Telegram (fetch bytes from Telegram API)
                elseif ($file->telegram_file_id) {
                    $telegramFile = $this->telegram->getFile($file->telegram_file_id);
                    if ($telegramFile && isset($telegramFile['file_path'])) {
                        $downloadUrl = $this->telegram->getFileDownloadUrl($telegramFile['file_path']);
                        $fileContents = Http::timeout(60)->get($downloadUrl)->body();

                        $tempPath = tempnam(sys_get_temp_dir(), 'parsi_drive_');
                        file_put_contents($tempPath, $fileContents);
                        $sourcePath = $tempPath;
                    }
                }

                if ($sourcePath && file_exists($sourcePath)) {
                    $mime = $file->mime_type ?? 'application/pdf';
                    $driveFileId = $this->driveService->uploadFile(
                        $sourcePath,
                        $file->original_filename,
                        $mime,
                        $targetFolderId
                    );

                    if ($driveFileId) {
                        $file->update([
                            'drive_file_id' => $driveFileId,
                        ]);
                        Log::info("ResourceFile #{$file->id} synced to Google Drive with ID: {$driveFileId}");
                    }
                }

                if ($tempPath && file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Drive sync on moderation for Resource #{$resource->id} encountered an error: {$e->getMessage()}");
        }
    }

    /**
     * Send approved resource files to the Telegram storage channel.
     */
    protected function sendToStorageChannel(Resource $resource): void
    {
        $storageChannelId = config('telegram.storage_channel_id');
        if (! $storageChannelId) {
            return;
        }

        $courseName = $resource->course?->name ?? 'Course Material';
        $categoryName = $resource->resourceType?->display_name ?? 'Material';

        foreach ($resource->files as $file) {
            if (! $file->telegram_file_id) {
                continue;
            }
            
            $caption = "📚 <b>" . e($courseName) . "</b>\n📄 " . e($file->original_filename) . "\n🏷️ <b>Category:</b> " . e($categoryName);

            try {
                $isPhoto = str_starts_with($file->mime_type ?? '', 'image/');

                if ($isPhoto) {
                    $this->telegram->sendPhoto(
                        $storageChannelId,
                        $file->telegram_file_id,
                        $caption,
                        'HTML'
                    );
                } else {
                    $this->telegram->sendDocument(
                        $storageChannelId,
                        $file->telegram_file_id,
                        $caption,
                        'HTML'
                    );
                }

                Log::info("ResourceFile #{$file->id} dispatched to Telegram storage channel {$storageChannelId}");
            } catch (\Throwable $e) {
                Log::warning("Failed to send ResourceFile #{$file->id} to storage channel: " . $e->getMessage());
            }
        }
    }
}
