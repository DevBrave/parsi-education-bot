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
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem;">
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">Total Materials</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);">{{ $stats['total'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #10b981;">
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">Telegram Synced</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: #065f46;">{{ $stats['telegram_synced'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #0284c7;">
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">Google Drive Synced</span>
            <span style="font-size: 1.75rem; font-weight: 700; color: #0369a1;">{{ $stats['drive_synced'] }}</span>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #f59e0b;">
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">Pending Review / Sync</span>
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
                @if(request()->anyFilled(['search', 'course_id', 'resource_type_id', 'status', 'platform']))
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

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="min-width: 220px;">Resource Title</th>
                        <th style="min-width: 180px;">Course & Programmes</th>
                        <th>Type</th>
                        <th style="min-width: 140px;">Attached File</th>
                        <th style="min-width: 190px;">Platform Upload Status</th>
                        <th>Status</th>
                        <th style="text-align: right; min-width: 140px;">Actions</th>
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
                                    <strong style="color: var(--text-main); font-size: 0.925rem;">{{ $resource->title }}</strong>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.25rem;">
                                        @if($resource->academicYear)
                                            <span class="badge badge-secondary">{{ $resource->academicYear->label }}</span>
                                        @endif
                                        @if($resource->has_solutions)
                                            <span class="badge badge-success" style="font-size: 0.7rem;">✓ Solutions</span>
                                        @endif
                                        @if($resource->language)
                                            <span class="badge badge-secondary" style="font-size: 0.7rem; text-transform: uppercase;">{{ $resource->language }}</span>
                                        @endif
                                    </div>
                                    @if($resource->exam_date)
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                                            📅 Exam: {{ $resource->exam_date }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- 2. Course & Programmes -->
                            <td>
                                @if($resource->course)
                                    <strong>{{ $resource->course->name }}</strong>
                                    <div style="color: var(--text-muted); font-size: 0.75rem; font-family: monospace;">
                                        [{{ $resource->course->course_code }}]
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.2rem;">
                                        @foreach($resource->course->programs as $p)
                                            <span class="badge badge-primary" style="font-size: 0.65rem;">{{ $p->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color: var(--text-muted);">No Course</span>
                                @endif
                            </td>

                            <!-- 3. Resource Type -->
                            <td>
                                @if($resource->resourceType)
                                    <span class="badge badge-primary">{{ $resource->resourceType->display_name }}</span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">—</span>
                                @endif
                            </td>

                            <!-- 4. Attached File Details -->
                            <td>
                                @if($primaryFile)
                                    <div style="font-size: 0.8125rem;">
                                        <div style="font-weight: 500; word-break: break-all;" title="{{ $primaryFile->original_filename }}">
                                            📄 {{ Str::limit($primaryFile->original_filename, 20) }}
                                        </div>
                                        <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.15rem;">
                                            {{ $primaryFile->formatted_size }}
                                        </div>
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">No File Attached</span>
                                @endif
                            </td>

                            <!-- 5. Platform Upload Status Badges -->
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.35rem; align-items: flex-start;">
                                    <!-- Telegram Status -->
                                    @if($primaryFile && $primaryFile->telegram_file_id)
                                        <span class="badge badge-success" title="Telegram File ID: {{ $primaryFile->telegram_file_id }}">
                                            ✅ Telegram Synced
                                        </span>
                                    @elseif($resource->status === 'rejected')
                                        <span class="badge badge-danger">
                                            ❌ Telegram Failed
                                        </span>
                                    @else
                                        <span class="badge badge-warning">
                                            ⏳ Telegram Pending
                                        </span>
                                    @endif

                                    <!-- Google Drive Status -->
                                    @if($primaryFile && $primaryFile->drive_file_id)
                                        <span class="badge badge-info" title="Drive File ID: {{ $primaryFile->drive_file_id }}">
                                            📁 Drive Synced
                                        </span>
                                    @else
                                        <span class="badge badge-secondary" style="opacity: 0.75;">
                                            ⚪ No Drive Backup
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 6. Overall Status -->
                            <td>
                                @if($resource->status === 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($resource->status === 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @else
                                    <span class="badge badge-danger">Rejected</span>
                                @endif
                            </td>

                            <!-- 7. Actions -->
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem; align-items: center; justify-content: flex-end;">
                                    <a href="{{ route('admin.resources.edit', $resource) }}" class="btn btn-secondary btn-sm">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.resources.destroy', $resource) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this resource?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
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
