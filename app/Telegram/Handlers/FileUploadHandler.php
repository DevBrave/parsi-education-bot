<?php

namespace App\Telegram\Handlers;

use App\Models\Course;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Keyboards\MainMenuKeyboard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FileUploadHandler
{
    public function __construct(
        protected TelegramService $telegram
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

        // 7. Send confirmation to user
        $courseName = e($course->name);
        $this->telegram->sendMessage(
            $chatId,
            __('bot.submit_material.success', ['course' => $courseName], $lang),
            MainMenuKeyboard::build($lang)
        );
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
}
