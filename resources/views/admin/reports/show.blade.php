@extends('admin.layout')

@section('title', 'Review Report #' . $report->id)

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem; max-width: 1000px; margin: 0 auto;">

    <!-- Top Navigation & Title -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary btn-sm" style="padding: 0.4rem 0.75rem;">
                ⬅️ Back to Reports
            </a>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin: 0;">
                Report #{{ $report->id }}
            </h1>
        </div>

        <div>
            @if($report->status === 'pending')
                <span style="background: #fef3c7; color: #92400e; padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 700;">
                    ⏳ Pending Review
                </span>
            @elseif($report->status === 'resolved')
                <span style="background: #d1fae5; color: #065f46; padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 700;">
                    ✅ Resolved
                </span>
            @else
                <span style="background: #f1f5f9; color: #475569; padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 700;">
                    🚫 Dismissed
                </span>
            @endif
        </div>
    </div>

    <!-- Report Details Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
        
        <!-- Issue Summary Card -->
        <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin: 0;">
                📋 Issue Details
            </h2>

            <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                <div>
                    <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.2rem;">Reported Reason</span>
                    <span style="font-weight: 600; font-size: 1rem; color: var(--text-main);">
                        {{ $report->reason_label }}
                    </span>
                </div>

                <div>
                    <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.2rem;">Student's Comment / Details</span>
                    <div style="background: var(--bg); border: 1px solid var(--border); padding: 0.75rem 1rem; border-radius: 6px; font-size: 0.875rem; color: var(--text-main); white-space: pre-wrap;">{{ $report->details ?: 'No additional notes provided by student.' }}</div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.25rem;">
                    <div>
                        <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.2rem;">Submitted By</span>
                        @if($report->user)
                            <div style="font-weight: 600; color: var(--text-main);">
                                {{ $report->user->first_name }} {{ $report->user->last_name }}
                            </div>
                            @if($report->user->username)
                                <a href="https://t.me/{{ $report->user->username }}" target="_blank" style="color: #0284c7; text-decoration: none; font-size: 0.8rem;">
                                    @<span>{{ $report->user->username }}</span>
                                </a>
                            @endif
                        @else
                            <span style="color: var(--text-muted); font-style: italic;">Anonymous / Bot</span>
                        @endif
                    </div>

                    <div>
                        <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.2rem;">Reported Date</span>
                        <span style="font-weight: 600; color: var(--text-main);">
                            {{ $report->created_at->format('M d, Y H:i') }}
                        </span>
                        <div style="color: var(--text-muted); font-size: 0.75rem;">
                            {{ $report->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>

                @if($report->resolved_by)
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 0.75rem 1rem; border-radius: 6px; margin-top: 0.5rem;">
                        <span style="color: #166534; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 0.2rem;">Resolution Info</span>
                        <div style="font-size: 0.85rem; color: #14532d;">
                            Handled by <strong>{{ $report->resolver?->first_name ?? 'Admin' }}</strong>
                            @if($report->resolved_at)
                                on {{ is_string($report->resolved_at) ? \Carbon\Carbon::parse($report->resolved_at)->format('M d, Y H:i') : $report->resolved_at->format('M d, Y H:i') }}
                            @endif
                                <p>{{ $report->admin_notes ?? '' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Target Resource Card -->
        <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin: 0;">
                    📚 Reported Material
                </h2>
                @if($report->resource && Auth::user()->canEditResource($report->resource))
                    <a href="{{ route('admin.resources.edit', $report->resource) }}" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                        ✏️ Edit Resource
                    </a>
                @endif
            </div>

            @if($report->resource)
                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                    <div>
                        <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.2rem;">Title</span>
                        <span style="font-weight: 700; font-size: 1.05rem; color: var(--text-main);">
                            {{ $report->resource->title }}
                        </span>
                    </div>

                    <div>
                        <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.2rem;">Course & Programmes</span>
                        <div style="font-weight: 600; color: var(--text-main);">
                            📖 {{ $report->resource->course?->name ?? 'Unknown Course' }}
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.25rem;">
                            @foreach($report->resource->course?->programs ?? [] as $prog)
                                <span style="background: var(--bg); border: 1px solid var(--border); font-size: 0.75rem; padding: 0.15rem 0.45rem; border-radius: 4px; color: var(--text-muted);">
                                    🎓 {{ $prog->name }} ({{ $prog->code ?? 'Major' }})
                                </span>
                            @endforeach
                        </div>
                    </div>

                    @if($report->resource->description)
                        <div>
                            <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.2rem;">Description</span>
                            <p style="color: var(--text-muted); font-size: 0.825rem; margin: 0;">{{ $report->resource->description }}</p>
                        </div>
                    @endif

                    <!-- Files Attached -->
                    <div>
                        <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.35rem;">
                            Attached Files ({{ $report->resource->files->count() }})
                        </span>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            @forelse($report->resource->files as $file)
                                @php
                                    $isReportedFile = ($report->resource_file_id && $report->resource_file_id === $file->id);
                                @endphp
                                <div style="display: flex; justify-content: space-between; align-items: center; background: {{ $isReportedFile ? '#fff1f2' : 'var(--bg)' }}; border: 1px solid {{ $isReportedFile ? '#f87171' : 'var(--border)' }}; padding: 0.5rem 0.75rem; border-radius: 6px;">
                                    <div style="display: flex; flex-direction: column; overflow: hidden; margin-right: 0.5rem;">
                                        <span style="font-weight: 600; font-size: 0.8125rem; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $file->original_filename }}">
                                            📄 {{ $file->original_filename }}
                                            @if($isReportedFile)
                                                <span style="color: #dc2626; font-weight: 700; font-size: 0.75rem; margin-left: 0.35rem;">❌ Reported File</span>
                                            @endif
                                        </span>
                                        <span style="font-size: 0.725rem; color: var(--text-muted);">
                                            {{ $file->formatted_size }}
                                            @if($file->drive_file_id)
                                                • <span style="color: #0284c7; font-weight: 600;">Drive Synced</span>
                                            @endif
                                            @if($file->telegram_file_id)
                                                • <span style="color: #2563eb; font-weight: 600;">Telegram Ready</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div style="display: flex; gap: 0.35rem; flex-shrink: 0; align-items: center;">
                                        @if($file->local_path)
                                            <a href="{{ route('admin.resources.download', [$report->resource, $file]) }}" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                📥 Download
                                            </a>
                                        @endif
                                        @if($file->drive_file_id)
                                            <a href="https://drive.google.com/file/d/{{ $file->drive_file_id }}/view" target="_blank" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                                🌐 Drive
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div style="color: var(--text-muted); font-size: 0.8rem; font-style: italic;">
                                    No physical files linked to this resource.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <div style="padding: 2rem 1rem; text-align: center; color: #ef4444;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚠️</div>
                    <div style="font-weight: 600;">This resource was already deleted.</div>
                </div>
            @endif
        </div>

    </div>

    <!-- Admin Review & Actions Card -->
    <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
        <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin: 0;">
            🛠️ Moderator Action & Resolution
        </h2>

        @if(Auth::user()->canManageReport($report))
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                
                <!-- Notes form for Resolve / Dismiss -->
                <div>
                    <label for="admin_notes" style="font-weight: 600; font-size: 0.875rem; color: var(--text-main); margin-bottom: 0.35rem; display: block;">
                        Admin / Moderator Notes (Optional)
                    </label>
                    <textarea id="admin_notes_input" name="admin_notes" rows="3" placeholder="Explain the actions taken or reason for dismissal..." style="width: 100%; border: 1px solid var(--border); border-radius: 6px; padding: 0.75rem; font-family: inherit; font-size: 0.875rem; box-sizing: border-box;">{{ old('admin_notes', $report->admin_notes) }}</textarea>
                </div>

                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                    
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <!-- Resolve Form -->
                        <form id="resolveForm" method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                            @csrf
                            <input type="hidden" name="admin_notes" id="resolve_notes">
                            <button type="submit" class="btn btn-primary" onclick="document.getElementById('resolve_notes').value = document.getElementById('admin_notes_input').value;" style="background: #10b981; border-color: #10b981; padding: 0.65rem 1.25rem;">
                                ✅ Mark as Resolved
                            </button>
                        </form>

                        <!-- Dismiss Form -->
                        <form id="dismissForm" method="POST" action="{{ route('admin.reports.dismiss', $report) }}">
                            @csrf
                            <input type="hidden" name="admin_notes" id="dismiss_notes">
                            <button type="submit" class="btn btn-secondary" onclick="document.getElementById('dismiss_notes').value = document.getElementById('admin_notes_input').value;" style="padding: 0.65rem 1.25rem;">
                                🚫 Dismiss Report
                            </button>
                        </form>
                    </div>

                    <!-- Danger Action: Delete Specific Reported File -->
                    @php
                        $targetFileName = $report->resourceFile?->original_filename;
                    @endphp
                    @if($report->resource && $targetFileName && Auth::user()->canDeleteResource($report->resource))
                        <form method="POST" action="{{ route('admin.reports.resource.destroy', $report) }}" onsubmit="return confirm('⚠️ ARE YOU SURE?\n\nThis will PERMANENTLY delete the reported file \'{{ addslashes($targetFileName) }}\' from local storage, Telegram, and Google Drive, and mark this report as Resolved.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding: 0.65rem 1.25rem; background: #ef4444; color: white;">
                                🗑️ Delete Reported File ({{ \Illuminate\Support\Str::limit($targetFileName, 25) }})
                            </button>
                        </form>
                    @endif

                </div>

            </div>
        @else
            <div style="background: var(--bg); border: 1px solid var(--border); padding: 1rem; border-radius: 6px; color: var(--text-muted); font-size: 0.875rem;">
                ℹ️ You have read-only access to this report. Only Super Admins, Moderators, and Major Admins assigned to this programme can resolve or take action on this report.
            </div>
        @endif
    </div>

</div>
@endsection
