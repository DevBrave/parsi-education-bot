<?php

namespace App\Telegram\Handlers;

use App\Models\Program;
use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Keyboards\MainMenuKeyboard;
use App\Telegram\Keyboards\OnboardingKeyboard;

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

            if (str_starts_with($data, 'onboard_prog_')) {
                return $this->handleProgramSelection($chatId, $messageId, $callbackId, $data, $user);
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
            $this->promptProgram($chatId, $user->preferred_language);

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
     * Send Step 2: Degree programme selection prompt.
     */
    public function promptProgram(int $chatId, string $lang, ?int $messageId = null): void
    {
        $text = __('bot.onboarding.program_prompt', [], $lang);

        if ($messageId) {
            $this->telegram->editMessageText($chatId, $messageId, $text, OnboardingKeyboard::programmes());
        } else {
            $this->telegram->sendMessage($chatId, $text, OnboardingKeyboard::programmes());
        }
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
            // Prompt for Step 2: Degree Programme
            $this->promptProgram($chatId, $lang, $messageId);
        }

        return true;
    }

    /**
     * Process degree programme callback selection.
     */
    protected function handleProgramSelection(
        int $chatId,
        ?int $messageId,
        ?string $callbackId,
        string $data,
        User $user
    ): bool {
        $programId = (int) substr($data, strlen('onboard_prog_'));
        $program = Program::find($programId);

        if (! $program) {
            return false;
        }

        if ($callbackId) {
            $this->telegram->answerCallbackQuery($callbackId);
        }

        $user->update(['program_id' => $program->id]);

        $this->finishOnboarding($chatId, $messageId, $user, $program->name);

        return true;
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
