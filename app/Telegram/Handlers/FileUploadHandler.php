<?php

namespace App\Telegram\Handlers;

use App\Jobs\FinalizeSubmissionJob;
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

        if(! isset($state['title'])){

            $this->telegram->sendMessage(
                $chatId,
                __('bot.submit_material.choose_title', [], $lang),
                MainMenuKeyboard::build($lang)
            );

            return;
        }

        // 1. Extract file info
        $fileData = $this->extractFileInfo($message);
        if (! $fileData) {
            return;
        }

        // 2. Check if a Resource has already been started in this upload session
        $caption = isset($message['caption']) ? trim($message['caption']) : null;
        if (! empty($caption)) {
            $state['caption'] = $caption;
        } elseif (! empty($state['caption'])) {
            $caption = $state['caption'];
        }
        $resource = null;
        if (! empty($state['resource_id'])) {
            $resource = Resource::with('files')->find($state['resource_id']);
        }

        if (! $resource) {
            // First file: resolve category and create Resource record
            $resourceType = null;
            if (! empty($state['resource_type_id'])) {
                $resourceType = ResourceType::where('is_active', true)->find($state['resource_type_id']);
            }

            if (! $resourceType) {
                $resourceType = ResourceType::where('code', 'notes')->first()
                    ?? ResourceType::where('is_active', true)->first()
                    ?? ResourceType::first();
            }

            if (! $resourceType) {
                $resourceType = ResourceType::create([
                    'code' => 'notes',
                    'display_name' => 'Notes & Summaries',
                    'sort_order' => 1,
                ]);
            }

            $title = ! empty($state['title'])
                ? $state['title']
                : ($course->name . ' Material');

            $description = ! empty($caption) ? $caption : null;

            $resource = Resource::create([
                'course_id' => $course->id,
                'resource_type_id' => $resourceType->id,
                'title' => $title,
                'description' => $description,
                'status' => 'pending',
                'submitted_by' => $user->id,
            ]);

            $state['resource_id'] = $resource->id;

            // Schedule delayed finalize job in case user closes Telegram or forgets to tap Done
            FinalizeSubmissionJob::dispatch($user->id, $resource->id, $chatId)
                ->delay(now()->addMinutes(3));
        } else {
            // If resource was already created (e.g. earlier file in an album had no caption)
            // and this message brings the album's caption, update the resource description only:
            if (! empty($caption) && empty($resource->description)) {
                $resource->update([
                    'description' => $caption,
                ]);
            }
        }

        // 3. Create ResourceFile record and attach to this Resource
        ResourceFile::create([
            'resource_id' => $resource->id,
            'original_filename' => $fileData['original_filename'],
            'mime_type' => $fileData['mime_type'],
            'size_bytes' => $fileData['size_bytes'],
            'sha256' => hash('sha256', $fileData['telegram_file_unique_id'] . '_' . microtime()),
            'telegram_file_id' => $fileData['telegram_file_id'],
            'telegram_file_unique_id' => $fileData['telegram_file_unique_id'],
        ]);

        $totalFiles = $resource->files()->count();
        $filename = e($fileData['original_filename']);

        $messageText = __('bot.submit_material.file_received', [
            'filename' => $filename,
            'count' => $totalFiles,
        ], $lang);

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.submit_material.finish_button', ['count' => $totalFiles], $lang), 'callback_data' => 'finish_upload'],
                ],
                [
                    ['text' => __('bot.submit_material.cancel', [], $lang), 'callback_data' => 'cancel_upload'],
                ],
            ],
        ];

        // Edit the existing confirmation message if one was already sent in this session,
        // otherwise send a new confirmation message and store its ID in session state.
        $feedbackMsgId = $state['feedback_message_id'] ?? null;
        $edited = false;

        if ($feedbackMsgId) {
            try {
                $editResponse = $this->telegram->editMessageText($chatId, $feedbackMsgId, $messageText, $replyMarkup);
                $edited = $editResponse->successful();
            } catch (\Throwable $e) {
                $edited = false;
            }
        }

        if (! $edited) {
            $sendResponse = $this->telegram->sendMessage($chatId, $messageText, $replyMarkup);
            $newMsgId = $sendResponse->json('result.message_id');
            if ($newMsgId) {
                $state['feedback_message_id'] = $newMsgId;
            }
        }

        // Keep upload state active for another 5 minutes
        Cache::put($stateKey, $state, now()->addMinutes(5));
    }

    /**
     * Notify the Admins Telegram group of a pending submission with all attached files.
     */
    public function notifyAdminsForResource(Resource $resource, User $user): void
    {
        // Prevent duplicate admin notifications for the same resource
        $notifiedKey = "resource_admin_notified:{$resource->id}";
        if (Cache::has($notifiedKey)) {
            return;
        }
        Cache::put($notifiedKey, true, now()->addHours(24));

        $adminChatId = config('telegram.admin_chat_id');
        if (empty($adminChatId)) {
            return;
        }

        $course = $resource->course;
        $resourceType = $resource->resourceType;
        $files = $resource->files;

        $fileLines = [];
        foreach ($files as $file) {
            $fileLines[] = "• " . e($file->original_filename) . " (" . $file->formatted_size . ")";
        }
        $fileList = implode("\n", $fileLines);

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
            . "📚 <b>Course:</b> " . e($course?->name ?? 'Unknown') . "\n"
            . "📝 <b>Title:</b> " . e($resource->title) . "\n"
            . "📁 <b>Attached Files (" . $files->count() . "):</b>\n" . $fileList . "\n\n"
            . "👤 <b>Submitted by:</b> " . e($submitterInfo) . "\n"
            . "🏷 <b>Category:</b> " . e($resourceType?->display_name ?? 'General') . "\n"
            . "⏳ <b>Status:</b> <code>pending review</code>\n"
            . "🆔 <b>Resource ID:</b> <code>#{$resource->id}</code>"
            . ($resource->description ? "\n\n💬 <b>Description:</b> " . e($resource->description) : "");

        if (! $isValidExternalUrl) {
            $message .= "\n\n🔗 <b>Review Link:</b> <code>{$reviewUrl}</code>";
        }

        $primaryFile = $files->first();
        try {
            if ($primaryFile && ! empty($primaryFile->telegram_file_id)) {
                $isPhoto = str_starts_with($primaryFile->mime_type ?? '', 'image/');
                $response = $isPhoto
                    ? $this->telegram->sendPhoto($adminChatId, $primaryFile->telegram_file_id, $message, 'HTML', $replyMarkup)
                    : $this->telegram->sendDocument($adminChatId, $primaryFile->telegram_file_id, $message, 'HTML', $replyMarkup);

                if (! $response->json('ok')) {
                    $this->telegram->sendMessage($adminChatId, $message, $replyMarkup, 'HTML');
                }
            } else {
                $this->telegram->sendMessage($adminChatId, $message, $replyMarkup, 'HTML');
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send admin notification for uploaded resource', [
                'error' => $e->getMessage(),
                'resource_id' => $resource->id,
            ]);
        }
    }

    /**
     * Legacy helper to notify admins.
     */
    protected function notifyAdmins(
        Resource $resource,
        Course $course,
        ResourceType $resourceType,
        User $user,
        array $fileData,
        ?string $caption = null
    ): void {
        $this->notifyAdminsForResource($resource, $user);
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


}

