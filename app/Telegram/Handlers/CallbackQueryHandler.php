<?php

namespace App\Telegram\Handlers;

use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceReport;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\ResourceModerationService;
use App\Services\TelegramService;
use App\Telegram\Keyboards\MainMenuKeyboard;
use App\Telegram\Keyboards\OnboardingKeyboard;
use App\Telegram\Keyboards\ProgramMenuKeyboard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class CallbackQueryHandler
{
    public function __construct(
        protected TelegramService $telegram,
        protected MessageHandler $messageHandler,
        protected ResourceModerationService $moderationService
    ) {}

    /**
     * Handle inline button presses (callback queries).
     */
    public function handle(array $callbackQuery, User $user): void
    {
        $callbackId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';
        $lang = $user->preferred_language ?? 'en';

        // Only message-backed inline menus can be edited by this bot.
        if (! isset($callbackQuery['message']['chat']['id'], $callbackQuery['message']['message_id'])) {
            $this->telegram->answerCallbackQuery($callbackId);
            return;
        }

        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];

        // Handle one-click Admin Moderation callbacks from the Admin Group
        if (str_starts_with($data, 'admin_approve_')) {
            $resourceId = (int) substr($data, strlen('admin_approve_'));
            $this->handleAdminApprove($chatId, $messageId, $resourceId, $user, $callbackQuery);

            return;
        }

        if (str_starts_with($data, 'admin_reject_')) {
            $resourceId = (int) substr($data, strlen('admin_reject_'));
            $this->handleAdminReject($chatId, $messageId, $resourceId, $user, $callbackQuery);

            return;
        }

        // Acknowledge the button press to remove the loading spinner for student menus
        $this->telegram->answerCallbackQuery($callbackId);

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
        } elseif ($data === 'search_prompt') {
            Cache::put("user_search_state:{$user->id}", ['step' => 'awaiting_search_query'], now()->addMinutes(10));
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.search.prompt', [], $lang),
                ProgramMenuKeyboard::searchPrompt($lang)
            );
        } elseif ($data === 'contact_prompt') {
            Cache::put("user_contact_state:{$user->id}", ['step' => 'awaiting_contact_message'], now()->addMinutes(15));
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.contact.write_prompt', [], $lang),
                [
                    'inline_keyboard' => [
                        [
                            ['text' => __('bot.submit_material.cancel', [], $lang), 'callback_data' => 'cancel_contact'],
                        ],
                    ],
                ]
            );
        } elseif ($data === 'cancel_contact') {
            Cache::forget("user_contact_state:{$user->id}");
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.contact.cancelled', [], $lang),
                ['inline_keyboard' => []]
            );
            $this->telegram->sendMessage(
                $chatId,
                __('bot.menu.choose_option', ['name' => e($user->first_name)], $lang),
                MainMenuKeyboard::build($lang)
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
        } elseif (str_starts_with($data, 'course_year_')) {
            $this->handleSelectCourseYear($chatId, $messageId, $data, $user);
        } elseif (str_starts_with($data, 'course_')) {
            $this->handleSelectCourse($chatId, $messageId, $data, $user);
        } elseif (str_starts_with($data, 'resource_type_') || str_starts_with($data, 'res_page_')) {
            $this->handleSelectResourceType($chatId, $messageId, $data, $user);
        } elseif (str_starts_with($data, 'send_resource_')) {
            $this->handleSendResource($chatId, $data, $user);
        } elseif (str_starts_with($data, 'submit_prog_')) {
            $programId = (int) substr($data, strlen('submit_prog_'));
            $program = Program::where('is_active', true)->find($programId);
            if ($program) {
                $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
                $text = __('bot.courses.choose_year', [
                    'program' => e($program->name) . $levelTag,
                ], $lang);

                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    $text,
                    ProgramMenuKeyboard::years($program, $lang, forSubmission: true)
                );
            }
        } elseif (str_starts_with($data, 'submit_year_')) {
            $this->handleSelectSubmitCourseYear($chatId, $messageId, $data, $user);
        } elseif (str_starts_with($data, 'submit_course_')) {
            $parts = explode('_', $data);
            $courseId = (int) ($parts[2] ?? 0);
            $programId = (int) ($parts[3] ?? 0);
            $year = $parts[4] ?? 'all';
            $this->handleSelectSubmitCourseType($chatId, $messageId, $courseId, $programId, $year, $user);
        } elseif (str_starts_with($data, 'submit_type_')) {
            $parts = explode('_', $data);
            $courseId = (int) ($parts[2] ?? 0);
            $resourceTypeId = (int) ($parts[3] ?? 0);
            $this->handleSubmitResourcePrompt($chatId, $messageId, $courseId, $user, $resourceTypeId);
        } elseif (str_starts_with($data, 'submit_res_')) {
            $parts = explode('_', $data);
            $courseId = (int) ($parts[2] ?? 0);
            $resourceTypeId = isset($parts[3]) ? (int) $parts[3] : null;
            $this->handleSubmitResourcePrompt($chatId, $messageId, $courseId, $user, $resourceTypeId);
        } elseif ($data === 'cancel_upload') {
            $stateKey = "user_upload_state:{$user->id}";
            $state = Cache::get($stateKey);
            if ($state && ! empty($state['resource_id'])) {
                $resource = Resource::with('files')->find($state['resource_id']);
                if ($resource && $resource->status === 'pending') {
                    foreach ($resource->files as $file) {
                        $file->delete();
                    }
                    $resource->delete();
                }
            }
            Cache::forget($stateKey);
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
        } elseif ($data === 'finish_upload') {
            $this->handleFinishUpload($chatId, $messageId, $user);
        } elseif ($data == 'title_approved') {
            $stateKey = "user_upload_state:{$user->id}";
            $state = Cache::get($stateKey);
            if ($state) {
                $state['step'] = 'awaiting_files';
                Cache::put($stateKey, $state, now()->addMinutes(15));
            }

            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.submit_material.title_approved', [], $lang),
                ['inline_keyboard' => []]
            );
        } elseif ($data === 'title_reject') {
            $stateKey = "user_upload_state:{$user->id}";
            $state = Cache::get($stateKey);
            if ($state) {
                unset($state['title']);
                $state['step'] = 'awaiting_title';
                Cache::put($stateKey, $state, now()->addMinutes(15));
            }

            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.submit_material.title_rejected', [], $lang),
                ['inline_keyboard' => []]
            );
        } elseif (str_starts_with($data, 'report_prompt_')) {
            $payload = substr($data, strlen('report_prompt_'));
            $parts = explode('_', $payload);
            $resourceId = (int) ($parts[0] ?? 0);
            $fileId = isset($parts[1]) ? (int) $parts[1] : 0;
            $this->handleReportPrompt($chatId, $messageId, $resourceId, $fileId, $user);
        } elseif (str_starts_with($data, 'report_submit_')) {
            $payload = substr($data, strlen('report_submit_'));
            $parts = explode('_', $payload);
            if (count($parts) >= 3) {
                $resourceId = (int) $parts[0];
                $fileId = (int) $parts[1];
                $reason = $parts[2];
            } else {
                $resourceId = (int) ($parts[0] ?? 0);
                $fileId = 0;
                $reason = $parts[1] ?? 'other';
            }
            $this->handleReportSubmit($chatId, $messageId, $resourceId, $fileId, $reason, $user);
        } elseif ($data === 'report_cancel') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.reports.cancelled', [], $lang),
                ['inline_keyboard' => []]
            );
        }
    }

    protected function handleSelectSubmitCourseYear(int $chatId, int $messageId, string $data, User $user): void
    {
        // Format: submit_year_{programId}_{year}_{page}
        $lang = $user->preferred_language ?? 'en';
        $parts = explode('_', $data);
        $programId = (int) ($parts[2] ?? 0);
        $year = $parts[3] ?? 'all';
        $page = (int) ($parts[4] ?? 1);

        $program = Program::where('is_active', true)->find($programId);
        if (! $program) {
            return;
        }

        $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
        $text = __('bot.submit_material.choose_course', [
            'program' => e($program->name) . $levelTag,
        ], $lang);

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            $text,
            ProgramMenuKeyboard::submitCourses($program, $lang, $year, $page, 8)
        );
    }

    protected function handleSelectSubmitCourseType(int $chatId, int $messageId, int $courseId, int $programId, $year, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $course = Course::find($courseId);
        if (! $course) {
            return;
        }

        $text = __('bot.submit_material.choose_type', ['course' => e($course->name)], $lang);

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            $text,
            ProgramMenuKeyboard::submitResourceTypes($course, $programId, $year, $lang)
        );
    }

    protected function handleSubmitResourcePrompt(int $chatId, int $messageId, int $courseId, User $user, ?int $resourceTypeId = null): void
    {
        $lang = $user->preferred_language ?? 'en';
        $course = Course::find($courseId);
        $courseName = $course ? e($course->name) : 'this course';

        $type = $resourceTypeId ? ResourceType::find($resourceTypeId) : null;
        if ($type) {
            $courseName .= " ({$type->display_name})";
        }

        $cacheData = ['course_id' => $courseId];
        if ($resourceTypeId) {
            $cacheData['resource_type_id'] = $resourceTypeId;
        }
        $cacheData['step'] = 'awaiting_title';
        // Set user state in cache for 15 minutes (We check this in fileuploadhandler)
        Cache::put("user_upload_state:{$user->id}", $cacheData, now()->addMinutes(15));

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
        $program = Program::with('courses')->where('is_active', true)->find($programId);

        if ($program) {
            $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
            $programTitle = e($program->name) . $levelTag;

            $text = '';
            if ($user->program_id !== $program->id) {
                $user->update(['program_id' => $program->id]);
                $text .= __('bot.courses.saved_as_default', [], $lang);
            }

            $text .= __('bot.courses.choose_year', ['program' => $programTitle], $lang);

            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $text,
                ProgramMenuKeyboard::years($program, $lang)
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

    protected function handleSelectCourseYear(int $chatId, int $messageId, string $data, User $user): void
    {
        // Format: course_year_{programId}_{year}_{page}
        $lang = $user->preferred_language ?? 'en';
        $parts = explode('_', $data);
        $programId = (int) ($parts[2] ?? 0);
        $year = $parts[3] ?? 'all';
        $page = (int) ($parts[4] ?? 1);

        $program = Program::where('is_active', true)->find($programId);
        if (! $program) {
            return;
        }

        $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
        $yearLabel = $year === 'all'
            ? __('bot.courses.year_all', [], $lang)
            : (__('bot.courses.year_' . $year, [], $lang) ?: "Year {$year}");

        $text = "🎓 <b>" . e($program->name) . "{$levelTag}</b> — <b>{$yearLabel}</b>\n\n" .
            __('bot.courses.choose_course', ['program' => e($program->name)], $lang);

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            $text,
            ProgramMenuKeyboard::courses($program, $lang, $year, $page, 8)
        );
    }

    protected function handleSelectCourse(int $chatId, int $messageId, string $data, User $user): void
    {
        // Format: course_{courseId}_{programId}_{year}
        $lang = $user->preferred_language ?? 'en';
        $parts = explode('_', $data);
        $courseId = (int) ($parts[1] ?? 0);
        $programId = isset($parts[2]) ? (int) $parts[2] : null;
        $year = $parts[3] ?? 'all';

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
            ->orderBy('sort_order')
            ->get();

        Log::debug("Resource Types for {$course->name}", ['content' => $resourceTypes]);

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            __('bot.courses.choose_type', ['course' => e($course->name)], $lang),
            ProgramMenuKeyboard::resourceTypes($resourceTypes, $course->id, $programId, $lang, $year)
        );
    }

    protected function handleSelectResourceType(int $chatId, int $messageId, string $data, User $user): void
    {
        // Format from resourceTypes: resource_type_{typeId}_{courseId}_{programId}_{year}_{page}
        // Format from res_page: res_page_{typeId}_{courseId}_{programId}_{year}_{page}
        $lang = $user->preferred_language ?? 'en';
        $parts = explode('_', $data);

        $resourceTypeId = (int) ($parts[2] ?? 0);
        $courseId = (int) ($parts[3] ?? 0);
        $programId = (int) ($parts[4] ?? 0);
        $year = $parts[5] ?? 'all';
        $page = (int) ($parts[6] ?? 1);

        $course = Course::find($courseId);
        $type = ResourceType::find($resourceTypeId);

        $resources = Resource::with('files')
            ->where('course_id', $courseId)
            ->where('resource_type_id', $resourceTypeId)
            ->where('status', 'approved')
            ->whereHas('course', fn($query) => $query->where('is_active', true))
            ->get();

        $courseName = $course ? e($course->name) : '';
        $typeName = $type ? e($type->display_name) : '';

        $headerText = $resources->isEmpty()
            ? "📖 <b>{$courseName}</b> — <b>{$typeName}</b>\n\n" . __('bot.courses.no_materials', [], $lang)
            : "📖 <b>{$courseName}</b> — <b>{$typeName}</b>\n\n" . __('bot.courses.available_resources', [], $lang);

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            $headerText,
            ProgramMenuKeyboard::resources($resources, $courseId, $programId, $lang, $resourceTypeId, $year, $page, 6)
        );
    }

    public function handleSendResourceDirect(int $chatId, int $resourceId, User $user): void
    {
        $this->handleSendResource($chatId, "send_resource_{$resourceId}", $user);
    }

    public function handleSelectCourseDirect(int $chatId, int $courseId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $course = Course::with('programs')->where('is_active', true)->find($courseId);
        if (! $course) {
            return;
        }

        $parentProgram = $user->program_id ? $course->programs->firstWhere('id', $user->program_id) : null;
        if (! $parentProgram) {
            $parentProgram = $course->programs->first();
        }

        $programId = $parentProgram?->id ?? 0;
        $resourceTypes = ResourceType::where('is_active', true)->orderBy('sort_order')->get();

        $this->telegram->sendMessage(
            $chatId,
            __('bot.courses.choose_type', ['course' => e($course->name)], $lang),
            ProgramMenuKeyboard::resourceTypes($resourceTypes, $course->id, $programId, $lang, 'all')
        );
    }

    protected function handleSendResource(int $chatId, string $data, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $resourceId = substr($data, strlen('send_resource_'));
        $resource = ctype_digit($resourceId) ? Resource::with(['files', 'course.programs', 'submitter'])
            ->where('status', 'approved')
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
        $baseCaption = __('bot.resources.caption_header', [
            'course' => $courseName,
            'title' => $title,
        ], $lang);

        // Contributor display is currently disabled
        // if ($contributor) {
        //     $baseCaption .= __('bot.resources.caption_contributor', ['contributor' => $contributor], $lang);
        // }

        $channel = config('telegram.channel_username', 'PARSI_POLITO');
        $bot = config('telegram.bot_username', 'parsi_politobot');

        $socialFooter = __('bot.resources.caption_social_media', [
            'channel' => ltrim($channel, '@'),
            'bot' => ltrim($bot, '@'),
        ], $lang);

        $programId = $user->program_id ?? $resource->course?->programs?->first()?->id ?? 0;

        // 1. Try sending via Telegram if telegram_file_id exists
        $sentTgCount = 0;
        $failedTgFiles = collect();

        foreach ($tgFiles as $file) {
            $fileCaption = $baseCaption;

            $fileCaption .= $socialFooter;

            $fileMarkup = [
                'inline_keyboard' => [],
            ];

            if ($file->drive_file_id) {
                $fileMarkup['inline_keyboard'][] = [
                    [
                        'text' => __('bot.resources.download_drive', [], $lang),
                        'url' => "https://drive.google.com/file/d/{$file->drive_file_id}/view",
                    ],
                ];
            }

            $fileMarkup['inline_keyboard'][] = [
                [
                    'text' => __('bot.reports.button', [], $lang),
                    'callback_data' => "report_prompt_{$resource->id}_{$file->id}",
                ],
            ];

            try {
                $response = $this->telegram->sendDocument($chatId, $file->telegram_file_id, $fileCaption, 'HTML', $fileMarkup);
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
            $resource->increment('downloads_count');

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
            $resource->increment('downloads_count');
            $fullCaption = $baseCaption . $socialFooter;
            $this->sendGoogleDriveDownloadOption($chatId, $resource, $driveFiles, $programId, $lang, $fullCaption);

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

        $firstFileId = $driveFiles->first()?->id ?? 0;
        $buttons[] = [
            [
                'text' => __('bot.reports.button', [], $lang),
                'callback_data' => "report_prompt_{$resource->id}_{$firstFileId}",
            ],
        ];

        $buttons[] = [
            [
                'text' => __('bot.courses.back_types', [], $lang),
                'callback_data' => "course_{$resource->course_id}_{$programId}",
            ],
        ];

        $text = ($caption ? $caption . "\n\n" : '') . __('bot.resources.available_on_drive', [], $lang);

        $this->telegram->sendMessage($chatId, $text, ['inline_keyboard' => $buttons]);
    }

    /**
     * Prompt student with reason options to report a faulty resource.
     */
    protected function handleReportPrompt(int $chatId, int $messageId, int $resourceId, int $fileId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $resource = Resource::with('files')->find($resourceId);

        if (! $resource) {
            $this->telegram->sendMessage($chatId, __('bot.resources.not_available', [], $lang));
            return;
        }

        $targetFile = $fileId ? $resource->files->firstWhere('id', $fileId) : $resource->files->first();
        $targetFileId = $targetFile?->id ?? 0;
        $displayName = $targetFile?->original_filename ?: $resource->title;

        $text = __('bot.reports.prompt_title', ['title' => e($displayName)], $lang);
        $keyboard = ProgramMenuKeyboard::reportReasons($resourceId, $targetFileId, $lang);

        $this->telegram->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Submit a student report for a resource.
     */
    protected function handleReportSubmit(int $chatId, int $messageId, int $resourceId, int $fileId, string $reason, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $resource = Resource::with('files')->find($resourceId);

        if (! $resource) {
            $this->telegram->editMessageText($chatId, $messageId, __('bot.resources.not_available', [], $lang), ['inline_keyboard' => []]);
            return;
        }

        $targetFile = $fileId ? $resource->files->firstWhere('id', $fileId) : null;
        $validFileId = $targetFile?->id;

        // Prevent duplicate pending reports from the same user for the same resource & file
        $existing = ResourceReport::where('resource_id', $resourceId)
            ->when($validFileId, fn($q) => $q->where('resource_file_id', $validFileId))
            ->where('user_id', $user->id)
            ->where('status', ResourceReport::STATUS_PENDING)
            ->first();

        if ($existing) {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                __('bot.reports.already_reported', [], $lang),
                ['inline_keyboard' => []]
            );
            return;
        }

        ResourceReport::create([
            'resource_id' => $resource->id,
            'resource_file_id' => $validFileId,
            'user_id' => $user->id,
            'reason' => $reason,
            'status' => ResourceReport::STATUS_PENDING,
        ]);

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            __('bot.reports.success', [], $lang),
            ['inline_keyboard' => []]
        );
    }

    /**
     * Handle one-click resource approval from the Telegram Admin Group.
     */
    protected function handleAdminApprove(
        int|string $chatId,
        int $messageId,
        int $resourceId,
        User $user,
        array $callbackQuery
    ): void {
        $callbackId = $callbackQuery['id'];
        $adminChatId = config('telegram.admin_chat_id');

        $resource = Resource::with(['course.programs', 'submitter', 'resourceType'])->find($resourceId);
        if (! $resource) {
            $this->telegram->answerCallbackQuery($callbackId, '⚠️ Resource not found.');

            return;
        }

        // Check permission: must be staff or inside admin chat, and if major professor, must belong to their major
        $isGeneralStaff = $user->isAdmin() || $user->isEditor() || $user->isProfessor() || ($chatId == $adminChatId && ! $user->isMajorProfessor());
        $canApprove = $isGeneralStaff || ($user->isMajorProfessor() && $user->canApproveResource($resource));

        if (! $canApprove) {
            $msg = $user->isMajorProfessor()
                ? '⛔ You are only authorized to moderate materials for your assigned major.'
                : '⛔ You are not authorized to moderate this material.';
            $this->telegram->answerCallbackQuery($callbackId, $msg);

            return;
        }

        if ($resource->status === 'approved') {
            $this->telegram->answerCallbackQuery($callbackId, "ℹ️ Resource #{$resourceId} has already been approved.");

            return;
        }

        $this->moderationService->approve($resource, $user);

        // Update the message in Telegram group (media caption or plain text)
        $reviewerTag = $user->username ? "@{$user->username}" : ($user->first_name ?? "Admin #{$user->id}");
        $hasCaption = isset($callbackQuery['message']['caption']);
        $originalContent = $hasCaption
            ? $callbackQuery['message']['caption']
            : ($callbackQuery['message']['text'] ?? '');

        $cleanContent = preg_replace('/(\n\n.*⏳.*Status:.*)/i', '', $originalContent);
        if (empty($cleanContent)) {
            $cleanContent = $originalContent;
        }

        $newContent = $cleanContent . "\n\n✅ <b>APPROVED & PUBLISHED</b> by {$reviewerTag}\n📅 " . now()->format('Y-m-d H:i');

        // Update buttons: remove Approve/Reject buttons
        $updatedKeyboard = ['inline_keyboard' => []];
        $appUrl = rtrim(config('app.url'), '/');
        $host = parse_url($appUrl, PHP_URL_HOST);
        if ($host && ! in_array(strtolower($host), ['localhost', '127.0.0.1'], true) && filter_var($appUrl, FILTER_VALIDATE_URL)) {
            $updatedKeyboard['inline_keyboard'][] = [
                ['text' => '🔎 View in Web Panel', 'url' => "{$appUrl}/admin/resources/{$resource->id}/edit"],
            ];
        }

        if ($hasCaption) {
            $this->telegram->editMessageCaption($chatId, $messageId, $newContent, $updatedKeyboard, 'HTML');
        } else {
            $this->telegram->editMessageText($chatId, $messageId, $newContent, $updatedKeyboard, 'HTML');
        }

        $this->telegram->answerCallbackQuery($callbackId, "✅ Material #{$resourceId} approved and published!");
    }

    /**
     * Handle one-click resource rejection from the Telegram Admin Group.
     */
    protected function handleAdminReject(
        int|string $chatId,
        int $messageId,
        int $resourceId,
        User $user,
        array $callbackQuery
    ): void {
        $callbackId = $callbackQuery['id'];
        $adminChatId = config('telegram.admin_chat_id');

        $resource = Resource::with(['course.programs', 'submitter'])->find($resourceId);
        if (! $resource) {
            $this->telegram->answerCallbackQuery($callbackId, '⚠️ Resource not found.');

            return;
        }

        // Check permission: must be staff or inside admin chat, and if major professor, must belong to their major
        $isGeneralStaff = $user->isAdmin() || $user->isEditor() || $user->isProfessor() || ($chatId == $adminChatId && ! $user->isMajorProfessor());
        $canReject = $isGeneralStaff || ($user->isMajorProfessor() && $user->canApproveResource($resource));

        if (! $canReject) {
            $msg = $user->isMajorProfessor()
                ? '⛔ You are only authorized to moderate materials for your assigned major.'
                : '⛔ You are not authorized to moderate this material.';
            $this->telegram->answerCallbackQuery($callbackId, $msg);

            return;
        }

        if ($resource->status === 'rejected') {
            $this->telegram->answerCallbackQuery($callbackId, "ℹ️ Resource #{$resourceId} has already been rejected.");

            return;
        }

        $this->moderationService->reject($resource, $user);

        $reviewerTag = $user->username ? "@{$user->username}" : ($user->first_name ?? "Admin #{$user->id}");
        $hasCaption = isset($callbackQuery['message']['caption']);
        $originalContent = $hasCaption
            ? $callbackQuery['message']['caption']
            : ($callbackQuery['message']['text'] ?? '');

        $cleanContent = preg_replace('/(\n\n.*⏳.*Status:.*)/i', '', $originalContent);
        if (empty($cleanContent)) {
            $cleanContent = $originalContent;
        }

        $newContent = $cleanContent . "\n\n❌ <b>REJECTED</b> by {$reviewerTag}\n📅 " . now()->format('Y-m-d H:i');

        if ($hasCaption) {
            $this->telegram->editMessageCaption($chatId, $messageId, $newContent, ['inline_keyboard' => []], 'HTML');
        } else {
            $this->telegram->editMessageText($chatId, $messageId, $newContent, ['inline_keyboard' => []], 'HTML');
        }

        $this->telegram->answerCallbackQuery($callbackId, "❌ Material #{$resourceId} rejected.");
    }

    /**
     * Finalize multi-file upload session and notify admins.
     */
    protected function handleFinishUpload(int $chatId, int $messageId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $stateKey = "user_upload_state:{$user->id}";
        $state = Cache::get($stateKey);

        if (! $state || empty($state['resource_id'])) {
            $this->telegram->sendMessage(
                $chatId,
                __('bot.submit_material.no_course_selected', [], $lang),
                MainMenuKeyboard::build($lang)
            );

            return;
        }

        $resource = Resource::with(['course', 'resourceType', 'files'])->find($state['resource_id']);
        Cache::forget($stateKey);

        if (! $resource) {
            return;
        }

        // Notify admins with summary of all attached files
        $uploadHandler = app(FileUploadHandler::class);
        $uploadHandler->notifyAdminsForResource($resource, $user);

        $courseName = $resource->course ? e($resource->course->name) : 'Course';
        $count = $resource->files->count();

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            __('bot.submit_material.success', ['course' => $courseName, 'count' => $count], $lang),
            ['inline_keyboard' => []]
        );

        $this->telegram->sendMessage(
            $chatId,
            __('bot.menu.choose_option', ['name' => e($user->first_name)], $lang),
            MainMenuKeyboard::build($lang)
        );
    }
}
