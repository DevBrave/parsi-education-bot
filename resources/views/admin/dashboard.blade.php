@extends('admin.layout')

@section('title', 'Admin Dashboard Overview')

@section('content')
<style>
    .dash-container {
        display: flex;
        flex-direction: column;
        gap: 1.75rem;
    }

    /* Purple Welcome Banner */
    .dash-banner {
        margin-bottom: 0;
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
        color: #ffffff;
        border: none;
        padding: 2.25rem 2.5rem;
        position: relative;
        overflow: hidden;
        border-radius: var(--radius);
    }

    .dash-banner-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        z-index: 2;
    }

    .dash-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255, 255, 255, 0.12);
        padding: 0.3rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 500;
        margin-bottom: 0.75rem;
        backdrop-filter: blur(4px);
    }

    .dash-title {
        font-size: 1.85rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #ffffff;
    }

    .dash-logo-overlay {
        position: absolute;
        right: 2rem;
        top: 50%;
        transform: translateY(-50%);
        width: 190px;
        height: 190px;
        pointer-events: none;
        z-index: 1;
        opacity: 0.30;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.25s ease;
    }

    .dash-logo-overlay img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: invert(1);
        mix-blend-mode: screen;
    }

    /* KPI Summary Cards Grid */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
    }

    .kpi-card {
        margin-bottom: 0;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .kpi-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-main);
        margin-top: 0.35rem;
        line-height: 1.15;
    }

    /* 2-Column Desktop Grid for Charts and Tables */
    .grid-2col {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }

    .chart-wrapper {
        padding: 1.5rem;
        height: 300px;
        position: relative;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1100px) {
        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 900px) {
        .grid-2col {
            grid-template-columns: 1fr;
        }

        .dash-banner {
            padding: 1.75rem 2rem;
        }

        .dash-title {
            font-size: 1.6rem;
        }

        .dash-logo-overlay {
            width: 150px;
            height: 150px;
            right: 1rem;
            opacity: 0.24;
        }
    }

    @media (max-width: 600px) {
        .dash-container {
            gap: 1.25rem;
        }

        .dash-banner {
            padding: 1.5rem 1.25rem;
        }

        .dash-title {
            font-size: 1.35rem;
        }

        .dash-logo-overlay {
            width: 110px;
            height: 110px;
            right: -6px;
            opacity: 0.16;
        }

        .kpi-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .kpi-card {
            padding: 1.25rem;
        }

        .kpi-value {
            font-size: 1.75rem;
        }

        .chart-wrapper {
            padding: 1rem 0.75rem;
            height: 260px;
        }
    }
</style>

<div class="dash-container">

    <!-- Welcome & Quick Action Header -->
    <div class="card dash-banner">
        <div class="dash-banner-content">
            <div>
                <div class="dash-badge">
                    <span>⭐ Current Academic Year:</span>
                    <strong>{{ $yearStats['current'] ? $yearStats['current']->label : 'Not Assigned' }}</strong>
                </div>
                <h1 class="dash-title">
                    PARSI Dashboard
                </h1>
            </div>
        </div>

        <!-- PARSI Logo Overlay on the right (with blend and responsive scale) -->
        <div class="dash-logo-overlay">
            <img src="{{ asset('ParsiLogo.jpg') }}" alt="PARSI Logo">
        </div>
    </div>

    <!-- 4 KPI Summary Cards Grid -->
    <div class="kpi-grid">
        
        <!-- Total Students Card -->
        <div class="card kpi-card" style="border-left: 4px solid #4f46e5;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                        Registered Students
                    </span>
                    <h2 class="kpi-value">
                        {{ number_format($userStats['total']) }}
                    </h2>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    👥
                </div>
            </div>
            <div style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap;">
                <span class="badge badge-success">+{{ $userStats['new_this_week'] }} this week</span>
                <span>• {{ $userStats['with_program'] }} selected degree</span>
            </div>
        </div>

        <!-- Educational Resources Card -->
        <div class="card kpi-card" style="border-left: 4px solid #10b981;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                        Total Resources
                    </span>
                    <h2 class="kpi-value">
                        {{ number_format($resourceStats['total']) }}
                    </h2>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    📚
                </div>
            </div>
            <div style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap;">
                <span class="badge badge-success">{{ $resourceStats['approved'] }} active</span>
                <span class="badge badge-primary" style="background: #e0f2fe; color: #0369a1; font-weight: 600;">📥 {{ number_format($resourceStats['total_downloads']) }} downloads</span>
                <span>• 🔍 {{ number_format($resourceStats['total_inline_shares']) }} inline shares</span>
            </div>
        </div>

        <!-- University Courses Card -->
        <div class="card kpi-card" style="border-left: 4px solid #0284c7;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                        University Courses
                    </span>
                    <h2 class="kpi-value">
                        {{ number_format($courseStats['total']) }}
                    </h2>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #f0f9ff; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    📖
                </div>
            </div>
            <div style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap;">
                <span class="badge badge-primary">{{ $courseStats['total_programs'] }} Degree Programmes</span>
                <span>• {{ $courseStats['active'] }} active</span>
            </div>
        </div>

        <!-- Storage & Backups Card -->
        <div class="card kpi-card" style="border-left: 4px solid #8b5cf6;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);">
                        Archive Storage
                    </span>
                    <h2 class="kpi-value">
                        {{ $resourceStats['storage_formatted'] }}
                    </h2>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #f5f3ff; color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    💾
                </div>
            </div>
            <div style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap;">
                <span class="badge badge-secondary">{{ $resourceStats['total_files'] }} files</span>
                <span>• Synced with Telegram & Drive</span>
            </div>
        </div>

    </div>

    <!-- 2x2 Interactive Charts Grid -->
    <div class="grid-2col">

        <!-- Chart 1: Resource Categories -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <div>
                    <h2>🏷️ Materials by Resource Type</h2>
                    <p>Distribution across past exams, lecture notes, exercises, and summaries</p>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartResourceTypes"></canvas>
            </div>
        </div>

        <!-- Chart 2: Top Student Degree Programmes -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <div>
                    <h2>🎓 Students by Degree Programme</h2>
                    <p>Enrolled students categorized by their chosen study track</p>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartPrograms"></canvas>
            </div>
        </div>

        <!-- Chart 3: Materials per Academic Year -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <div>
                    <h2>📅 Materials by Academic Year</h2>
                    <p>Distribution of archive items across calendar sessions</p>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartYears"></canvas>
            </div>
        </div>

        <!-- Chart 4: Language Preferences -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <div>
                    <h2>🌐 Student Language Distribution</h2>
                    <p>Preferred communication language inside Telegram</p>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartLanguages"></canvas>
            </div>
        </div>

    </div>

    <!-- Data Tables Grid: Recent Uploads & Top Courses -->
    <div class="grid-2col">

        <!-- Table 1: Recently Uploaded Materials -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <div>
                    <h2>⚡ Recently Uploaded Materials</h2>
                    <p>Latest materials added to the repository</p>
                </div>
                <a href="{{ route('admin.resources.create') }}" class="btn btn-secondary btn-sm">Upload More</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Resource Title</th>
                            <th>Course</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th style="text-align: right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resourceStats['recent'] as $res)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.resources.edit', $res) }}" style="text-decoration: none; color: inherit; font-weight: 600;" class="hover-underline">
                                        {{ $res->title }}
                                    </a>
                                    @if($res->has_solutions)
                                        <span title="Includes Solutions" style="font-size: 0.75rem;">💡</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size: 0.8125rem;">{{ $res->course?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $res->resourceType?->display_name ?? 'General' }}</span>
                                </td>
                                <td>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ $res->created_at?->diffForHumans() }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    @if($res->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif($res->status === 'pending')
                                        <span class="badge badge-secondary" style="background: #fef3c7; color: #92400e;">Pending</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($res->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    No materials uploaded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Table 2: Top Courses by Resource Count -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <div>
                    <h2>🏆 Top Courses by Resources</h2>
                    <p>Courses with the largest collection of study materials</p>
                </div>
                <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary btn-sm">All Courses</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Degree Track</th>
                            <th style="text-align: right;">Resources</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courseStats['top_courses'] as $course)
                            <tr>
                                <td>
                                    <span class="badge badge-secondary" style="font-family: monospace;">{{ $course->course_code }}</span>
                                </td>
                                <td>
                                    <strong>{{ $course->name }}</strong>
                                </td>
                                <td>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        @forelse($course->programs->take(2) as $p)
                                            <span class="badge badge-primary" style="font-size: 0.7rem;">{{ $p->name }}</span>
                                        @empty
                                            <span style="color: var(--text-muted); font-size: 0.75rem;">—</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <span class="badge badge-success" style="font-weight: 700;">
                                        {{ $course->resources_count }} files
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    No courses with resources found yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Shared modern typography and styling options
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#64748b';

        const chartColors = [
            '#4f46e5', '#10b981', '#f59e0b', '#0284c7', 
            '#ec4899', '#8b5cf6', '#14b8a6', '#f43f5e'
        ];

        // 1. Chart: Resources by Type (Doughnut)
        const typeCtx = document.getElementById('chartResourceTypes');
        if (typeCtx) {
            new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($chartData['resource_types']['labels']),
                    datasets: [{
                        data: @json($chartData['resource_types']['data']),
                        backgroundColor: chartColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } }
                    },
                    cutout: '68%'
                }
            });
        }

        // 2. Chart: Students by Programme (Horizontal Bar)
        const progCtx = document.getElementById('chartPrograms');
        if (progCtx) {
            new Chart(progCtx, {
                type: 'bar',
                data: {
                    labels: @json($chartData['programs']['labels']),
                    datasets: [{
                        label: 'Students',
                        data: @json($chartData['programs']['data']),
                        backgroundColor: '#4f46e5',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }

        // 3. Chart: Materials per Academic Year (Bar)
        const yearCtx = document.getElementById('chartYears');
        if (yearCtx) {
            new Chart(yearCtx, {
                type: 'bar',
                data: {
                    labels: @json($chartData['academic_years']['labels']),
                    datasets: [{
                        label: 'Materials',
                        data: @json($chartData['academic_years']['data']),
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // 4. Chart: Student Language Preferences (Doughnut)
        const langCtx = document.getElementById('chartLanguages');
        if (langCtx) {
            new Chart(langCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($chartData['languages']['labels']),
                    datasets: [{
                        data: @json($chartData['languages']['data']),
                        backgroundColor: ['#0284c7', '#10b981', '#f59e0b'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } }
                    },
                    cutout: '68%'
                }
            });
        }
    });
</script>
@endpush
