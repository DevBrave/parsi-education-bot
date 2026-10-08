<?php

namespace App\Telegram\Keyboards;

class MainMenuKeyboard
{
    /**
     * Build the main menu persistent reply keyboard.
     */
    public static function build(): array
    {
        return [
            'keyboard' => [
                [['text' => '📚 Educational Files'], ['text' => '🔍 Edisu']],
                [['text' => '📤 Submit Material'], ['text' => '❓ Important Detail']],
                [['text' => '⚠️ Contact / Report']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
            'one_time_keyboard' => false,
        ];
    }
}
