<?php

namespace App\Telegram\Handlers;

use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Keyboards\MainMenuKeyboard;
use App\Telegram\Keyboards\OnboardingKeyboard;
use App\Telegram\Keyboards\ProgramMenuKeyboard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class CallbackQueryHandler
{
    public function __construct(
        protected TelegramService $telegram,
        protected MessageHandler $messageHandler
    ) {}

    /**
     * Handle inline button presses (callback queries).
     */
    public function handle(array $callbackQuery, User $user): void
    {
        $callbackId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';
        $lang = $user->preferred_language ?? 'en';

        // Acknowledge the button press to remove the loading spinner
        $this->telegram->answerCallbackQuery($callbackId);

        // Only message-backed inline menus can be edited by this bot.
        if (! isset($callbackQuery['message']['chat']['id'], $callbackQuery['message']['message_id'])) {
            return;
        }

        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];

        if ($data === 'back_main' || str_starts_with($data, 'back_main_')) {
            $sectionKey = str_starts_with($data, 'back_main_')
                ? substr($data, strlen('back_main_'))
                : null;

            $sectionTitle = $sectionKey && Lang::has("bot.sections.{$sectionKey}", $lang)
                ? __("bot.sections.{$sectionKey}", [], $lang)
                : __('bot.menu.default_section', [], $lang);

            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.menu.closed', ['lastSection' => $sectionTitle], $lang),
                ['inline_keyboard' => []]
            );

            $this->telegram->sendMessage(
                $chatId,
                __('bot.menu.choose_option', ['name' => e($user->first_name)], $lang),
                MainMenuKeyboard::build($lang)
            );
        } elseif ($data === 'change_program') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.settings.change_program_prompt', [], $lang),
                ProgramMenuKeyboard::programmes($lang)
            );
        } elseif ($data === 'change_language') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.settings.change_language_prompt', [], $lang),
                OnboardingKeyboard::languages()
            );
        } elseif ($data === 'edu_files') {
            if ($user->program_id && $user->program && $user->program->is_active) {
                $this->handleSelectProgram($chatId, $messageId, $user->program_id, $user);
            } else {
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    __('bot.courses.choose_program', [], $lang),
                    ProgramMenuKeyboard::programmes($lang)
                );
            }
        } elseif (str_starts_with($data, 'prog_')) {
            $this->handleSelectProgram($chatId, $messageId, (int) substr($data, 5), $user);
        } elseif (str_starts_with($data, 'course_')) {
            $this->handleSelectCourse($chatId, $messageId, $data, $user);
        } elseif (str_starts_with($data, 'resource_type_')) {
            $this->handleSelectResourceType($chatId, $messageId, $data, $user);
        } elseif (str_starts_with($data, 'send_resource_')) {
            $this->handleSendResource($chatId, $data, $user);
        } elseif (str_starts_with($data, 'submit_res_')) {
            $this->handleSubmitResourcePrompt($chatId, $messageId, (int) substr($data, 11), $user);
        } elseif ($data === 'cancel_upload') {
            Cache::forget("user_upload_state:{$user->id}");
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.submit_material.cancelled', [], $lang),
                ['inline_keyboard' => []]
            );
            $this->telegram->sendMessage(
                $chatId,
                __('bot.menu.choose_option', ['name' => e($user->first_name)], $lang),
                MainMenuKeyboard::build($lang)
            );
        }
    }

    protected function handleSubmitResourcePrompt(int $chatId, int $messageId, int $courseId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $course = Course::find($courseId);
        $courseName = $course ? e($course->name) : 'this course';

        // Set user state in cache for 15 minutes
        Cache::put("user_upload_state:{$user->id}", [
            'course_id' => $courseId,
        ], now()->addMinutes(15));

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            __('bot.submit_material.course_prompt', ['course' => $courseName], $lang),
            [
                'inline_keyboard' => [
                    [['text' => __('bot.submit_material.cancel', [], $lang), 'callback_data' => 'cancel_upload']],
                ],
            ]
        );
    }

    protected function handleSelectProgram(int $chatId, int $messageId, int $programId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $program = Program::with(['courses' => function ($query) {
            $query->where('is_active', true)->orderBy('name');
        }])->where('is_active', true)->find($programId);

        if ($program) {
            $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
            $programTitle = e($program->name) . $levelTag;

            $text = '';
            if ($user->program_id !== $program->id) {
                $user->update(['program_id' => $program->id]);
                $text .= __('bot.courses.saved_as_default', [], $lang);
            }

            $text .= __('bot.courses.choose_course', ['program' => $programTitle], $lang);

            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $text,
                ProgramMenuKeyboard::courses($program, $lang)
            );
        } else {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.courses.program_not_found', [], $lang),
                ProgramMenuKeyboard::programmes($lang)
            );
        }
    }

    protected function handleSelectCourse(int $chatId, int $messageId, string $data, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $parts = explode('_', $data);
        $courseId = (int) ($parts[1] ?? 0);
        $programId = isset($parts[2]) ? (int) $parts[2] : null;

        $course = Course::with('programs')->where('is_active', true)->find($courseId);

        if (! $course) {
            return;
        }

        $parentProgram = $programId === null
            ? $course->programs->first()
            : $course->programs->firstWhere('id', $programId);

        if (! $parentProgram || ! $parentProgram->is_active) {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.courses.choose_program', [], $lang),
                ProgramMenuKeyboard::programmes($lang)
            );

            return;
        }

        $programId = $parentProgram->id;

        $resourceTypes = ResourceType::where('is_active', true)
            ->whereHas('resources', function ($query) use ($course) {
                $query->where('course_id', $course->id)
                    ->where('status', 'approved');
            })
            ->orderBy('sort_order')
            ->get();

        Log::debug("Resource Types for {$course->name}", ['content' => $resourceTypes]);

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            __('bot.courses.choose_type', ['course' => e($course->name)], $lang),
            ProgramMenuKeyboard::resourceTypes($resourceTypes, $course->id, $programId, $lang)
        );
    }

    protected function handleSelectResourceType(int $chatId, int $messageId, string $data, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $parts = explode('_', $data);
        $resourceTypeId = (int) ($parts[2] ?? 0);
        $courseId = (int) ($parts[3] ?? 0);
        $programId = isset($parts[4]) ? (int) $parts[4] : null;

        $resources = Resource::with('files')
            ->where('course_id', $courseId)
            ->where('resource_type_id', $resourceTypeId)
            ->where('status', 'approved')
            ->whereHas('course', fn($query) => $query->where('is_active', true))
            ->get();

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            __('bot.courses.available_resources', [], $lang),
            ProgramMenuKeyboard::resources($resources, $courseId, $programId ?? 0, $lang)
        );
    }

    protected function handleSendResource(int $chatId, string $data, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $resourceId = substr($data, strlen('send_resource_'));
        $resource = ctype_digit($resourceId) ? Resource::with(['files', 'course.programs', 'submitter'])
            ->where('status', 'approved')
            ->whereHas('resourceType', fn($query) => $query->where('is_active', true))
            ->whereHas('course', fn($query) => $query->where('is_active', true)
                ->whereHas('programs', fn($programQuery) => $programQuery->where('is_active', true)))
            ->find((int) $resourceId) : null;

        if (! $resource) {
            $this->telegram->sendMessage($chatId, __('bot.resources.not_available', [], $lang));

            return;
        }

        $allFiles = $resource->files;
        $tgFiles = $allFiles->filter(fn($file) => filled($file->telegram_file_id));
        $driveFiles = $allFiles->filter(fn($file) => filled($file->drive_file_id));

        if ($tgFiles->isEmpty() && $driveFiles->isEmpty()) {
            $this->telegram->sendMessage($chatId, __('bot.resources.no_files', [], $lang));

            return;
        }

        $courseName = $resource->course?->name ? e($resource->course->name) : 'Course Material';
        $title = e($resource->title);
        $contributor = $resource->submitter?->username
            ? '@' . e($resource->submitter->username)
            : ($resource->submitter?->first_name ? e($resource->submitter->first_name) : null);

        $caption = __('bot.resources.caption_header', [
            'course' => $courseName,
            'title' => $title,
        ], $lang);

        if ($contributor) {
            $caption .= __('bot.resources.caption_contributor', ['contributor' => $contributor], $lang);
        }

        $programId = $user->program_id ?? $resource->course?->programs?->first()?->id ?? 0;

        // 1. Try sending via Telegram if telegram_file_id exists
        $sentTgCount = 0;
        $failedTgFiles = collect();

        foreach ($tgFiles as $file) {
            try {
                $response = $this->telegram->sendDocument($chatId, $file->telegram_file_id, $caption);
                if ($response->json('ok')) {
                    $sentTgCount++;
                } else {
                    $failedTgFiles->push($file);
                }
            } catch (ConnectionException $exception) {
                Log::warning('Telegram document connection failed', ['file_id' => $file->id]);
                $failedTgFiles->push($file);
            }
        }

        // If at least one file was sent via Telegram
        if ($sentTgCount > 0) {
            $unsentDriveFiles = $allFiles->filter(function ($file) use ($failedTgFiles) {
                return (blank($file->telegram_file_id) || $failedTgFiles->contains('id', $file->id))
                    && filled($file->drive_file_id);
            });

            if ($unsentDriveFiles->isNotEmpty()) {
                $this->sendGoogleDriveDownloadOption($chatId, $resource, $unsentDriveFiles, $programId, $lang);
            } elseif ($sentTgCount < $allFiles->count()) {
                $this->telegram->sendMessage($chatId, __('bot.resources.partial_files_error', [], $lang));
            }

            return;
        }

        // 2. Solution 2: Direct Google Drive link button when telegram_file_id is missing or failed
        if ($driveFiles->isNotEmpty()) {
            $this->sendGoogleDriveDownloadOption($chatId, $resource, $driveFiles, $programId, $lang, $caption);

            return;
        }

        $this->telegram->sendMessage($chatId, __('bot.resources.send_error', [], $lang));
    }

    /**
     * Send direct Google Drive download button(s) when Telegram delivery is unavailable or secondary.
     */
    protected function sendGoogleDriveDownloadOption(
        int $chatId,
        Resource $resource,
        $driveFiles,
        int $programId,
        string $lang,
        ?string $caption = null
    ): void {
        $buttons = [];

        foreach ($driveFiles as $file) {
            $label = $driveFiles->count() === 1
                ? __('bot.resources.download_drive', [], $lang)
                : __('bot.resources.download_file_drive', ['filename' => $file->original_filename ?: 'File'], $lang);

            $buttons[] = [
                [
                    'text' => $label,
                    'url' => "https://drive.google.com/file/d/{$file->drive_file_id}/view",
                ],
            ];
        }

        $buttons[] = [
            [
                'text' => __('bot.courses.back_types', [], $lang),
                'callback_data' => "course_{$resource->course_id}_{$programId}",
            ],
        ];

        $text = ($caption ? $caption . "\n\n" : '') . __('bot.resources.available_on_drive', [], $lang);

        $this->telegram->sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
    }
}
