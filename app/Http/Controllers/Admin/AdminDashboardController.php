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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Display the main admin dashboard with summaries, statistics, and graphs.
     * Uses Split Caching: heavy aggregates are cached for 10 minutes,
     * while operational metrics (pending count, recent uploads, current year) are queried live.
     */
    public function index(Request $request)
    {
        if ($request->boolean('refresh')) {
            Cache::forget('admin:dashboard:heavy_metrics');
        }

        $cachedMetrics = Cache::remember('admin:dashboard:heavy_metrics', now()->addMinutes(10), function () {
            $userStats = $this->getUserStats();
            $courseStats = $this->getCourseStats();
            $yearStats = $this->getYearStats();
            $resourceStats = $this->getHeavyResourceStats();
            $chartData = $this->getChartData($userStats['languages']);

            return [
                'userStats' => $userStats,
                'courseStats' => $courseStats,
                'yearStats' => $yearStats,
                'resourceStats' => $resourceStats,
                'chartData' => $chartData,
            ];
        });

        // Extract cached scalar metrics
        $userStats = $cachedMetrics['userStats'];
        $courseStats = $cachedMetrics['courseStats'];
        $yearStats = $cachedMetrics['yearStats'];
        $resourceStats = $cachedMetrics['resourceStats'];
        $chartData = $cachedMetrics['chartData'];

        // Inject live operational metrics (always fresh & live Eloquent models)
        $resourceStats['pending'] = Resource::where('status', 'pending')->count();
        $resourceStats['approved'] = Resource::where('status', 'approved')->count();
        $resourceStats['recent'] = Resource::with(['course', 'resourceType', 'academicYear'])
            ->latest()
            ->take(6)
            ->get();

        $yearStats['current'] = AcademicYear::where('is_current', true)->first();

        // Live top courses table (always live Eloquent models with relations, never cached as objects)
        $courseStats['top_courses'] = Course::withCount('resources')
        ->with('programs')
        ->orderByDesc('resources_count')
            ->take(6)
            ->get();

        return view('admin.dashboard', [
            'userStats' => $userStats,
            'courseStats' => $courseStats,
            'yearStats' => $yearStats,
            'resourceStats' => $resourceStats,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Aggregate user metrics and language/programme distribution (pure scalar values).
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

        return [
            'total' => $totalUsers,
            'new_this_week' => $newThisWeek,
            'active' => $activeUsers,
            'blocked' => $blockedUsers,
            'with_program' => $withProgram,
            'languages' => [
                'en' => (int) ($languages['en'] ?? 0),
                'it' => (int) ($languages['it'] ?? 0),
                'fa' => (int) ($languages['fa'] ?? 0),
                'other' => (int) array_sum(array_diff_key($languages, ['en' => 0, 'it' => 0, 'fa' => 0])),
            ],
        ];
    }

    /**
     * Aggregate course and degree programme statistics (pure scalar values).
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

        return [
            'total' => $totalCourses,
            'active' => $activeCourses,
            'total_programs' => $totalPrograms,
            'levels' => $levelCounts,
        ];
    }

    /**
     * Aggregate academic years metrics (pure scalar values).
     */
    protected function getYearStats(): array
    {
        return [
            'total' => AcademicYear::count(),
        ];
    }

    /**
     * Aggregate educational resources and file storage statistics (pure scalar values).
     */
    protected function getHeavyResourceStats(): array
    {
        $totalResources = Resource::count();
        $withSolutions = Resource::where('has_solutions', true)->count();

        $totalDownloads = (int) Resource::sum('downloads_count');
        $totalInlineShares = (int) Resource::sum('inline_shares_count');

        $totalFiles = ResourceFile::count();
        $totalBytes = (int) ResourceFile::sum('size_bytes');

        return [
            'total' => $totalResources,
            'with_solutions' => $withSolutions,
            'total_downloads' => $totalDownloads,
            'total_inline_shares' => $totalInlineShares,
            'total_files' => $totalFiles,
            'storage_bytes' => $totalBytes,
            'storage_formatted' => $this->formatBytes($totalBytes),
        ];
    }

    /**
     * Prepare structured datasets for Chart.js interactive graphs (pure primitive arrays).
     */
    protected function getChartData(array $languages): array
    {
        // 1. Resources by Type Chart
        $types = ResourceType::withCount('resources')
            ->orderByDesc('resources_count')
            ->get();

        $typeLabels = $types->pluck('display_name')->toArray();
        $typeData = $types->pluck('resources_count')->map(fn($v) => (int) $v)->toArray();

        // 2. Student Programme Distribution Chart
        $programs = Program::withCount('users')
            ->orderByDesc('users_count')
            ->take(5)
            ->get();

        $programLabels = [];
        $programData = [];
        foreach ($programs as $prog) {
            $programLabels[] = $prog->name . ($prog->degree_level ? " ({$prog->degree_level})" : '');
            $programData[] = (int) $prog->users_count;
        }

        // 3. Materials by Academic Year Chart
        $years = AcademicYear::withCount('resources')
            ->orderByDesc('label')
            ->take(6)
            ->get()
            ->reverse();

        $yearLabels = $years->pluck('label')->toArray();
        $yearData = $years->pluck('resources_count')->map(fn($v) => (int) $v)->toArray();

        // 4. Language Preferences
        $languageLabels = ['English (EN)', 'Italian (IT)', 'Persian (FA)'];
        $languageData = [
            (int) ($languages['en'] ?? 0),
            (int) ($languages['it'] ?? 0),
            (int) ($languages['fa'] ?? 0),
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
