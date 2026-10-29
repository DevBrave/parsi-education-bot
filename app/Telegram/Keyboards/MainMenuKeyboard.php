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
                    [['text' => '📚 منابع درسی'], ['text' => '📑 راهنماهای آموزشی']],
                    [['text' => '🔍 ادیزو'], ['text' => '👥 گروه‌های دانشجویی']],
                    [['text' => '⚠️ گزارش'], ['text' => '📞 تماس با ما']],
                    [['text' => "\u{200F}ℹ️ درباره ما"], ['text' => '⚙️ تنظیمات']],
                ],
                'resize_keyboard' => true,
                'is_persistent' => true,
                'one_time_keyboard' => false,
            ];
        }

        if ($lang === 'it') {
            return [
                'keyboard' => [
                    [['text' => '📚 Materiali Didattici'], ['text' => '📑 Guide e Tutorial']],
                    [['text' => '🔍 Edisu'], ['text' => '👥 Gruppi Studenti']],
                    [['text' => '⚠️ Segnalazioni'], ['text' => '📞 Contattaci']],
                    [['text' => 'ℹ️ Chi siamo'], ['text' => '⚙️ Impostazioni']],
                ],
                'resize_keyboard' => true,
                'is_persistent' => true,
                'one_time_keyboard' => false,
            ];
        }

        return [
            'keyboard' => [
                [['text' => '📚 Study Resources'], ['text' => '📑 Tutorials & Guides']],
                [['text' => '🔍 Edisu'], ['text' => '👥 Student Groups']],
                [['text' => '⚠️ Report'], ['text' => '📞 Contact Us']],
                [['text' => 'ℹ️ About Us'], ['text' => '⚙️ Settings']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
            'one_time_keyboard' => false,
        ];
    }
}
