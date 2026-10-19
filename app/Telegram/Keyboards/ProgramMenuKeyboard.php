<?php

namespace App\Telegram\Keyboards;

use App\Models\Course;
use App\Models\Program;
use App\Models\ResourceType;
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
     * Build inline keyboard of academic years for a programme.
     */
    public static function years(Program $program, ?string $lang = 'en', bool $forSubmission = false): array
    {
        $lang = $lang ?: 'en';
        $prefix = $forSubmission ? 'submit_year' : 'course_year';
        $backCallback = $forSubmission ? 'back_main_submit' : 'back_main_educational_files';

        // Detect years present in this program's active courses
        $distinctYears = $program->courses()
            ->where('is_active', true)
            ->whereNotNull('year')
            ->distinct()
            ->pluck('year')
            ->sort()
            ->values();

        if ($distinctYears->isEmpty()) {
            $distinctYears = collect([1, 2, 3]);
        }

        $buttons = [];
        $yearRow = [];

        foreach ($distinctYears as $year) {
            $key = "year_{$year}";
            $yearLabel = __('bot.courses.' . $key, [], $lang);
            if ($yearLabel === 'bot.courses.' . $key) {
                $yearLabel = "Year {$year}";
            }

            $yearRow[] = [
                'text' => $yearLabel,
                'callback_data' => "{$prefix}_{$program->id}_{$year}_1",
            ];

            // 2 buttons per row
            if (count($yearRow) === 2) {
                $buttons[] = $yearRow;
                $yearRow = [];
            }
        }

        if (! empty($yearRow)) {
            $buttons[] = $yearRow;
        }

        // All courses button
        $buttons[] = [
            ['text' => __('bot.courses.year_all', [], $lang), 'callback_data' => "{$prefix}_{$program->id}_all_1"],
        ];

        $buttons[] = [
            ['text' => __('bot.courses.change_program', [], $lang), 'callback_data' => 'change_program'],
        ];

        $buttons[] = [
            ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => $backCallback],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of active courses for a programme, filtered by year and paginated.
     */
    public static function courses(Program $program, ?string $lang = 'en', $year = 'all', int $page = 1, int $perPage = 8): array
    {
        $lang = $lang ?: 'en';
        $query = $program->courses()->where('is_active', true)->orderBy('name');

        if ($year !== 'all' && is_numeric($year)) {
            $query->where('year', (int) $year);
        }

        $allCourses = $query->get();
        $total = $allCourses->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, $page), $totalPages);
        $coursesPage = $allCourses->forPage($currentPage, $perPage);

        $courseButtons = [];
        foreach ($coursesPage as $course) {
            
            $courseButtons[] = [
                ['text' => "📖 {$course->name}", 'callback_data' => "course_{$course->id}_{$program->id}_{$year}"],
            ];
        }

        if (empty($courseButtons)) {
            $courseButtons[] = [
                ['text' => __('bot.courses.no_courses', [], $lang), 'callback_data' => 'noop'],
            ];
        }

        // Pagination buttons row (e.g. ◀️ Prev | 1/2 | Next ▶️)
        if ($totalPages > 1) {
            $paginationRow = [];
            if ($currentPage > 1) {
                $paginationRow[] = [
                    'text' => __('bot.courses.prev_page', [], $lang),
                    'callback_data' => "course_year_{$program->id}_{$year}_" . ($currentPage - 1),
                ];
            }

            $paginationRow[] = [
                'text' => __('bot.courses.page_indicator', ['current' => $currentPage, 'total' => $totalPages], $lang),
                'callback_data' => 'noop',
            ];

            if ($currentPage < $totalPages) {
                $paginationRow[] = [
                    'text' => __('bot.courses.next_page', [], $lang),
                    'callback_data' => "course_year_{$program->id}_{$year}_" . ($currentPage + 1),
                ];
            }

            $courseButtons[] = $paginationRow;
        }

        $courseButtons[] = [
            ['text' => __('bot.courses.change_year', [], $lang), 'callback_data' => "prog_{$program->id}"],
        ];

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
    public static function submitCourses(Program $program, ?string $lang = 'en', $year = 'all', int $page = 1, int $perPage = 8): array
    {
        $lang = $lang ?: 'en';
        $query = $program->courses()->where('is_active', true)->orderBy('name');

        if ($year !== 'all' && is_numeric($year)) {
            $query->where('year', (int) $year);
        }

        $allCourses = $query->get();
        $total = $allCourses->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, $page), $totalPages);
        $coursesPage = $allCourses->forPage($currentPage, $perPage);

        $buttons = [];
        foreach ($coursesPage as $course) {
            $buttons[] = [
                ['text' => "📤 {$course->name}", 'callback_data' => "submit_course_{$course->id}_{$program->id}_{$year}"],
            ];
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => __('bot.courses.no_courses', [], $lang), 'callback_data' => 'noop'],
            ];
        }

        if ($totalPages > 1) {
            $paginationRow = [];
            if ($currentPage > 1) {
                $paginationRow[] = [
                    'text' => __('bot.courses.prev_page', [], $lang),
                    'callback_data' => "submit_year_{$program->id}_{$year}_" . ($currentPage - 1),
                ];
            }

            $paginationRow[] = [
                'text' => __('bot.courses.page_indicator', ['current' => $currentPage, 'total' => $totalPages], $lang),
                'callback_data' => 'noop',
            ];

            if ($currentPage < $totalPages) {
                $paginationRow[] = [
                    'text' => __('bot.courses.next_page', [], $lang),
                    'callback_data' => "submit_year_{$program->id}_{$year}_" . ($currentPage + 1),
                ];
            }

            $buttons[] = $paginationRow;
        }

        $buttons[] = [
            ['text' => __('bot.courses.change_year', [], $lang), 'callback_data' => "submit_prog_{$program->id}"],
        ];

        $buttons[] = [
            ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main_submit'],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of active resource types for submitting materials for a course.
     */
    public static function submitResourceTypes(Course $course, int $programId, $year = 'all', ?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $types = ResourceType::where('is_active', true)->orderBy('sort_order')->get();

        $buttons = [];
        $row = [];

        foreach ($types as $type) {
            $row[] = [
                'text' => "📁 {$type->display_name}",
                'callback_data' => "submit_type_{$course->id}_{$type->id}",
            ];

            if (count($row) === 2) {
                $buttons[] = $row;
                $row = [];
            }
        }

        if (! empty($row)) {
            $buttons[] = $row;
        }

        $buttons[] = [
            ['text' => __('bot.courses.back_courses', [], $lang), 'callback_data' => "submit_year_{$programId}_{$year}_1"],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of resource types for a course.
     */
    public static function resourceTypes(Collection $resourceTypes, int $courseId, int $programId, ?string $lang = 'en', $year = 'all'): array
    {
        $lang = $lang ?: 'en';
        $buttons = [];
        foreach ($resourceTypes as $type) {
            $buttons[] = [
                ['text' => "📖 {$type->display_name}", 'callback_data' => "resource_type_{$type->id}_{$courseId}_{$programId}_{$year}_1"],
            ];
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => __('bot.courses.no_types', [], $lang), 'callback_data' => 'noop'],
            ];
            $buttons[] = [
                ['text' => __('bot.courses.submit_for_course', [], $lang), 'callback_data' => "submit_res_{$courseId}"],
            ];
        }

        $buttons[] = [
            ['text' => __('bot.courses.back_courses', [], $lang), 'callback_data' => "course_year_{$programId}_{$year}_1"],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of available resources with pagination (5 to 10 buttons per page).
     */
    public static function resources(Collection $resources, int $courseId, int $programId, ?string $lang = 'en', int $resourceTypeId = 0, $year = 'all', int $page = 1, int $perPage = 6): array
    {
        $lang = $lang ?: 'en';
        $total = $resources->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, $page), $totalPages);
        $pageResources = $resources->forPage($currentPage, $perPage);

        $buttons = [];
        foreach ($pageResources as $resource) {
            $buttons[] = [
                ['text' => "📄 {$resource->title}", 'callback_data' => "send_resource_{$resource->id}"],
            ];
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => __('bot.courses.no_materials', [], $lang), 'callback_data' => 'noop'],
            ];
        }

        // Pagination buttons if resources exceed perPage (e.g. 5-10 buttons)
        if ($totalPages > 1) {
            $paginationRow = [];
            if ($currentPage > 1) {
                $paginationRow[] = [
                    'text' => __('bot.courses.prev_page', [], $lang),
                    'callback_data' => "res_page_{$resourceTypeId}_{$courseId}_{$programId}_{$year}_" . ($currentPage - 1),
                ];
            }

            $paginationRow[] = [
                'text' => __('bot.courses.page_indicator', ['current' => $currentPage, 'total' => $totalPages], $lang),
                'callback_data' => 'noop',
            ];

            if ($currentPage < $totalPages) {
                $paginationRow[] = [
                    'text' => __('bot.courses.next_page', [], $lang),
                    'callback_data' => "res_page_{$resourceTypeId}_{$courseId}_{$programId}_{$year}_" . ($currentPage + 1),
                ];
            }

            $buttons[] = $paginationRow;
        }

        // Button allowing students to contribute files (passing course and category)
        $submitData = $resourceTypeId ? "submit_res_{$courseId}_{$resourceTypeId}" : "submit_res_{$courseId}";
        $buttons[] = [
            ['text' => __('bot.courses.submit_for_course', [], $lang), 'callback_data' => $submitData],
        ];

        $buttons[] = [
            ['text' => __('bot.courses.back_types', [], $lang), 'callback_data' => "course_{$courseId}_{$programId}_{$year}"],
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

    /**
     * Simple Yes or No confirmation keyboard.
     */
    public static function simpleYesOrNo(?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';

        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.simple_buttons.no', [], $lang), 'callback_data' => 'title_reject'],
                    ['text' => __('bot.simple_buttons.yes', [], $lang), 'callback_data' => 'title_approved'],
                ],
            ],
        ];
    }

    /**
     * Inline keyboard for search results (courses and resources).
     */
    public static function searchResults($courses, $resources, ?string $lang = 'en', ?int $programId = 0, bool $showNavigation = true): array
    {
        $lang = $lang ?: 'en';
        $buttons = [];

        // Course buttons
        foreach ($courses as $course) {
            $progId = ($programId && $course->programs && $course->programs->contains('id', $programId))
                ? $programId
                : ($course->programs?->first()?->id ?? $programId);
            $buttons[] = [
                ['text' => "📖 {$course->name}", 'callback_data' => "course_{$course->id}_{$progId}_all"],
            ];
        }

        // Resource buttons
        foreach ($resources as $resource) {
            $courseLabel = $resource->course?->name ? " ({$resource->course->name})" : '';
            $filesCount = $resource->files ? $resource->files->count() : 0;
            $filesBadge = $filesCount > 1 ? " [{$filesCount} files]" : '';
            $buttons[] = [
                ['text' => "📄 {$resource->title}{$courseLabel}{$filesBadge}", 'callback_data' => "send_resource_{$resource->id}"],
            ];
        }

        // Navigation row: Search again + Back to main (only when requested, omitted in group searches)
        if ($showNavigation) {
            $buttons[] = [
                ['text' => __('bot.search.search_again', [], $lang), 'callback_data' => 'search_prompt'],
                ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main'],
            ];
        }

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Inline keyboard for search prompt (cancel or back).
     */
    public static function searchPrompt(?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';

        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.search.cancel', [], $lang), 'callback_data' => 'back_main'],
                ],
            ],
        ];
    }

    /**
     * Report reasons inline keyboard.
     */
    public static function reportReasons(int $resourceId, int $fileId = 0, ?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $reasons = [
            'corrupted' => __('bot.reports.reasons.corrupted', [], $lang),
            'wrong_course' => __('bot.reports.reasons.wrong_course', [], $lang),
            'poor_quality' => __('bot.reports.reasons.poor_quality', [], $lang),
            'copyright' => __('bot.reports.reasons.copyright', [], $lang),
            'other' => __('bot.reports.reasons.other', [], $lang),
        ];

        $buttons = [];
        foreach ($reasons as $key => $label) {
            $buttons[] = [
                ['text' => $label, 'callback_data' => "report_submit_{$resourceId}_{$fileId}_{$key}"],
            ];
        }

        $buttons[] = [
            ['text' => __('bot.reports.cancel', [], $lang), 'callback_data' => 'report_cancel'],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Contact & Report Hub inline keyboard.
     */
    public static function contactMenu(?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $channel = config('telegram.channel_username', 'PARSI_POLITO');

        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.contact.report_material_btn', [], $lang), 'callback_data' => 'search_prompt'],
                ],
                [
                    ['text' => __('bot.contact.send_message_btn', [], $lang), 'callback_data' => 'contact_prompt'],
                ],
                [
                    ['text' => __('bot.contact.channel_btn', [], $lang), 'url' => 'https://t.me/' . ltrim($channel, '@')],
                ],
                [
                    ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main_contact'],
                ],
            ],
        ];
    }
}
