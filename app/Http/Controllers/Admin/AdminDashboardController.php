<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Display the main admin dashboard with summaries, statistics, and graphs.
     */
    public function index()
    {
        $userStats = $this->getUserStats();
        $courseStats = $this->getCourseStats();
        $yearStats = $this->getYearStats();
        $resourceStats = $this->getResourceStats();
        $chartData = $this->getChartData($userStats, $courseStats, $yearStats, $resourceStats);

        return view('admin.dashboard', [
            'userStats' => $userStats,
            'courseStats' => $courseStats,
            'yearStats' => $yearStats,
            'resourceStats' => $resourceStats,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Aggregate user metrics and language/programme distribution.
     */
    protected function getUserStats(): array
    {
        $totalUsers = User::count();
        $newThisWeek = User::where('created_at', '>=', now()->subDays(7))->count();
        $blockedUsers = User::where('is_blocked', true)->count();
        $activeUsers = $totalUsers - $blockedUsers;

        $withProgram = User::whereNotNull('program_id')->count();

        // Language breakdown
        $languages = User::select('preferred_language', DB::raw('count(*) as count'))
            ->groupBy('preferred_language')
            ->pluck('count', 'preferred_language')
            ->toArray();

        // Top programmes by student count
        $topPrograms = Program::withCount('users')
            ->orderByDesc('users_count')
            ->take(5)
            ->get();

        return [
            'total' => $totalUsers,
            'new_this_week' => $newThisWeek,
            'active' => $activeUsers,
            'blocked' => $blockedUsers,
            'with_program' => $withProgram,
            'languages' => [
                'en' => $languages['en'] ?? 0,
                'it' => $languages['it'] ?? 0,
                'fa' => $languages['fa'] ?? 0,
                'other' => array_sum(array_diff_key($languages, ['en' => 0, 'it' => 0, 'fa' => 0])),
            ],
            'top_programs' => $topPrograms,
        ];
    }

    /**
     * Aggregate course and degree programme statistics.
     */
    protected function getCourseStats(): array
    {
        $totalCourses = Course::count();
        $activeCourses = Course::where('is_active', true)->count();
        $totalPrograms = Program::count();

        // Degree level distribution (BSc, MSc, PhD)
        $levelCounts = Program::select('degree_level', DB::raw('count(*) as count'))
            ->groupBy('degree_level')
            ->pluck('count', 'degree_level')
            ->toArray();

        // Top courses by material count
        $topCourses = Course::withCount('resources')
            ->with('programs')
            ->orderByDesc('resources_count')
            ->take(6)
            ->get();

        return [
            'total' => $totalCourses,
            'active' => $activeCourses,
            'total_programs' => $totalPrograms,
            'levels' => $levelCounts,
            'top_courses' => $topCourses,
        ];
    }

    /**
     * Aggregate academic years metrics.
     */
    protected function getYearStats(): array
    {
        $totalYears = AcademicYear::count();
        $currentYear = AcademicYear::where('is_current', true)->first();

        $yearsWithCount = AcademicYear::withCount('resources')
            ->orderByDesc('label')
            ->take(6)
            ->get();

        return [
            'total' => $totalYears,
            'current' => $currentYear,
            'history' => $yearsWithCount,
        ];
    }

    /**
     * Aggregate educational resources and file storage statistics.
     */
    protected function getResourceStats(): array
    {
        $totalResources = Resource::count();
        $approvedCount = Resource::where('status', 'approved')->count();
        $pendingCount = Resource::where('status', 'pending')->count();
        $withSolutions = Resource::where('has_solutions', true)->count();

        $totalFiles = ResourceFile::count();
        $totalBytes = (int) ResourceFile::sum('size_bytes');

        // Resources per type
        $types = ResourceType::withCount('resources')
            ->orderByDesc('resources_count')
            ->get();

        // Recent 6 uploaded resources
        $recentResources = Resource::with(['course', 'resourceType', 'academicYear'])
            ->latest()
            ->take(6)
            ->get();

        return [
            'total' => $totalResources,
            'approved' => $approvedCount,
            'pending' => $pendingCount,
            'with_solutions' => $withSolutions,
            'total_files' => $totalFiles,
            'storage_bytes' => $totalBytes,
            'storage_formatted' => $this->formatBytes($totalBytes),
            'types' => $types,
            'recent' => $recentResources,
        ];
    }

    /**
     * Prepare structured datasets for Chart.js interactive graphs.
     */
    protected function getChartData(array $userStats, array $courseStats, array $yearStats, array $resourceStats): array
    {
        // 1. Resources by Type Chart
        $typeLabels = [];
        $typeData = [];
        foreach ($resourceStats['types'] as $type) {
            $typeLabels[] = $type->display_name;
            $typeData[] = $type->resources_count;
        }

        // 2. Student Programme Distribution Chart
        $programLabels = [];
        $programData = [];
        foreach ($userStats['top_programs'] as $prog) {
            $programLabels[] = $prog->name.($prog->degree_level ? " ({$prog->degree_level})" : '');
            $programData[] = $prog->users_count;
        }

        // 3. Materials by Academic Year Chart
        $yearLabels = [];
        $yearData = [];
        foreach ($yearStats['history']->reverse() as $year) {
            $yearLabels[] = $year->label;
            $yearData[] = $year->resources_count;
        }

        // 4. Language Preferences
        $languageLabels = ['English (EN)', 'Italian (IT)', 'Persian (FA)'];
        $languageData = [
            $userStats['languages']['en'],
            $userStats['languages']['it'],
            $userStats['languages']['fa'],
        ];

        return [
            'resource_types' => [
                'labels' => $typeLabels,
                'data' => $typeData,
            ],
            'programs' => [
                'labels' => $programLabels,
                'data' => $programData,
            ],
            'academic_years' => [
                'labels' => $yearLabels,
                'data' => $yearData,
            ],
            'languages' => [
                'labels' => $languageLabels,
                'data' => $languageData,
            ],
        ];
    }

    /**
     * Helper to format raw bytes into human readable KB, MB, or GB.
     */
    protected function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
