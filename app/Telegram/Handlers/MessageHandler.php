<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Keyboards\MainMenuKeyboard;
use App\Telegram\Keyboards\ProgramMenuKeyboard;

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

        if (str_starts_with($text, '/start')) {
            $this->sendMainMenu($chatId, $user);
        } elseif ($text === '📚 Educational Files') {
            $this->sendProgrammeSelector($chatId);
        } elseif ($text === '📤 Submit Material') {
            $this->telegram->sendMessage(
                $chatId,
                "📤 <b>Contribute Study Material</b>\n\n"
                . "Help fellow PoliTo students by submitting past exams, lecture notes, summaries, or exercises!\n\n"
                . "To submit:\n"
                . "1. Tap <b>📚 Educational Files</b>\n"
                . "2. Choose your Degree & Course\n"
                . "3. Tap <b>📤 Submit a Material for this Course</b>\n\n"
                . "Our admin team will review and verify your material before publishing it with credit to you! 🎓",
                ProgramMenuKeyboard::backToMain()
            );
        } elseif ($text === '🔍 Edisu') {
            $this->telegram->sendMessage(
                $chatId,
                "🔍 <b>Edisu</b>\n\nThis section is coming soon!",
                ProgramMenuKeyboard::backToMain()
            );
        } elseif ($text === '❓ Important Deadlines' || $text === '❓ Important Detail') {
            $this->telegram->sendMessage(
                $chatId,
                "❓ <b>Important Deadlines</b>\n\nThis section is coming soon!",
                ProgramMenuKeyboard::backToMain()
            );
        } elseif ($text === '⚠️ Contact / Report') {
            $this->telegram->sendMessage(
                $chatId,
                "⚠️ <b>Contact / Report</b>\n\nThis section is coming soon!",
                ProgramMenuKeyboard::backToMain()
            );
        } else {
            $this->telegram->sendMessage(
                $chatId,
                '👋 Hello '.e($user->first_name)."!\n\nPlease choose an option from the menu below, or send /start.",
                MainMenuKeyboard::build()
            );
        }
    }

    /**
     * Send the main menu welcome message with reply keyboard buttons.
     */
    public function sendMainMenu(int $chatId, User $user): void
    {
        $welcome = '👋 <b>Welcome to PARSI Education Bot, '.e($user->first_name)."!</b>\n\n"
            ."Find course resources, past exams, and lecture notes easily.\n"
            .'What would you like to do?';

        $this->telegram->sendMessage(
            $chatId,
            $welcome,
            MainMenuKeyboard::build()
        );
    }

    /**
     * Send a NEW message with inline programme buttons dynamically from database.
     */
    public function sendProgrammeSelector(int $chatId): void
    {
        $this->telegram->sendMessage(
            $chatId,
            "📚 <b>Educational Files</b>\n\nChoose your degree programme:",
            ProgramMenuKeyboard::programmes()
        );
    }
}
