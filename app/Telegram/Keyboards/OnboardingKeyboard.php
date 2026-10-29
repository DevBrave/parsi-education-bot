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
     * Inline keyboard for selecting degree levels (Step 2a of onboarding).
     */
    public static function degrees(?string $lang = 'en'): array
    {
        return ProgramMenuKeyboard::degrees($lang ?: 'en');
    }

    /**
     * Inline keyboard for selecting degree programme by degree level (Step 2b of onboarding).
     */
    public static function programsByDegree(string $degreeLevel, ?string $lang = 'en'): array
    {
        return ProgramMenuKeyboard::programsByDegree($degreeLevel, $lang ?: 'en', 'back_to_degrees');
    }

    /**
     * Inline keyboard for selecting specialization track for a program during onboarding (Step 2c).
     */
    public static function tracks(Program $program, ?string $lang = 'en'): array
    {
        $degreeLevel = $program->degree_level ?: 'MSc';
        $backCallback = "select_degree_{$degreeLevel}";

        return ProgramMenuKeyboard::tracks($program, $lang ?: 'en', $backCallback);
    }

    /**
     * Inline keyboard for selecting degree programme during onboarding (legacy/fallback).
     */
    public static function programmes(): array
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();

        $rows = [];
        foreach ($programs as $program) {
            $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
            $rows[] = [
                ['text' => "🎓 {$program->name}{$levelTag}", 'callback_data' => "select_program_{$program->id}"],
            ];
        }

        return ['inline_keyboard' => $rows];
    }
}
