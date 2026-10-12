<?php

namespace App\Telegram\Keyboards;

class MainMenuKeyboard
{
    /**
     * Build the main menu persistent reply keyboard based on preferred language.
     */
    public static function build(?string $lang = 'en'): array
    {
        $lang = $lang ?? 'en';

        if ($lang === 'fa') {
            return [
                'keyboard' => [
                    [['text' => '📚 فایل‌های آموزشی'], ['text' => '🔍 ادیزو']],
                    [['text' => '📤 ارسال فایل درسی'], ['text' => '❓ ددلاین‌های مهم']],
                    [['text' => '⚙️ تنظیمات'], ['text' => '⚠️ ارتباط / گزارش']],
                ],
                'resize_keyboard' => true,
                'is_persistent' => true,
                'one_time_keyboard' => false,
            ];
        }

        if ($lang === 'it') {
            return [
                'keyboard' => [
                    [['text' => '📚 Materiali Didattici'], ['text' => '🔍 Edisu']],
                    [['text' => '📤 Invia Materiale'], ['text' => '❓ Scadenze Importanti']],
                    [['text' => '⚙️ Impostazioni'], ['text' => '⚠️ Contatti / Segnalazioni']],
                ],
                'resize_keyboard' => true,
                'is_persistent' => true,
                'one_time_keyboard' => false,
            ];
        }

        return [
            'keyboard' => [
                [['text' => '📚 Educational Files'], ['text' => '🔍 Edisu']],
                [['text' => '📤 Submit Material'], ['text' => '❓ Important Deadlines']],
                [['text' => '⚙️ Settings'], ['text' => '⚠️ Contact / Report']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
            'one_time_keyboard' => false,
        ];
    }
}
