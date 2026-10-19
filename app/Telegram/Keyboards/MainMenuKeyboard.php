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
                    [['text' => '📚 منابع درسی'], ['text' => '🔎  جستجوی منابع درسی']],
                    [['text' => '📤 ارسال فایل درسی'], ['text' => '🔍 ادیزو']],
                    [['text' => '❓ ددلاین‌های مهم'], ['text' => '⚠️ ارتباط / گزارش']],
                    [['text' => '⚙️ تنظیمات']],
                ],
                'resize_keyboard' => true,
                'is_persistent' => true,
                'one_time_keyboard' => false,
            ];
        }

        if ($lang === 'it') {
            return [
                'keyboard' => [
                    [['text' => '📚 Materiali Didattici'], ['text' => '🔎 Cerca Risorse']],
                    [['text' => '📤 Invia Materiale'], ['text' => '🔍 Edisu']],
                    [['text' => '❓ Scadenze Importanti'], ['text' => '⚙️ Impostazioni']],
                    [['text' => '⚠️ Contatti / Segnalazioni']],
                ],
                'resize_keyboard' => true,
                'is_persistent' => true,
                'one_time_keyboard' => false,
            ];
        }

        return [
            'keyboard' => [
                [['text' => '📚 Study Resources'], ['text' => '🔎 Search Resources']],
                [['text' => '📤 Submit Material'], ['text' => '🔍 Edisu']],
                [['text' => '❓ Important Deadlines'], ['text' => '⚙️ Settings']],
                [['text' => '⚠️ Contact / Report']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
            'one_time_keyboard' => false,
        ];
    }
}
