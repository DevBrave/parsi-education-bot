<?php

namespace App\Telegram\Handlers;

use App\Models\Program;
use App\Models\Track;
use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Keyboards\MainMenuKeyboard;
use App\Telegram\Keyboards\OnboardingKeyboard;
use App\Telegram\Keyboards\ProgramMenuKeyboard;

class OnboardingHandler
{
    public function __construct(
        protected TelegramService $telegram
    ) {}

    /**
     * Handle onboarding callback queries or initial prompt messages.
     *
     * @return bool True if intercepted and handled, false otherwise.
     */
    public function handle(array $payload, User $user): bool
    {
        $message = $payload['message'] ?? null;
        $callbackQuery = $payload['callback_query'] ?? null;

        $chatId = $message['chat']['id'] ?? $callbackQuery['message']['chat']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;
        $callbackId = $callbackQuery['id'] ?? null;

        if (! $chatId) {
            return false;
        }

        // 1. Handle incoming onboarding callbacks
        if ($callbackQuery) {
            $data = $callbackQuery['data'] ?? '';

            if (str_starts_with($data, 'onboard_lang_')) {
                return $this->handleLanguageSelection($chatId, $messageId, $callbackId, $data, $user);
            }

            if (str_starts_with($data, 'select_degree_') || str_starts_with($data, 'onboard_degree_')) {
                return $this->handleDegreeSelection($chatId, $messageId, $callbackId, $data, $user);
            }

            if ($data === 'back_to_degrees' || $data === 'onboard_back_degrees') {
                return $this->handleBackToDegrees($chatId, $messageId, $callbackId, $user);
            }

            if (str_starts_with($data, 'select_program_') || str_starts_with($data, 'onboard_prog_')) {
                return $this->handleProgramSelection($chatId, $messageId, $callbackId, $data, $user);
            }

            if (str_starts_with($data, 'onboard_track_') || str_starts_with($data, 'track_')) {
                return $this->handleTrackSelection($chatId, $messageId, $callbackId, $data, $user);
            }
        }

        // 2. Dismiss any stray callback query spinner while still in onboarding
        if ($callbackId) {
            $this->telegram->answerCallbackQuery($callbackId);
        }

        // 3. Prompt user for missing step
        if (! $user->preferred_language) {
            $this->promptLanguage($chatId);

            return true;
        }

        if (! $user->program_id) {
            $this->promptDegreeLevel($chatId, $user->preferred_language);

            return true;
        }

        return false;
    }

    /**
     * Send Step 1: Language selection prompt.
     */
    public function promptLanguage(int $chatId): void
    {
        $text = __('bot.onboarding.language_prompt');
        $this->telegram->sendMessage($chatId, $text, OnboardingKeyboard::languages());
    }

    /**
     * Send Step 2a: Degree level selection prompt (BSc / MSc).
     */
    public function promptDegreeLevel(int $chatId, string $lang, ?int $messageId = null): void
    {
        $text = __('bot.onboarding.degree_prompt', [], $lang);

        if ($messageId) {
            $this->telegram->editMessageText($chatId, $messageId, $text, OnboardingKeyboard::degrees($lang));
        } else {
            $this->telegram->sendMessage($chatId, $text, OnboardingKeyboard::degrees($lang));
        }
    }

    /**
     * Send Step 2b: Programs belonging to the chosen degree level.
     */
    public function promptProgramsByDegree(int $chatId, string $degreeLevel, string $lang, ?int $messageId = null): void
    {
        $text = __('bot.onboarding.programs_by_degree_prompt', ['degree' => $degreeLevel], $lang);

        if ($messageId) {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $text,
                OnboardingKeyboard::programsByDegree($degreeLevel, $lang)
            );
        } else {
            $this->telegram->sendMessage(
                $chatId,
                $text,
                OnboardingKeyboard::programsByDegree($degreeLevel, $lang)
            );
        }
    }

    /**
     * Legacy/Fallback: Send degree programme selection prompt.
     */
    public function promptProgram(int $chatId, string $lang, ?int $messageId = null): void
    {
        $this->promptDegreeLevel($chatId, $lang, $messageId);
    }

    /**
     * Process language callback selection.
     */
    protected function handleLanguageSelection(
        int $chatId,
        ?int $messageId,
        ?string $callbackId,
        string $data,
        User $user
    ): bool {
        $lang = substr($data, strlen('onboard_lang_'));
        if (! in_array($lang, ['en', 'it', 'fa'], true)) {
            return false;
        }

        if ($callbackId) {
            $this->telegram->answerCallbackQuery($callbackId);
        }

        $user->update(['preferred_language' => $lang]);

        // If user already has a degree programme, complete onboarding
        if ($user->program_id) {
            $program = $user->program;
            $progName = $program ? $program->name : 'Your Degree';
            $this->finishOnboarding($chatId, $messageId, $user, $progName);
        } else {
            // Prompt for Step 2: Degree Level (BSc / MSc)
            $this->promptDegreeLevel($chatId, $lang, $messageId);
        }

        return true;
    }

    /**
     * Process degree level selection (Step 2a).
     */
    protected function handleDegreeSelection(
        int $chatId,
        ?int $messageId,
        ?string $callbackId,
        string $data,
        User $user
    ): bool {
        $prefix = str_starts_with($data, 'select_degree_') ? 'select_degree_' : 'onboard_degree_';
        $degreeLevel = substr($data, strlen($prefix));

        if ($callbackId) {
            $this->telegram->answerCallbackQuery($callbackId);
        }

        $lang = $user->preferred_language ?? 'en';
        $this->promptProgramsByDegree($chatId, $degreeLevel, $lang, $messageId);

        return true;
    }

    /**
     * Process "Back to Degree Levels" callback button.
     */
    protected function handleBackToDegrees(
        int $chatId,
        ?int $messageId,
        ?string $callbackId,
        User $user
    ): bool {
        if ($callbackId) {
            $this->telegram->answerCallbackQuery($callbackId);
        }

        $lang = $user->preferred_language ?? 'en';
        $this->promptDegreeLevel($chatId, $lang, $messageId);

        return true;
    }

    /**
     * Process degree programme callback selection (Step 2b).
     */
    protected function handleProgramSelection(
        int $chatId,
        ?int $messageId,
        ?string $callbackId,
        string $data,
        User $user
    ): bool {
        $prefix = str_starts_with($data, 'select_program_') ? 'select_program_' : 'onboard_prog_';
        $programId = (int) substr($data, strlen($prefix));
        $program = Program::with('tracks')->find($programId);

        if (! $program) {
            return false;
        }

        if ($callbackId) {
            $this->telegram->answerCallbackQuery($callbackId);
        }

        $user->update(['program_id' => $program->id, 'track_id' => null]);
        $lang = $user->preferred_language ?? 'en';

        // If this program has specialization tracks (e.g. Master's in Computer Engineering),
        // show Step 2c: Track / inner major selection!
        if ($program->tracks()->where('is_active', true)->exists()) {
            $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
            $programTitle = e($program->name) . $levelTag;
            $text = __('bot.tracks.choose_track', ['program' => $programTitle], $lang);

            $backCallback = $program->degree_level ? "select_degree_{$program->degree_level}" : 'back_to_degrees';
            $keyboard = ProgramMenuKeyboard::tracks(
                $program,
                $lang,
                backCallback: $backCallback,
                customPrefix: 'onboard_track_'
            );

            if ($messageId) {
                $this->telegram->editMessageText($chatId, $messageId, $text, $keyboard);
            } else {
                $this->telegram->sendMessage($chatId, $text, $keyboard);
            }

            return true;
        }

        $this->finishOnboarding($chatId, $messageId, $user, $program->name);

        return true;
    }

    /**
     * Process specialization track callback selection (Step 2c of onboarding).
     */
    protected function handleTrackSelection(
        int $chatId,
        ?int $messageId,
        ?string $callbackId,
        string $data,
        User $user
    ): bool {
        if ($callbackId) {
            $this->telegram->answerCallbackQuery($callbackId);
        }

        if (str_contains($data, 'all_') || str_ends_with($data, '_all')) {
            $parts = explode('_', $data);
            $programId = (int) end($parts);
            $program = Program::find($programId) ?? $user->program;
            $user->update(['track_id' => null]);
            $progName = $program ? $program->name : 'Your Degree';
            $this->finishOnboarding($chatId, $messageId, $user, $progName);

            return true;
        }

        $prefix = str_starts_with($data, 'onboard_track_') ? 'onboard_track_' : 'track_';
        $trackId = (int) substr($data, strlen($prefix));
        $track = Track::with('program')->find($trackId);

        if ($track) {
            $user->update([
                'program_id' => $track->program_id,
                'track_id' => $track->id,
            ]);
            $progTitle = $track->program ? "{$track->program->name} — {$track->name}" : $track->name;
            $this->finishOnboarding($chatId, $messageId, $user, $progTitle);

            return true;
        }

        return false;
    }

    /**
     * Finish onboarding: confirm settings and send localized main menu.
     */
    public function finishOnboarding(int $chatId, ?int $messageId, User $user, string $progName): void
    {
        $lang = $user->preferred_language ?? 'en';
        $completionText = __('bot.onboarding.completed', [
            'name' => e($user->first_name),
            'program' => e($progName),
        ], $lang);

        if ($messageId) {
            $this->telegram->editMessageText($chatId, $messageId, $completionText, ['inline_keyboard' => []]);
        }

        $readyText = __('bot.onboarding.ready', [], $lang);
        $this->telegram->sendMessage($chatId, $readyText, MainMenuKeyboard::build($lang));
    }
}
