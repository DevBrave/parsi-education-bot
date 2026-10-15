<?php

namespace App\Telegram\Handlers;

use App\Models\Course;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use App\Telegram\Keyboards\MainMenuKeyboard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadHandler
{
    public function __construct(
        protected TelegramService $telegram,
        protected GoogleDriveService $driveService
    ) {}

    /**
     * Handle incoming document and photo messages.
     */
    public function handle(array $message, User $user): void
    {
        $chatId = $message['chat']['id'] ?? null;
        if (! $chatId) {
            return;
        }

        $lang = $user->preferred_language ?? 'en';
        $stateKey = "user_upload_state:{$user->id}";
        $state = Cache::get($stateKey);

        if (! $state || ! isset($state['course_id'])) {
            $this->telegram->sendMessage(
                $chatId,
                __('bot.submit_material.no_course_selected', [], $lang),
                MainMenuKeyboard::build($lang)
            );

            return;
        }

        $course = Course::find($state['course_id']);
        if (! $course) {
            Cache::forget($stateKey);
            $this->telegram->sendMessage(
                $chatId,
                __('bot.submit_material.no_course_selected', [], $lang),
                MainMenuKeyboard::build($lang)
            );

            return;
        }

        // 1. Extract file info
        $fileData = $this->extractFileInfo($message);
        if (! $fileData) {
            return;
        }

        // 2. Resolve default resource type (notes or first available)
        $resourceType = ResourceType::where('code', 'notes')->first()
            ?? ResourceType::first();

        if (! $resourceType) {
            $resourceType = ResourceType::create([
                'code' => 'notes',
                'display_name' => 'Notes & Summaries',
                'sort_order' => 1,
            ]);
        }

        // 3. Extract title and description
        $caption = isset($message['caption']) ? trim($message['caption']) : null;
        $title = $caption ? mb_substr($caption, 0, 255) : pathinfo($fileData['original_filename'], PATHINFO_FILENAME);
        if (empty($title)) {
            $title = $course->name . ' Material';
        }

        // 4. Create Resource record
        $resource = Resource::create([
            'course_id' => $course->id,
            'resource_type_id' => $resourceType->id,
            'title' => $title,
            'description' => $caption,
            'status' => 'pending',
            'submitted_by' => $user->id,
        ]);

        // 5. Create ResourceFile record
        ResourceFile::create([
            'resource_id' => $resource->id,
            'original_filename' => $fileData['original_filename'],
            'mime_type' => $fileData['mime_type'],
            'size_bytes' => $fileData['size_bytes'],
            'sha256' => hash('sha256', $fileData['telegram_file_unique_id'] . '_' . microtime()),
            'telegram_file_id' => $fileData['telegram_file_id'],
            'telegram_file_unique_id' => $fileData['telegram_file_unique_id'],
        ]);


        // 6. Clear upload state
        Cache::forget($stateKey);

        Log::info("User {$user->id} submitted material for course {$course->id}", [
            'resource_id' => $resource->id,
            'filename' => $fileData['original_filename'],
        ]);
         // 7. Notify Admins Group
        $this->notifyAdmins($resource, $course, $resourceType, $user, $fileData,$caption);

        // 8. Send confirmation to user
        $courseName = e($course->name);
        $this->telegram->sendMessage(
            $chatId,
            __('bot.submit_material.success', ['course' => $courseName], $lang),
            MainMenuKeyboard::build($lang)
        );

       
    }

    /**
     * Notify the Admins Telegram group of a new pending submission.
     */
    protected function notifyAdmins(
        Resource $resource,
        Course $course,
        ResourceType $resourceType,
        User $user,
        array $fileData,
        ?string $caption = null
    ): void {
        $adminChatId = config('telegram.admin_chat_id');
        if (empty($adminChatId)) {
            return;
        }

        $sizeFormatted = $this->formatBytes($fileData['size_bytes']);
        $submitterInfo = $user->username
            ? "@{$user->username}"
            : "ID: {$user->telegram_user_id} (" . trim("{$user->first_name} {$user->last_name}") . ")";

        $appUrl = rtrim(config('app.url'), '/');
        $reviewUrl = "{$appUrl}/admin/resources/{$resource->id}/edit";
        $host = parse_url($reviewUrl, PHP_URL_HOST);
        $isValidExternalUrl = $host && ! in_array(strtolower($host), ['localhost', '127.0.0.1'], true) && filter_var($reviewUrl, FILTER_VALIDATE_URL);

        $inlineRows = [
            [
                ['text' => '✅ Approve & Publish', 'callback_data' => "admin_approve_{$resource->id}"],
                ['text' => '❌ Reject', 'callback_data' => "admin_reject_{$resource->id}"],
            ],
        ];

        if ($isValidExternalUrl) {
            $inlineRows[] = [
                ['text' => '🔎 Review in Admin Dashboard', 'url' => $reviewUrl],
            ];
        }

        $replyMarkup = ['inline_keyboard' => $inlineRows];

        $message = "📥 <b>New Material Uploaded (Pending Review)</b>\n\n"
            . "📚 <b>Course:</b> " . e($course->name) . "\n"
            . "📄 <b>File:</b> " . e($fileData['original_filename']) . " ({$sizeFormatted})\n"
            . "👤 <b>Submitted by:</b> " . e($submitterInfo) . "\n"
            . "🏷 <b>Category:</b> " . e($resourceType->display_name) . "\n"
            . "⏳ <b>Status:</b> <code>pending review</code>\n"
            . "🆔 <b>Resource ID:</b> <code>#{$resource->id}</code>"
            . ($caption === null || $caption === ''
                ? "\n\n⚠️ <i>Please add a caption from admin panel</i>"
                : "\n\n📝 <b>Caption:</b> " . e($caption));

        if (! $isValidExternalUrl) {
            $message .= "\n\n🔗 <b>Review Link:</b> <code>{$reviewUrl}</code>";
        }

        try {
            if (! empty($fileData['telegram_file_id'])) {
                $isPhoto = str_starts_with($fileData['mime_type'] ?? '', 'image/');

                $response = $isPhoto
                    ? $this->telegram->sendPhoto(
                        $adminChatId,
                        $fileData['telegram_file_id'],
                        $message,
                        'HTML',
                        $replyMarkup
                    )
                    : $this->telegram->sendDocument(
                        $adminChatId,
                        $fileData['telegram_file_id'],
                        $message,
                        'HTML',
                        $replyMarkup
                    );

                if (! $response->json('ok')) {
                    // Fallback to text message if media dispatch failed
                    $this->telegram->sendMessage($adminChatId, $message, $replyMarkup, 'HTML');
                }
            } else {
                $this->telegram->sendMessage($adminChatId, $message, $replyMarkup, 'HTML');
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send admin notification for uploaded file', [
                'error' => $e->getMessage(),
                'resource_id' => $resource->id,
            ]);
        }
    }

    /**
     * Format bytes into human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 1) . ' ' . $units[$pow];
    }

    /**
     * Extract file details from message payload.
     */
    protected function extractFileInfo(array $message): ?array
    {
        if (isset($message['document'])) {
            $doc = $message['document'];

            return [
                'telegram_file_id' => $doc['file_id'],
                'telegram_file_unique_id' => $doc['file_unique_id'],
                'original_filename' => $doc['file_name'] ?? 'document.pdf',
                'mime_type' => $doc['mime_type'] ?? 'application/octet-stream',
                'size_bytes' => (int) ($doc['file_size'] ?? 0),
            ];
        }

        if (isset($message['photo']) && is_array($message['photo']) && ! empty($message['photo'])) {
            $photo = end($message['photo']);

            return [
                'telegram_file_id' => $photo['file_id'],
                'telegram_file_unique_id' => $photo['file_unique_id'],
                'original_filename' => 'photo_' . date('Ymd_His') . '.jpg',
                'mime_type' => 'image/jpeg',
                'size_bytes' => (int) ($photo['file_size'] ?? 0),
            ];
        }

        return null;
    }

    /**
     * Automatically download the file from Telegram and sync it to Google Drive if configured.
     * Note: Telegram Bot API limits getFile to 20MB.
     */
    protected function syncToDriveIfPossible(ResourceFile $file, Course $course, ResourceType $resourceType): void
    {
        if (! $this->driveService->isConfigured()) {
            return;
        }

        // Telegram Bot API getFile limit is 20MB
        if ($file->size_bytes > 20 * 1024 * 1024) {
            Log::info("File #{$file->id} ({$file->formatted_size}) exceeds Telegram's 20MB getFile limit. Drive auto-sync skipped.");

            return;
        }

        try {
            $telegramFile = $this->telegram->getFile($file->telegram_file_id);
            if (! $telegramFile || empty($telegramFile['file_path'])) {
                return;
            }

            $downloadUrl = $this->telegram->getFileDownloadUrl($telegramFile['file_path']);
            $response = Http::timeout(60)->get($downloadUrl);

            if (! $response->successful()) {
                return;
            }

            // Save locally in resources directory
            $ext = pathinfo($file->original_filename, PATHINFO_EXTENSION) ?: 'bin';
            $storedPath = 'resources/' . Str::random(40) . '.' . $ext;
            Storage::disk('local')->put($storedPath, $response->body());

            $fullPath = Storage::disk('local')->path($storedPath);

            $courseFolderId = $this->driveService->getOrCreateFolder($course->name);
            $typeFolderId = $this->driveService->getOrCreateFolder($resourceType->display_name, $courseFolderId);

            $driveFileId = $this->driveService->uploadFile(
                filePath: $fullPath,
                filename: $file->original_filename,
                mimeType: $file->mime_type,
                folderId: $typeFolderId,
            );

            if ($driveFileId) {
                $file->update([
                    'local_path' => $storedPath,
                    'drive_file_id' => $driveFileId,
                ]);
                Log::info("File #{$file->id} successfully synced to Google Drive with ID: {$driveFileId}");
            }
        } catch (\Throwable $e) {
            Log::warning("Drive auto-sync on upload for file #{$file->id} failed: " . $e->getMessage());
        }
    }
}

