<?php

namespace App\Telegram\Handlers;

use App\Models\Course;
use App\Models\Resource;
use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Keyboards\MainMenuKeyboard;
use App\Telegram\Keyboards\ProgramMenuKeyboard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MessageHandler
{
    public function __construct(
        protected TelegramService $telegram
    ) {}

    /**
     * Handle incoming text messages and commands.
     */
    public function handle(array $message, User $user): void
    {
        $chatId = $message['chat']['id'];
        $text = trim($message['text'] ?? '');
        $lang = $user->preferred_language ?? 'en';

        if (str_starts_with($text, '/start')) {
            $param = trim(substr($text, 6));
            if (str_starts_with($param, 'res_')) {
                $resourceId = (int) substr($param, 4);
                app(CallbackQueryHandler::class)->handleSendResourceDirect($chatId, $resourceId, $user);

                return;
            }
            if (str_starts_with($param, 'course_')) {
                $courseId = (int) substr($param, 7);
                app(CallbackQueryHandler::class)->handleSelectCourseDirect($chatId, $courseId, $user);

                return;
            }
            if (str_starts_with($param, 'report_')) {
                $payload = substr($param, 7);
                $parts = explode('_', $payload);
                $resourceId = (int) ($parts[0] ?? 0);
                $fileId = isset($parts[1]) ? (int) $parts[1] : 0;
                app(CallbackQueryHandler::class)->handleReportPromptDirect($chatId, $resourceId, $fileId, $user);

                return;
            }

            $this->sendMainMenu($chatId, $user);
        } elseif ($text === '/cancel' || strtolower($text) === 'cancel') {
            Cache::forget("user_upload_state:{$user->id}");
            $this->telegram->sendMessage(
                $chatId,
                __('bot.submit_material.cancelled', [], $lang),
                MainMenuKeyboard::build($lang)
            );
        } elseif (in_array($text, ['📚 Study Resources', '📚 Educational Files', '📚 منابع درسی', '📚 فایل‌های آموزشی', '📚 Materiali Didattici'], true)) {
            $this->sendEducationalFiles($chatId, $user);
        } elseif (in_array($text, ['📤 Submit Material', '📤 ارسال فایل درسی', '📤 Invia Materiale'], true)) {
            $this->sendSubmitPrompt($chatId, $user);
        } elseif (in_array($text, ['🔍 Edisu', '🔍 ادیزو'], true)) {
            $this->telegram->sendMessage(
                $chatId,
                __('bot.sections.edisu_coming_soon', [], $lang),
                ProgramMenuKeyboard::backToMain($lang, 'edisu')
            );
        } elseif (in_array($text, ['❓ Important Deadlines', '❓ Important Detail', '❓ ددلاین‌های مهم', '❓ Scadenze Importanti'], true)) {
            $this->telegram->sendMessage(
                $chatId,
                __('bot.sections.deadlines_coming_soon', [], $lang),
                ProgramMenuKeyboard::backToMain($lang, 'deadlines')
            );
        } elseif (in_array($text, ['⚙️ Settings', '⚙️ تنظیمات', '⚙️ Impostazioni', '/settings'], true)) {
            $this->sendSettingsMenu($chatId, $user);
        } elseif (in_array($text, ['🔎 Search Resources', '🔎 Cerca Risorse', '🔎  جستجوی منابع درسی', '🔍 Search Resources', '🔍 Cerca Risorse', '🔍 جستجوی منابع', '/search'], true)) {
            $this->sendSearchPrompt($chatId, $user);
        } elseif (str_starts_with($text, '/search ')) {
            $this->executeSearch($chatId, $user, trim(substr($text, 8)));
        } elseif (in_array($text, ['📑 Tutorials & Guides', '📑 راهنماهای آموزشی', '📑 Guide e Tutorial', '/tutorials', '/guides'], true)) {
            $this->sendTutorialsMenu($chatId, $user);
        } elseif (in_array($text, ['👥 Student Groups', '👥 گروه‌های دانشجویی', '👥 Gruppi Studenti', '/groups'], true)) {
            $this->sendStudentGroupsMenu($chatId, $user);
        } elseif (in_array($text, ['⚠️ Report', '⚠️ گزارش', '⚠️ Segnalazioni', '/report'], true)) {
            $this->sendReportMenu($chatId, $user);
        } elseif (in_array($text, ['📞 Contact Us', '📞 Contattaci', '📞 تماس با ما', '/contact'], true)) {
            $this->sendContactMenu($chatId, $user);
        } elseif (in_array($text, ['⚠️ Contact / Report', '⚠️ ارتباط / گزارش', '⚠️ Contatti / Segnalazioni'], true)) {
            $this->sendContactReportMenu($chatId, $user);
        } elseif (in_array($text, ['ℹ️ About Us', 'ℹ️ Chi siamo', 'ℹ️ درباره ما', "\u{200F}ℹ️ درباره ما", 'درباره ما ℹ️', '/about'], true)) {
            $this->sendAboutUsMenu($chatId, $user);
        } else {
            $contactCacheKey = "user_contact_state:{$user->id}";
            $contactState = Cache::get($contactCacheKey);
            if ($contactState && ($contactState['step'] ?? null) === 'awaiting_contact_message') {
                Cache::forget($contactCacheKey);
                $this->handleContactMessageSubmit($chatId, $user, trim($text));
                return;
            }

            $searchCacheKey = "user_search_state:{$user->id}";
            $searchState = Cache::get($searchCacheKey);
            if ($searchState && ($searchState['step'] ?? null) === 'awaiting_search_query') {
                Cache::forget($searchCacheKey);
                $this->executeSearch($chatId, $user, trim($text));
                return;
            }

            $cacheKey = "user_upload_state:{$user->id}";
            $state = Cache::get($cacheKey);
            if ($state && ($state['step'] ?? null) === 'awaiting_title') {
                $state['title'] = trim($text) ?: 'New Material';
                Cache::put($cacheKey, $state, now()->addMinutes(15));
                $this->telegram->sendMessage(
                    $chatId,
                    __('bot.submit_material.writing_title', ['title' => $state['title']], $lang),
                    ProgramMenuKeyboard::simpleYesOrNo($lang)
                );
                return;
            }

            if ($state && ($state['step'] ?? null) === 'awaiting_files') {
                $this->telegram->sendMessage(
                    $chatId,
                    __('bot.submit_material.waiting_for_files', ['title' => $state['title'] ?? 'Material'], $lang),
                    [
                        'inline_keyboard' => [
                            [
                                ['text' => __('bot.submit_material.cancel', [], $lang), 'callback_data' => 'cancel_upload'],
                            ],
                        ],
                    ]
                );
                return;
            }
            // TODO: Adding llm into the project and genrate a text based on user input
            $msg = __('bot.menu.choose_option', ['name' => e($user->first_name)], $lang);

            $this->telegram->sendMessage(
                $chatId,
                $msg,
                MainMenuKeyboard::build($lang)
            );
        }
    }

    /**
     * Send the main menu welcome message with localized reply keyboard buttons.
     */
    public function sendMainMenu(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $welcome = __('bot.menu.welcome', ['name' => e($user->first_name)], $lang);

        $this->telegram->sendMessage(
            $chatId,
            $welcome,
            MainMenuKeyboard::build($lang)
        );
    }

    /**
     * Send settings menu allowing the user to switch language or degree programme.
     */
    public function sendSettingsMenu(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $programName = $user->program ? $user->program->name : __('bot.settings.not_selected', [], $lang);
        $langLabel = match ($lang) {
            'it' => 'Italiano',
            'fa' => 'فارسی',
            default => 'English',
        };

        $text = __('bot.settings.title', [
            'lang' => $langLabel,
            'program' => e($programName),
        ], $lang);

        $this->telegram->sendMessage(
            $chatId,
            $text,
            [
                'inline_keyboard' => [
                    [['text' => __('bot.settings.change_language', [], $lang), 'callback_data' => 'change_language']],
                    [['text' => __('bot.settings.change_program', [], $lang), 'callback_data' => 'change_program']],
                    [['text' => __('bot.settings.back_main', [], $lang), 'callback_data' => 'back_main_settings']],
                ],
            ]
        );
    }

    /**
     * Send submit study material prompt.
     */
    public function sendSubmitPrompt(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';

        if ($user->program_id) {
            $program = $user->program;
            if ($program && $program->is_active) {
                $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
                $text = __('bot.courses.choose_year', [
                    'program' => e($program->name) . $levelTag,
                ], $lang);

                $this->telegram->sendMessage(
                    $chatId,
                    $text,
                    ProgramMenuKeyboard::years($program, $lang, forSubmission: true)
                );

                return;
            }
        }

        $this->telegram->sendMessage(
            $chatId,
            __('bot.courses.choose_program', [], $lang),
            ProgramMenuKeyboard::programmes($lang, backCallback: 'back_edu_hub', forSubmission: true)
        );
    }

    /**
     * Send educational resources hub menu.
     */
    public function sendEducationalFiles(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';

        $this->telegram->sendMessage(
            $chatId,
            __('bot.education_hub.title', [], $lang),
            ProgramMenuKeyboard::educationHub($lang)
        );
    }

    /**
     * Send a NEW message with inline programme buttons dynamically from database.
     */
    public function sendProgrammeSelector(int $chatId, ?string $intro = null, ?string $lang = 'en'): void
    {
        $lang = $lang ?: 'en';
        $intro = $intro ?? __('bot.courses.choose_program', [], $lang);

        $this->telegram->sendMessage(
            $chatId,
            $intro,
            ProgramMenuKeyboard::programmes($lang)
        );
    }

    /**
     * Send search prompt asking user for a course or keyword.
     */
    public function sendSearchPrompt(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        Cache::put("user_search_state:{$user->id}", ['step' => 'awaiting_search_query'], now()->addMinutes(10));

        $this->telegram->sendMessage(
            $chatId,
            __('bot.search.prompt', [], $lang),
            ProgramMenuKeyboard::searchPrompt($lang)
        );
    }

    /**
     * Execute search across active courses and approved resources.
     */
    public function executeSearch(int $chatId, User $user, string $query, bool $showNavigation = true): void
    {
        $lang = $user->preferred_language ?? 'en';

        if (mb_strlen($query) < 2) {
            $replyMarkup = $showNavigation ? ProgramMenuKeyboard::searchPrompt($lang) : null;
            $this->telegram->sendMessage(
                $chatId,
                __('bot.search.too_short', [], $lang),
                $replyMarkup
            );

            return;
        }

        // Search active courses with programs
        $courses = Course::with('programs')
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('course_code', 'like', "%{$query}%");
            })
            ->take(5)
            ->get();

        // Search approved resources (by title, description, or course name/code)
        $resources = Resource::where('status', 'approved')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('course', function ($cq) use ($query) {
                        $cq->where('name', 'like', "%{$query}%")
                            ->orWhere('course_code', 'like', "%{$query}%");
                    });
            })
            ->with(['course', 'resourceType', 'files'])
            ->latest()
            ->take(8)
            ->get();

        if ($courses->isEmpty() && $resources->isEmpty()) {
            $replyMarkup = $showNavigation ? [
                'inline_keyboard' => [
                    [['text' => __('bot.search.search_again', [], $lang), 'callback_data' => 'search_prompt']],
                    [['text' => __('bot.courses.back_edu', [], $lang), 'callback_data' => 'back_edu_hub']],
                ],
            ] : null;

            $this->telegram->sendMessage(
                $chatId,
                __('bot.search.no_results', ['query' => e($query)], $lang),
                $replyMarkup
            );

            return;
        }

        $count = $courses->count() + $resources->count();
        $text = __('bot.search.results_header', ['query' => e($query), 'count' => $count], $lang);

        $programId = $user->program_id ?? 0;
        $keyboard = ProgramMenuKeyboard::searchResults($courses, $resources, $lang, $programId, $showNavigation);

        $this->telegram->sendMessage($chatId, $text, $keyboard);
    }

    /**
     * Send PARSI Educational Guides & Tutorials menu (coming soon).
     */
    public function sendTutorialsMenu(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $this->telegram->sendMessage(
            $chatId,
            __('bot.sections.tutorials_coming_soon', [], $lang),
            ProgramMenuKeyboard::backToMain($lang, 'tutorials')
        );
    }

    /**
     * Send Student Groups menu (coming soon).
     */
    public function sendStudentGroupsMenu(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $this->telegram->sendMessage(
            $chatId,
            __('bot.sections.groups_coming_soon', [], $lang),
            ProgramMenuKeyboard::backToMain($lang, 'groups')
        );
    }

    /**
     * Send Report Menu.
     */
    public function sendReportMenu(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $this->telegram->sendMessage(
            $chatId,
            __('bot.report.title', [], $lang),
            ProgramMenuKeyboard::reportMenu($lang)
        );
    }

    /**
     * Send Contact Menu.
     */
    public function sendContactMenu(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $this->telegram->sendMessage(
            $chatId,
            __('bot.contact.hub_text', [], $lang),
            ProgramMenuKeyboard::contactMenu($lang)
        );
    }

    /**
     * Send Contact & Report Hub menu (compatibility fallback).
     */
    public function sendContactReportMenu(int $chatId, User $user): void
    {
        $this->sendContactMenu($chatId, $user);
    }

    /**
     * Handle submission of student contact / feedback message.
     */
    public function handleContactMessageSubmit(int $chatId, User $user, string $text): void
    {
        $lang = $user->preferred_language ?? 'en';

        if (mb_strlen($text) < 3) {
            $this->telegram->sendMessage($chatId, __('bot.search.too_short', [], $lang));
            return;
        }

        $adminChatId = config('telegram.admin_chat_id');
        if ($adminChatId) {
            $sender = $user->username
                ? '@' . $user->username
                : ($user->first_name ? $user->first_name : "User #{$user->telegram_user_id}");
            $program = $user->program?->name ?? 'Not set';

            $adminText = "📬 <b>New Student Feedback / Contact</b>\n\n"
                . "👤 <b>From:</b> " . e($sender) . " (ID: <code>{$user->telegram_user_id}</code>)\n"
                . "🎓 <b>Program:</b> " . e($program) . "\n\n"
                . "💬 <b>Message:</b>\n" . e($text);

            $this->telegram->sendMessage($adminChatId, $adminText);
        }

        $this->telegram->sendMessage(
            $chatId,
            __('bot.contact.sent_success', [], $lang),
            MainMenuKeyboard::build($lang)
        );
    }

    /**
     * Send About Us information card.
     */
    public function sendAboutUsMenu(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';
        $this->telegram->sendMessage(
            $chatId,
            __('bot.about.text', [], $lang),
            ProgramMenuKeyboard::aboutUsMenu($lang)
        );
    }
}

