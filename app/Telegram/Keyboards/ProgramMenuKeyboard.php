<?php

namespace App\Telegram\Keyboards;

use App\Models\Course;
use App\Models\Program;
use App\Models\ResourceType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProgramMenuKeyboard
{
    /**
     * Educational Resources Hub inline keyboard.
     */
    public static function educationHub(?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';

        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.education_hub.browse_btn', [], $lang), 'callback_data' => 'edu_browse'],
                ],
                [
                    ['text' => __('bot.education_hub.search_btn', [], $lang), 'callback_data' => 'search_prompt'],
                ],
                [
                    ['text' => __('bot.education_hub.submit_btn', [], $lang), 'callback_data' => 'edu_submit_start'],
                ],
                [
                    ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main_educational_files'],
                ],
            ],
        ];
    }

    /**
     * Build inline keyboard of active programmes from database.
     */
    public static function programmes(?string $lang = 'en', string $backCallback = 'back_edu_hub', bool $forSubmission = false): array
    {
        $lang = $lang ?: 'en';
        $prefix = $forSubmission ? 'submit_prog_' : 'prog_';
        $programs = Cache::remember('catalog:programs:active', now()->addDay(), function () {
            return Program::where('is_active', true)->orderBy('name')->get();
        });

        if (! is_iterable($programs) || $programs instanceof \__PHP_Incomplete_Class) {
            Cache::forget('catalog:programs:active');
            $programs = Program::where('is_active', true)->orderBy('name')->get();
        }

        $rows = [];
        foreach ($programs as $program) {
            $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
            $rows[] = [
                ['text' => "🎓 {$program->name}{$levelTag}", 'callback_data' => "{$prefix}{$program->id}"],
            ];
        }

        $backText = $backCallback === 'back_edu_hub'
            ? __('bot.courses.back_edu', [], $lang)
            : __('bot.courses.back_main', [], $lang);

        $rows[] = [
            ['text' => $backText, 'callback_data' => $backCallback],
        ];

        return ['inline_keyboard' => $rows];
    }


    /**
     * Build inline keyboard of active degree levels (BSc, MSc, etc.).
     */
    public static function degrees(string $lang = 'en', ?string $backCallback = null): array
    {
        $lang = $lang ?: 'en';
        // 1. Pluck returns a clean collection of strings: ['BSc', 'MSc']
        $degrees = Cache::remember('catalog:degrees:unique_levels', now()->addDay(), function () {
            return Program::where('is_active', true)
                ->whereNotNull('degree_level')
                ->distinct()
                ->orderBy('degree_level')
                ->pluck('degree_level');
        });

        if (! is_iterable($degrees) || $degrees instanceof \__PHP_Incomplete_Class) {
            Cache::forget('catalog:degrees:unique_levels');
            $degrees = Program::where('is_active', true)
                ->whereNotNull('degree_level')
                ->distinct()
                ->orderBy('degree_level')
                ->pluck('degree_level');
        }

        $buttons = [];
        foreach ($degrees as $degree) {
            $degreeStr = (string) $degree;
            $buttons[] = [
                [
                    'text' => "🎓 {$degreeStr}",
                    'callback_data' => "select_degree_{$degreeStr}",
                ],
            ];
        }

        if ($backCallback) {
            $backText = $backCallback === 'back_edu_hub'
                ? __('bot.courses.back_edu', [], $lang)
                : __('bot.courses.back_main', [], $lang);

            $buttons[] = [
                [
                    'text' => $backText,
                    'callback_data' => $backCallback,
                ],
            ];
        }

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of programs belonging to a specific degree level.
     */
    public static function programsByDegree(string $degreeLevel, string $lang = 'en', string $backCallback = 'back_to_degrees'): array
    {
        $lang = $lang ?: 'en';
        $cacheKey = "catalog:programs:degree_{$degreeLevel}";

        $programs = Cache::remember($cacheKey, now()->addDay(), function () use ($degreeLevel) {
            return Program::where('is_active', true)
                ->where('degree_level', $degreeLevel)
                ->orderBy('name')
                ->get();
        });

        if (! is_iterable($programs) || $programs instanceof \__PHP_Incomplete_Class) {
            Cache::forget($cacheKey);
            $programs = Program::where('is_active', true)
                ->where('degree_level', $degreeLevel)
                ->orderBy('name')
                ->get();
        }

        $buttons = [];
        foreach ($programs as $program) {
            $buttons[] = [
                [
                    'text' => "📘 {$program->name}",
                    'callback_data' => "select_program_{$program->id}",
                ],
            ];
        }

        $backText = __('bot.onboarding.back_degrees', [], $lang) ?: '⬅️ Back to Degree Levels';

        $buttons[] = [
            [
                'text' => $backText,
                'callback_data' => $backCallback,
            ],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of active specialization tracks for a programme.
     */
    public static function tracks(
        Program $program,
        ?string $lang = 'en',
        string $backCallback = 'change_program',
        bool $forSubmission = false,
        ?string $customPrefix = null
    ): array {
        $lang = $lang ?: 'en';
        if ($customPrefix !== null) {
            $prefix = $customPrefix;
        } else {
            $prefix = $forSubmission ? 'submit_track_' : 'track_';
        }

        $cacheKey = "catalog:tracks:program_{$program->id}";

        $tracks = Cache::remember($cacheKey, now()->addDay(), function () use ($program) {
            return $program->tracks()->where('is_active', true)->orderBy('name')->get();
        });

        if (! is_iterable($tracks) || $tracks instanceof \__PHP_Incomplete_Class) {
            Cache::forget($cacheKey);
            $tracks = $program->tracks()->where('is_active', true)->orderBy('name')->get();
        }

        $buttons = [];
        foreach ($tracks as $track) {
            $codeTag = $track->code ? " [{$track->code}]" : '';
            $buttons[] = [
                ['text' => "🎯 {$track->name}{$codeTag}", 'callback_data' => "{$prefix}{$track->id}"],
            ];
        }

        $backText = str_starts_with($backCallback, 'select_degree_')
            ? (__('bot.tracks.back_to_programs', [], $lang) ?: '⬅️ Back to Programs')
            : __('bot.courses.change_program', [], $lang);

        $buttons[] = [
            ['text' => $backText, 'callback_data' => $backCallback],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Get active academic years for a program (cached 24h as scalar array).
     */
    public static function getProgramYears(Program $program): array
    {
        $defaultYears = $program->defaultYears();
        $cacheKey = "catalog:years:program_{$program->id}";
        $distinctYears = Cache::remember($cacheKey, now()->addDay(), function () use ($program, $defaultYears) {
            $years = $program->courses()
                ->where('is_active', true)
                ->whereNotNull('year')
                ->distinct()
                ->pluck('year')
                ->sort()
                ->values()
                ->all();

            return empty($years) ? $defaultYears : $years;
        });

        if (! is_iterable($distinctYears) || $distinctYears instanceof \__PHP_Incomplete_Class) {
            Cache::forget($cacheKey);
            $distinctYears = $defaultYears;
        }

        return array_values((array) $distinctYears);
    }

    /**
     * Build inline keyboard of academic years for a programme.
     */
    public static function years(Program $program, ?string $lang = 'en', bool $forSubmission = false, ?string $backCallback = null): array
    {
        $lang = $lang ?: 'en';
        $prefix = $forSubmission ? 'submit_year' : 'course_year';
        $backCallback = $backCallback ?? 'back_edu_hub';

        $distinctYears = self::getProgramYears($program);

        $buttons = [];
        $yearRow = [];

        foreach ($distinctYears as $year) {
            if (! is_numeric($year)) {
                continue;
            }

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

        if ($program->tracks()->where('is_active', true)->exists()) {
            $trackCallback = $forSubmission ? "submit_change_track_{$program->id}" : "change_track_{$program->id}";
            $buttons[] = [
                ['text' => __('bot.tracks.change_track', [], $lang), 'callback_data' => $trackCallback],
            ];
        }

        $buttons[] = [
            ['text' => __('bot.courses.change_program', [], $lang), 'callback_data' => 'change_program'],
        ];

        $backText = $backCallback === 'back_edu_hub'
            ? __('bot.courses.back_edu', [], $lang)
            : __('bot.courses.back_main', [], $lang);

        $buttons[] = [
            ['text' => $backText, 'callback_data' => $backCallback],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Build inline keyboard of active courses for a programme, filtered by year and paginated.
     */
    public static function courses(Program $program, ?string $lang = 'en', $year = 'all', int $page = 1, int $perPage = 8, ?int $trackId = null): array
    {
        $lang = $lang ?: 'en';
        // Cache all courses for this program, track, and year for 24 hours as scalar array
        $trackSuffix = $trackId ? ":track_{$trackId}" : ":track_all";
        $cacheKey = "catalog:courses:program_{$program->id}{$trackSuffix}:year_{$year}";
        $allCourses = Cache::remember($cacheKey, now()->addDay(), function () use ($program, $year, $trackId) {
            $query = $program->courses()->where('is_active', true)->orderBy('name');

            if ($trackId) {
                $query->where(function ($q) use ($trackId) {
                    $q->whereDoesntHave('tracks')
                        ->orWhereHas('tracks', fn($t) => $t->where('tracks.id', $trackId));
                });
            }

            if ($year !== 'all' && is_numeric($year)) {
                $query->where('year', (int) $year);
            }

            return $query->select(['courses.id', 'courses.name'])->get()->toArray();
        });

        if (! is_array($allCourses)) {
            Cache::forget($cacheKey);
            $query = $program->courses()->where('is_active', true)->orderBy('name');
            if ($trackId) {
                $query->where(function ($q) use ($trackId) {
                    $q->whereDoesntHave('tracks')
                        ->orWhereHas('tracks', fn($t) => $t->where('tracks.id', $trackId));
                });
            }
            if ($year !== 'all' && is_numeric($year)) {
                $query->where('year', (int) $year);
            }
            $allCourses = $query->select(['courses.id', 'courses.name'])->get()->toArray();
        }

        $total = count($allCourses);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, $page), $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $coursesPage = array_slice($allCourses, $offset, $perPage);

        $courseButtons = [];
        foreach ($coursesPage as $course) {
            $courseId = is_array($course) ? $course['id'] : $course->id;
            $courseName = is_array($course) ? $course['name'] : $course->name;
            $courseButtons[] = [
                ['text' => "📖 {$courseName}", 'callback_data' => "course_{$courseId}_{$program->id}_{$year}"],
            ];
        }

        if (empty($courseButtons)) {
            $courseButtons[] = [
                ['text' => __('bot.courses.no_courses', [], $lang), 'callback_data' => 'noop'],
            ];
        }

        // Pagination buttons row:
        // - When browsing a specific year: [◀️ Prev] [2nd Year] [Next ▶️] switches between years
        // - When browsing all courses: [◀️ Prev] [Page 1/3] [Next ▶️] paginates pages
        if ($year !== 'all' && is_numeric($year)) {
            $currentYear = (int) $year;
            $availableYears = self::getProgramYears($program);
            $currentIndex = array_search($currentYear, $availableYears);

            if ($currentIndex !== false) {
                $prevYear = ($currentIndex > 0) ? $availableYears[$currentIndex - 1] : null;
                $nextYear = ($currentIndex < count($availableYears) - 1) ? $availableYears[$currentIndex + 1] : null;

                $paginationRow = [];
                if ($prevYear !== null) {
                    $paginationRow[] = [
                        'text' => __('bot.courses.prev_page', [], $lang),
                        'callback_data' => "course_year_{$program->id}_{$prevYear}_1",
                    ];
                }

                $yearLabel = __('bot.courses.year_' . $currentYear, [], $lang);
                if ($yearLabel === 'bot.courses.year_' . $currentYear) {
                    $yearLabel = "Year {$currentYear}";
                }

                $paginationRow[] = [
                    'text' => $yearLabel,
                    'callback_data' => 'noop',
                ];

                if ($nextYear !== null) {
                    $paginationRow[] = [
                        'text' => __('bot.courses.next_page', [], $lang),
                        'callback_data' => "course_year_{$program->id}_{$nextYear}_1",
                    ];
                }

                $courseButtons[] = $paginationRow;
            }
        } elseif ($totalPages > 1) {
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
            ['text' => __('bot.courses.change_year', [], $lang), 'callback_data' => "prog_years_{$program->id}"],
        ];

        if ($program->tracks()->where('is_active', true)->exists()) {
            $courseButtons[] = [
                ['text' => __('bot.tracks.change_track', [], $lang), 'callback_data' => "change_track_{$program->id}"],
            ];
        }

        $courseButtons[] = [
            ['text' => __('bot.courses.change_program', [], $lang), 'callback_data' => 'change_program'],
        ];

        $courseButtons[] = [
            ['text' => __('bot.courses.back_edu', [], $lang), 'callback_data' => 'back_edu_hub'],
        ];

        return ['inline_keyboard' => $courseButtons];
    }

    /**
     * Build inline keyboard of active courses for submitting materials.
     */
    public static function submitCourses(Program $program, ?string $lang = 'en', $year = 'all', int $page = 1, int $perPage = 8, ?int $trackId = null): array
    {
        $lang = $lang ?: 'en';
        // Cache all courses for this program, track, and year for 24 hours
        $trackSuffix = $trackId ? ":track_{$trackId}" : ":track_all";
        $cacheKey = "catalog:courses:program_{$program->id}{$trackSuffix}:year_{$year}";
        $allCourses = Cache::remember($cacheKey, now()->addDay(), function () use ($program, $year, $trackId) {
            $query = $program->courses()->where('is_active', true)->orderBy('name');

            if ($trackId) {
                $query->where(function ($q) use ($trackId) {
                    $q->whereDoesntHave('tracks')
                        ->orWhereHas('tracks', fn($t) => $t->where('tracks.id', $trackId));
                });
            }

            if ($year !== 'all' && is_numeric($year)) {
                $query->where('year', (int) $year);
            }

            return $query->select(['courses.id', 'courses.name'])->get()->toArray();
        });

        if (! is_array($allCourses)) {
            Cache::forget($cacheKey);
            $query = $program->courses()->where('is_active', true)->orderBy('name');
            if ($trackId) {
                $query->where(function ($q) use ($trackId) {
                    $q->whereDoesntHave('tracks')
                        ->orWhereHas('tracks', fn($t) => $t->where('tracks.id', $trackId));
                });
            }
            if ($year !== 'all' && is_numeric($year)) {
                $query->where('year', (int) $year);
            }
            $allCourses = $query->select(['courses.id', 'courses.name'])->get()->toArray();
        }

        $total = count($allCourses);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, $page), $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $coursesPage = array_slice($allCourses, $offset, $perPage);

        $buttons = [];
        foreach ($coursesPage as $course) {
            $courseId = is_array($course) ? $course['id'] : $course->id;
            $courseName = is_array($course) ? $course['name'] : $course->name;
            $buttons[] = [
                ['text' => "📤 {$courseName}", 'callback_data' => "submit_course_{$courseId}_{$program->id}_{$year}"],
            ];
        }

        if (empty($buttons)) {
            $buttons[] = [
                ['text' => __('bot.courses.no_courses', [], $lang), 'callback_data' => 'noop'],
            ];
        }

        // Pagination buttons row:
        // - When browsing a specific year: [◀️ Prev] [2nd Year] [Next ▶️] switches between years
        // - When browsing all courses: [◀️ Prev] [Page 1/3] [Next ▶️] paginates pages
        if ($year !== 'all' && is_numeric($year)) {
            $currentYear = (int) $year;
            $availableYears = self::getProgramYears($program);
            $currentIndex = array_search($currentYear, $availableYears);

            if ($currentIndex !== false) {
                $prevYear = ($currentIndex > 0) ? $availableYears[$currentIndex - 1] : null;
                $nextYear = ($currentIndex < count($availableYears) - 1) ? $availableYears[$currentIndex + 1] : null;

                $paginationRow = [];
                if ($prevYear !== null) {
                    $paginationRow[] = [
                        'text' => __('bot.courses.prev_page', [], $lang),
                        'callback_data' => "submit_year_{$program->id}_{$prevYear}_1",
                    ];
                }

                $yearLabel = __('bot.courses.year_' . $currentYear, [], $lang);
                if ($yearLabel === 'bot.courses.year_' . $currentYear) {
                    $yearLabel = "Year {$currentYear}";
                }

                $paginationRow[] = [
                    'text' => $yearLabel,
                    'callback_data' => 'noop',
                ];

                if ($nextYear !== null) {
                    $paginationRow[] = [
                        'text' => __('bot.courses.next_page', [], $lang),
                        'callback_data' => "submit_year_{$program->id}_{$nextYear}_1",
                    ];
                }

                $buttons[] = $paginationRow;
            }
        } elseif ($totalPages > 1) {
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
            ['text' => __('bot.courses.change_year', [], $lang), 'callback_data' => "submit_prog_years_{$program->id}"],
        ];

        if ($program->tracks()->where('is_active', true)->exists()) {
            $buttons[] = [
                ['text' => __('bot.tracks.change_track', [], $lang), 'callback_data' => "submit_change_track_{$program->id}"],
            ];
        }

        $buttons[] = [
            ['text' => __('bot.courses.back_edu', [], $lang), 'callback_data' => 'back_edu_hub'],
        ];

        return ['inline_keyboard' => $buttons];
    }

    /**
     * Get active resource types safely from cache as pure primitive arrays.
     */
    public static function getActiveResourceTypes(): array
    {
        $types = Cache::remember('catalog:resource_types:active_array', now()->addDay(), function () {
            return ResourceType::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'display_name', 'code', 'sort_order'])
                ->toArray();
        });

        if (! is_array($types)) {
            Cache::forget('catalog:resource_types:active_array');

            return ResourceType::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'display_name', 'code', 'sort_order'])
                ->toArray();
        }

        return $types;
    }

    /**
     * Build inline keyboard of active resource types for submitting materials for a course.
     */
    public static function submitResourceTypes(Course $course, int $programId, $year = 'all', ?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $types = self::getActiveResourceTypes();

        $buttons = [];
        $row = [];

        foreach ($types as $type) {
            $id = is_array($type) ? ($type['id'] ?? 0) : ($type->id ?? 0);
            $name = is_array($type) ? ($type['display_name'] ?? '') : ($type->display_name ?? '');

            $row[] = [
                'text' => "📁 {$name}",
                'callback_data' => "submit_type_{$course->id}_{$id}",
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
    public static function resourceTypes(iterable $resourceTypes, int $courseId, int $programId, ?string $lang = 'en', $year = 'all'): array
    {
        $lang = $lang ?: 'en';
        $buttons = [];
        foreach ($resourceTypes as $type) {
            $id = is_array($type) ? ($type['id'] ?? 0) : ($type->id ?? 0);
            $name = is_array($type) ? ($type['display_name'] ?? '') : ($type->display_name ?? '');

            $buttons[] = [
                ['text' => "📖 {$name}", 'callback_data' => "resource_type_{$id}_{$courseId}_{$programId}_{$year}_1"],
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
            // ['text' => __('bot.reports.button', [], $lang), 'callback_data' => "report_prompt_{$courseId}"], // Report the course

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
                ['text' => __('bot.courses.back_edu', [], $lang), 'callback_data' => 'back_edu_hub'],
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
                    ['text' => __('bot.search.cancel', [], $lang), 'callback_data' => 'cancel_search'],
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

    /**
     * Report Menu inline keyboard.
     */
    public static function reportMenu(?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';

        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.report.report_material_btn', [], $lang), 'callback_data' => 'search_prompt'],
                ],
                [
                    ['text' => __('bot.report.send_report_btn', [], $lang), 'callback_data' => 'contact_prompt'],
                ],
                [
                    ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main_report'],
                ],
            ],
        ];
    }

    /**
     * PARSI Educational Guides & Tutorials inline keyboard.
     */
    public static function tutorialsMenu(?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $channel = ltrim(config('telegram.channel_username', 'PARSI_POLITO'), '@');

        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.tutorials.edisu_btn', [], $lang), 'url' => "https://t.me/{$channel}"],
                ],
                [
                    ['text' => __('bot.tutorials.enrollment_btn', [], $lang), 'url' => "https://t.me/{$channel}"],
                ],
                [
                    ['text' => __('bot.tutorials.didattica_btn', [], $lang), 'url' => "https://t.me/{$channel}"],
                ],
                [
                    ['text' => __('bot.tutorials.permesso_btn', [], $lang), 'url' => "https://t.me/{$channel}"],
                ],
                [
                    ['text' => __('bot.tutorials.channel_guides_btn', [], $lang), 'url' => "https://t.me/{$channel}"],
                ],
                [
                    ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main_tutorials'],
                ],
            ],
        ];
    }

    /**
     * About Us inline keyboard.
     */
    public static function aboutUsMenu(?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';
        $channel = config('telegram.channel_username', 'PARSI_POLITO');
        $instagram_page = config('telegram.instagram_link', 'https://www.instagram.com/parsi___polito/');

        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.about.members_btn', [], $lang), 'callback_data' => 'about_members'],
                ],
                [
                    ['text' => __('bot.about.channel_btn', [], $lang), 'url' => 'https://t.me/' . ltrim($channel, '@')],
                ],
                [
                    ['text' => __('bot.about.insta_btn', [], $lang), 'url' => str_starts_with($instagram_page, 'http') ? $instagram_page : 'https://www.instagram.com/' . ltrim($instagram_page, '@/ ')],
                ],
                [
                    ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main_about'],
                ],
            ],
        ];
    }

    /**
     * About Us Members inline keyboard.
     */
    public static function aboutMembersMenu(?string $lang = 'en'): array
    {
        $lang = $lang ?: 'en';

        return [
            'inline_keyboard' => [
                [
                    ['text' => __('bot.about.back_about', [], $lang), 'callback_data' => 'back_about'],
                ],
                [
                    ['text' => __('bot.courses.back_main', [], $lang), 'callback_data' => 'back_main_about'],
                ],
            ],
        ];
    }
}
