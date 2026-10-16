@extends('admin.layout')

@section('title', 'Manage Resources')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- Top Header & Action -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">📚 Educational Resources</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage study materials and monitor their upload sync status across Telegram and Google Drive</p>
        </div>
        <a href="{{ route('admin.resources.create') }}" class="btn btn-primary" style="padding: 0.65rem 1.25rem;">
            ➕ Upload New Resource
        </a>
    </div>

    <!-- Quick Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem;">
        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">Total Materials</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);">{{ $stats['total'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #8b5cf6;">
            <span style="font-size: 0.8125rem; color: #6b21a8; font-weight: 600;">👤 Community Submissions</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: #5b21b6;">{{ $stats['community'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #059669;">
            <span style="font-size: 0.8125rem; color: #047857; font-weight: 600;">🛡️ Official PARSI</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: #065f46;">{{ $stats['official'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #0284c7;">
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">Google Drive Synced</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: #0369a1;">{{ $stats['drive_synced'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #f59e0b;">
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">Pending Review</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: #92400e;">{{ $stats['pending'] }}</span>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="card" style="padding: 1.25rem;">
        <form method="GET" action="{{ route('admin.resources.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: flex-end;">
            
            <div class="form-group">
                <label for="search">Search Keyword</label>
                <input type="text" id="search" name="search" placeholder="Title, course code, notes..." value="{{ request('search') }}">
            </div>

            <div class="form-group">
                <label for="course_id">Course</label>
                <select id="course_id" name="course_id">
                    <option value="">All Courses</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->course_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="source">Origin / Submitter</label>
                <select id="source" name="source">
                    <option value="">All Origins</option>
                    <option value="community" {{ request('source') === 'community' ? 'selected' : '' }}>👤 Community (Students)</option>
                    <option value="official" {{ request('source') === 'official' ? 'selected' : '' }}>🛡️ Official (PARSI Team)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="resource_type_id">Type</label>
                <select id="resource_type_id" name="resource_type_id">
                    <option value="">All Types</option>
                    @foreach($resourceTypes as $t)
                        <option value="{{ $t->id }}" {{ request('resource_type_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <div class="form-group">
                <label for="platform">Platform Sync</label>
                <select id="platform" name="platform">
                    <option value="">All Platforms</option>
                    <option value="telegram_synced" {{ request('platform') === 'telegram_synced' ? 'selected' : '' }}>Telegram Synced ✅</option>
                    <option value="telegram_pending" {{ request('platform') === 'telegram_pending' ? 'selected' : '' }}>Telegram Missing ⏳</option>
                    <option value="drive_synced" {{ request('platform') === 'drive_synced' ? 'selected' : '' }}>Drive Synced 📁</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                @if(request()->anyFilled(['search', 'course_id', 'source', 'resource_type_id', 'status', 'platform']))
                    <a href="{{ route('admin.resources.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Resources Table Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2>All Uploaded Resources ({{ $resources->total() }})</h2>
                <p>Showing page {{ $resources->currentPage() }} of {{ $resources->lastPage() }}</p>
            </div>
        </div>

        <style>
            .compact-table th {
                padding: 0.55rem 0.65rem !important;
                font-size: 0.72rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.04em !important;
                color: var(--text-muted) !important;
                border-bottom: 1px solid var(--border) !important;
                background: #f8fafc !important;
            }
            .compact-table td {
                padding: 0.55rem 0.65rem !important;
                font-size: 0.8rem !important;
                vertical-align: middle !important;
                border-bottom: 1px solid var(--border) !important;
            }
            .compact-table tr:hover td {
                background-color: #f8fafc !important;
            }
        </style>

        <div class="table-responsive">
            <table class="compact-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="min-width: 160px;">Resource Title</th>
                        <th style="min-width: 160px;">Course & Programmes</th>
                        <th style="min-width: 100px;">Type</th>
                        <th style="min-width: 110px;">Origin</th>
                        <th style="min-width: 110px;">Attached File</th>
                        <th style="min-width: 120px;">Platform Status</th>
                        <th style="min-width: 75px;">Status</th>
                        <th style="text-align: right; min-width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resources as $resource)
                        @php
                            $primaryFile = $resource->files->first();
                        @endphp
                        <tr>
                            <!-- 1. Resource Title & Metadata -->
                            <td>
                                <div>
                                    <strong style="color: var(--text-main); font-size: 0.875rem;">{{ $resource->title }}</strong>
                                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem; margin-top: 0.2rem;">
                                        @if($resource->language)
                                            <span style="font-size: 0.65rem; font-weight: 700; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 3px; padding: 1px 4px; text-transform: uppercase;">{{ $resource->language }}</span>
                                        @endif
                                        @if($resource->academicYear)
                                             <span style="font-size: 0.675rem; color: var(--text-muted); font-weight: 500;">{{ $resource->academicYear->label }}</span>
                                        @endif
                                        @if($resource->has_solutions)
                                            <span style="font-size: 0.65rem; font-weight: 600; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 3px; padding: 1px 4px;">✓ Solved</span>
                                        @endif
                                    </div>
                                    @if($resource->exam_date)
                                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.15rem;">
                                            📅 {{ $resource->exam_date }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- 2. Course & Programmes -->
                            <td>
                                @if($resource->course)
                                    <div style="font-weight: 600; color: var(--text-main); font-size: 0.84rem;">{{ $resource->course->name }}</div>
                                    @if($resource->course->programs->isNotEmpty())
                                        <div style="color: var(--text-muted); font-size: 0.725rem; margin-top: 0.15rem; line-height: 1.35;" title="{{ $resource->course->programs->pluck('name')->implode(', ') }}">
                                            {{ $resource->course->programs->pluck('name')->implode(' • ') }}
                                        </div>
                                    @endif
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">—</span>
                                @endif
                            </td>

                            <!-- 3. Resource Type -->
                            <td>
                                @if($resource->resourceType)
                                    <span style="font-size: 0.75rem; font-weight: 600; color: #3730a3; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 4px; padding: 2px 7px; display: inline-block; white-space: nowrap;">
                                        {{ $resource->resourceType->display_name }}
                                    </span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">—</span>
                                @endif
                            </td>

                            <!-- 4. Origin / Submitter -->
                            <td>
                                @if($resource->isCommunitySubmission())
                                    <div>
                                        <span style="background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; border-radius: 4px; padding: 1px 5px; font-size: 0.685rem; font-weight: 600; display: inline-block;">
                                            👤 Community
                                        </span>
                                        <div style="font-size: 0.775rem; font-weight: 600; color: var(--text-main); margin-top: 0.15rem;">
                                            @if($resource->submitter?->username)
                                                <a href="https://t.me/{{ $resource->submitter->username }}" target="_blank" style="color: #6d28d9; text-decoration: none;">
                                                    {{ '@' . $resource->submitter->username }}
                                                </a>
                                            @else
                                                {{ $resource->submitter?->first_name ?? 'Student' }}
                                            @endif
                                        </div>
                                        @if($resource->submitter?->telegram_user_id)
                                            <div style="font-size: 0.675rem; color: var(--text-muted); font-family: monospace;">
                                                TG: #{{ $resource->submitter->telegram_user_id }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div>
                                        <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 4px; padding: 1px 5px; font-size: 0.685rem; font-weight: 600; display: inline-block;">
                                            🛡️ PARSI
                                        </span>
                                        <div style="font-size: 0.725rem; color: var(--text-muted); margin-top: 0.15rem;">
                                            Official
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <!-- 5. Attached File Details -->
                            <td>
                                @if($primaryFile)
                                    <div style="font-size: 0.775rem; line-height: 1.3;">
                                        <div>
                                            <a href="{{ route('admin.resources.download', [$resource, $primaryFile]) }}" target="_blank" style="font-weight: 600; color: var(--primary); text-decoration: none;" title="Download: {{ $primaryFile->original_filename }}">
                                                📄 {{ Str::limit($primaryFile->original_filename, 18) }}
                                            </a>
                                        </div>
                                        <div style="color: var(--text-muted); font-size: 0.685rem; margin-top: 0.15rem; display: flex; align-items: center; gap: 0.35rem;">
                                            <span>{{ $primaryFile->formatted_size }}</span>
                                            @if($primaryFile->drive_file_id)
                                                <span>•</span>
                                                <a href="https://drive.google.com/file/d/{{ $primaryFile->drive_file_id }}/view" target="_blank" style="color: #0284c7; text-decoration: none; font-weight: 600;" title="Preview on Google Drive">
                                                    Drive ↗
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.725rem;">—</span>
                                @endif
                            </td>

                            <!-- 6. Platform Upload Status -->
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.72rem;">
                                    @if($primaryFile && $primaryFile->telegram_file_id)
                                        <span style="color: #065f46; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            ✅ Telegram Synced
                                        </span>
                                    @elseif($resource->status === 'rejected')
                                        <span style="color: #991b1b; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            ❌ Failed
                                        </span>
                                    @else
                                        <span style="color: #92400e; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            ⏳ Telegram Pending
                                        </span>
                                    @endif

                                    @if($primaryFile && $primaryFile->drive_file_id)
                                        <a href="https://drive.google.com/file/d/{{ $primaryFile->drive_file_id }}/view" target="_blank" style="color: #0369a1; font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem; text-decoration: none;" title="Open in Google Drive">
                                            📁 Drive Synced ↗
                                        </a>
                                    @elseif($primaryFile && $primaryFile->size_bytes > 20 * 1024 * 1024)
                                        <span style="color: #b45309; font-size: 0.685rem; display: inline-flex; align-items: center; gap: 0.25rem;" title="File exceeds Telegram's 20MB Bot API limit. Edit resource to upload to Google Drive.">
                                            ⚠️ >20MB (No Drive)
                                        </span>
                                    @else
                                        <span style="color: #64748b; font-size: 0.685rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                            ⚪ No Drive Backup
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 7. Overall Status -->
                            <td>
                                @if($resource->status === 'approved')
                                    <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 4px; padding: 2px 6px; font-size: 0.725rem; font-weight: 600; display: inline-block;">Approved</span>
                                @elseif($resource->status === 'pending')
                                    <span style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; border-radius: 4px; padding: 2px 6px; font-size: 0.725rem; font-weight: 600; display: inline-block;">Pending</span>
                                @else
                                    <span style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 4px; padding: 2px 6px; font-size: 0.725rem; font-weight: 600; display: inline-block;">Rejected</span>
                                @endif
                            </td>

                            <!-- 8. Actions -->
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.3rem; align-items: center; justify-content: flex-end;">
                                    <a href="{{ route('admin.resources.edit', $resource) }}" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.725rem; border-radius: 4px;">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.resources.destroy', $resource) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this resource?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.725rem; border-radius: 4px;">
                                            Del
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                                <div>
                                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📁</div>
                                    <strong>No resources found</strong>
                                    <p style="margin-top: 0.25rem; font-size: 0.8125rem;">Try adjusting your filters or upload a new material.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($resources->hasPages())
            <div style="padding: 1.25rem; border-top: 1px solid var(--border); display: flex; justify-content: center;">
                {{ $resources->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
