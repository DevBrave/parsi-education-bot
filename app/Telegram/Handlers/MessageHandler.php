<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Keyboards\MainMenuKeyboard;
use App\Telegram\Keyboards\ProgramMenuKeyboard;
use Illuminate\Support\Facades\Cache;

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
        } elseif (in_array($text, ['⚠️ Contact / Report', '⚠️ ارتباط / گزارش', '⚠️ Contatti / Segnalazioni'], true)) {
            $this->telegram->sendMessage(
                $chatId,
                __('bot.sections.contact_coming_soon', [], $lang),
                ProgramMenuKeyboard::backToMain($lang, 'contact')
            );
        } else {

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
    protected function sendSubmitPrompt(int $chatId, User $user): void
    {
        $lang = $user->preferred_language ?? 'en';

        if ($user->program_id) {
            $program = $user->program;
            if ($program && $program->is_active) {
                $program->load(['courses' => function ($query) {
                    $query->where('is_active', true)->orderBy('name');
                }]);

                $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
                $text = __('bot.submit_material.choose_course', [
                    'program' => e($program->name) . $levelTag,
                ], $lang);

                $this->telegram->sendMessage(
                    $chatId,
                    $text,
                    ProgramMenuKeyboard::submitCourses($program, $lang)
                );

                return;
            }
        }

        $prompt = __('bot.submit_material.prompt', [], $lang);

        $this->telegram->sendMessage(
            $chatId,
            $prompt,
            ProgramMenuKeyboard::backToMain($lang)
        );
    }

    /**
     * Send educational files: direct courses if user has a saved programme, or selector.
     */
    public function sendEducationalFiles(int $chatId, User $user): void
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
                    ProgramMenuKeyboard::years($program, $lang)
                );

                return;
            }
        }

        $this->sendProgrammeSelector($chatId, null, $lang);
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
}
