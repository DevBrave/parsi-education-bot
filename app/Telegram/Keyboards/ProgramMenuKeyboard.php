<?php

namespace App\Telegram\Keyboards;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Support\Collection;

class ProgramMenuKeyboard
{
    /**
     * Build inline keyboard of active programmes from database.
     */
    public static function programmes(?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $programs = Program::where('is_active', true)->orderBy('name')->get();

        $rows = [];
        foreach ($programs as $program) {
            $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
            $rows[] = [
                ['text' => "🎓 {$program->name}{$levelTag}", 'callback_data' => "prog_{$program->id}"],
            ];
        }

        $rows[] = [
            ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main_educational_files'],
        ];

        return ['inline_keyboard' => $rows];
    }

    /**
     * Build inline keyboard of active courses for a programme.
     */
    public static function courses(Program $program, ?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $courseButtons = [];
        foreach ($program->courses as $course) {
            $codeSuffix = $course->course_code ? " [{$course->course_code}]" : '';
            $courseButtons[] = [
                ['text' => "📖 {$course->name}{$codeSuffix}", 'callback_data' => "course_{$course->id}_{$program->id}"],
            ];
        }

        if (empty($courseButtons)) {
            $courseButtons[] = [
                ['text' => __('bot.courses.no_courses', [], $lang), 'callback_data' => 'noop'],
            ];
        }

        $courseButtons[] = [
            ['text' => __('bot.courses.change_program', [], $lang), 'callback_data' => 'change_program'],
        ];

        $courseButtons[] = [
            ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main_educational_files'],
        ];

        return ['inline_keyboard' => $courseButtons];
    }

    /**
     * Build inline keyboard of active courses for submitting materials.
     */
    public static function submitCourses(Program $program, ?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $buttons = [];
        foreach ($program->courses as $course) {
            $codeSuffix = $course->course_code ? " [{$course->course_code}]" : '';
            $buttons[] = [
                ['text' => "📤 {$course->name}{$codeSuffix}", 'callback_data' => "submit_res_{$course->id}"],
            ];
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => __('bot.courses.no_courses', [], $lang), 'callback_data' => 'noop'],
            ];
        }

        $buttons[] = [
            ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main'],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of resource types for a course.
     */
    public static function resourceTypes(Collection $resourceTypes, int $courseId, int $programId, ?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $buttons = [];
        foreach ($resourceTypes as $type) {
            $buttons[] = [
                ['text' => "📖 {$type->display_name}", 'callback_data' => "resource_type_{$type->id}_{$courseId}_{$programId}"],
            ];
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => __('bot.courses.no_types', [], $lang), 'callback_data' => 'noop'],
            ];
        }

        $buttons[] = [
            ['text' => __('bot.courses.back_courses', [], $lang), 'callback_data' => "prog_{$programId}"],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of available resources.
     */
    public static function resources(Collection $resources, int $courseId, int $programId, ?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $buttons = [];
        foreach ($resources as $resource) {
            $buttons[] = [
                ['text' => __("{$resource->title}"), 'callback_data' => "send_resource_{$resource->id}"],
            ];
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => __('bot.courses.no_materials', [], $lang), 'callback_data' => 'noop'],
            ];
        }

        // Button allowing students to contribute files
        $buttons[] = [
            ['text' => __('bot.courses.submit_for_course', [], $lang), 'callback_data' => "submit_res_{$courseId}"],
        ];

        $buttons[] = [
            ['text' => __('bot.courses.back_types', [], $lang), 'callback_data' => "course_{$courseId}_{$programId}"],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Single back to main menu inline keyboard.
     */
    public static function backToMain(?string $lang = 'en', ?string $section = null): array
    {
        $lang = $lang ?: 'en';
        $callbackData = $section ? "back_main_{$section}" : 'back_main';

        return [
            'inline_keyboard' => [
                [['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => $callbackData]],
            ],
        ];
    }
}
