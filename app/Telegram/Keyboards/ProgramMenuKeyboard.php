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
    public static function programmes(): array
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();

        $rows = [];
        foreach ($programs as $program) {
            $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
            $rows[] = [
                ['text' => "🎓 {$program->name}{$levelTag}", 'callback_data' => "prog_{$program->id}"],
            ];
        }

        $rows[] = [
            ['text' => '⬅️ Back to Main Menu', 'callback_data' => 'back_main'],
        ];

        return ['inline_keyboard' => $rows];
    }

    /**
     * Build inline keyboard of active courses for a programme.
     */
    public static function courses(Program $program): array
    {
        $courseButtons = [];
        foreach ($program->courses as $course) {
            $codeSuffix = $course->course_code ? " [{$course->course_code}]" : '';
            $courseButtons[] = [
                ['text' => "📖 {$course->name}{$codeSuffix}", 'callback_data' => "course_{$course->id}_{$program->id}"],
            ];
        }

        if (empty($courseButtons)) {
            $courseButtons[] = [
                ['text' => 'ℹ️ No courses registered yet', 'callback_data' => 'noop'],
            ];
        }

        $courseButtons[] = [
            ['text' => '⬅️ Back to Programmes', 'callback_data' => 'edu_files'],
        ];

        return ['inline_keyboard' => $courseButtons];
    }

    /**
     * Build inline keyboard of resource types for a course.
     */
    public static function resourceTypes(Collection $resourceTypes, int $courseId, int $programId): array
    {
        $buttons = [];
        foreach ($resourceTypes as $type) {
            $buttons[] = [
                ['text' => "📖 {$type->display_name}", 'callback_data' => "resource_type_{$type->id}_{$courseId}_{$programId}"],
            ];
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => 'ℹ️ There is no resource for this', 'callback_data' => 'noop'],
            ];
        }

        $buttons[] = [
            ['text' => '⬅️ Back to Courses', 'callback_data' => "prog_{$programId}"],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of available resources.
     */
    public static function resources(Collection $resources, int $courseId, int $programId): array
    {
        $buttons = [];
        foreach ($resources as $resource) {
            $buttons[] = [
                ['text' => "✅ {$resource->title}", 'callback_data' => "send_resource_{$resource->id}"],
            ];
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => 'ℹ️ No materials uploaded for this yet', 'callback_data' => 'noop'],
            ];
        }

        // Button allowing students to contribute files
        $buttons[] = [
            ['text' => '📤 Submit a Material for this Course', 'callback_data' => "submit_res_{$courseId}"],
        ];

        $buttons[] = [
            ['text' => '⬅️ Back to Types', 'callback_data' => "course_{$courseId}_{$programId}"],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Single back to main menu inline keyboard.
     */
    public static function backToMain(): array
    {
        return [
            'inline_keyboard' => [
                [['text' => '⬅️ Back to Main Menu', 'callback_data' => 'back_main']],
            ],
        ];
    }
}
