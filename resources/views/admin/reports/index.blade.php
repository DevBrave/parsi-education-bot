@extends('admin.layout')

@section('title', 'Material Issue Reports')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- Top Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">⚠️ Material Issue Reports</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Review and resolve reports submitted by students regarding faulty, corrupt, or miscategorized materials</p>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">Total Reports</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);">{{ $stats['total'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #ef4444;">
            <span style="font-size: 0.8125rem; color: #dc2626; font-weight: 600;">⚠️ Pending Review</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: #b91c1c;">{{ $stats['pending'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #10b981;">
            <span style="font-size: 0.8125rem; color: #059669; font-weight: 600;">✅ Resolved</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: #047857;">{{ $stats['resolved'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #64748b;">
            <span style="font-size: 0.8125rem; color: #475569; font-weight: 600;">🚫 Dismissed</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: #334155;">{{ $stats['dismissed'] }}</span>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card" style="padding: 1.25rem;">
        <form method="GET" action="{{ route('admin.reports.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: flex-end;">
            
            <div class="form-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" placeholder="Material title, course, reporter, notes..." value="{{ request('search') }}">
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ Pending Review</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>✅ Resolved</option>
                    <option value="dismissed" {{ request('status') === 'dismissed' ? 'selected' : '' }}>🚫 Dismissed</option>
                </select>
            </div>

            <div class="form-group">
                <label for="reason">Report Reason</label>
                <select id="reason" name="reason">
                    <option value="">All Reasons</option>
                    <option value="corrupted" {{ request('reason') === 'corrupted' ? 'selected' : '' }}>❌ Corrupted / Unreadable</option>
                    <option value="wrong_course" {{ request('reason') === 'wrong_course' ? 'selected' : '' }}>⚠️ Wrong Course</option>
                    <option value="poor_quality" {{ request('reason') === 'poor_quality' ? 'selected' : '' }}>📉 Poor Quality</option>
                    <option value="copyright" {{ request('reason') === 'copyright' ? 'selected' : '' }}>⚖️ Copyright Concern</option>
                    <option value="other" {{ request('reason') === 'other' ? 'selected' : '' }}>📝 Other</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.65rem 1rem;">
                    🔍 Filter
                </button>
                @if(request()->anyFilled(['search', 'status', 'reason']))
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary" style="padding: 0.65rem 1rem;">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Reported Material</th>
                        <th>Reason & Details</th>
                        <th>Reporter</th>
                        <th>Status</th>
                        <th>Reported Date</th>
                        <th style="text-align: right; width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>
                                <span style="font-weight: 700; color: var(--text-muted); font-size: 0.85rem;">#{{ $report->id }}</span>
                            </td>

                            <td>
                                @if($report->resource)
                                    <div style="display: flex; flex-direction: column; gap: 0.2rem;">
                                        <a href="{{ route('admin.reports.show', $report) }}" style="font-weight: 600; color: #4f46e5; text-decoration: none;">
                                            {{ $report->resource->title }}
                                        </a>
                                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">
                                                📖 {{ $report->resource->course?->name ?? 'Unknown Course' }}
                                            </span>
                                            @foreach($report->resource->course?->programs ?? [] as $prog)
                                                <span style="background: var(--bg); border: 1px solid var(--border); font-size: 0.7rem; padding: 0.1rem 0.35rem; border-radius: 4px; color: var(--text-muted);">
                                                    🎓 {{ $prog->code ?? $prog->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <span style="color: #ef4444; font-size: 0.85rem; font-style: italic;">
                                        ⚠️ Resource Deleted
                                    </span>
                                @endif
                            </td>

                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <span style="font-weight: 600; font-size: 0.8125rem; color: var(--text-main);">
                                        {{ $report->reason_label }}
                                    </span>
                                    @if($report->details)
                                        <span style="font-size: 0.775rem; color: var(--text-muted); max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $report->details }}">
                                            "{{ $report->details }}"
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td>
                                @if($report->user)
                                    <div style="display: flex; flex-direction: column; font-size: 0.8125rem;">
                                        <span style="font-weight: 600; color: var(--text-main);">{{ $report->user->first_name }} {{ $report->user->last_name }}</span>
                                        @if($report->user->username)
                                            <a href="https://t.me/{{ $report->user->username }}" target="_blank" style="color: #0284c7; text-decoration: none; font-size: 0.75rem;">
                                                @<span>{{ $report->user->username }}</span>
                                            </a>
                                        @elseif($report->user->telegram_user_id)
                                            <span style="color: var(--text-muted); font-size: 0.725rem;">ID: {{ $report->user->telegram_user_id }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">Anonymous / Bot</span>
                                @endif
                            </td>

                            <td>
                                @if($report->status === 'pending')
                                    <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        ⏳ Pending
                                    </span>
                                @elseif($report->status === 'resolved')
                                    <span style="background: #d1fae5; color: #065f46; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        ✅ Resolved
                                    </span>
                                @else
                                    <span style="background: #f1f5f9; color: #475569; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem;">
                                        🚫 Dismissed
                                    </span>
                                @endif
                            </td>

                            <td>
                                <span style="font-size: 0.8125rem; color: var(--text-muted);" title="{{ $report->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $report->created_at->diffForHumans() }}
                                </span>
                            </td>

                            <td style="text-align: right;">
                                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 0.35rem;">
                                    <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.6rem; font-size: 0.775rem;" title="View Details">
                                        🔍 Review
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem 1rem;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                    <span style="font-size: 2.5rem;">🎉</span>
                                    <span style="font-weight: 600; font-size: 1.1rem; color: var(--text-main);">No reports found!</span>
                                    <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 400px;">
                                        All reported materials have been addressed or no reports match your current filters.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
            <div style="padding: 1rem; border-top: 1px solid var(--border);">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
