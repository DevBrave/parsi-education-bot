<?php

namespace App\Telegram\Keyboards;

use App\Models\Program;

class OnboardingKeyboard
{
    /**
     * Inline keyboard for selecting preferred language.
     */
    public static function languages(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => 'English', 'callback_data' => 'onboard_lang_en'],
                    ['text' => 'Italiano', 'callback_data' => 'onboard_lang_it'],
                    ['text' => 'فارسی', 'callback_data' => 'onboard_lang_fa'],
                ],
            ],
        ];
    }

    /**
     * Inline keyboard for selecting degree programme during onboarding.
     */
    public static function programmes(): array
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();

        $rows = [];
        foreach ($programs as $program) {
            $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
            $rows[] = [
                ['text' => "🎓 {$program->name}{$levelTag}", 'callback_data' => "onboard_prog_{$program->id}"],
            ];
        }

        return ['inline_keyboard' => $rows];
    }
}
