@extends('admin.layout')

@section('title', 'Edit Resource - ' . $resource->title)

@section('content')
<div style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- Top Navigation -->
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; color: var(--text-main);">✏️ Edit Resource</h1>
            <p style="color: var(--text-muted); font-size: 0.8125rem;">Update resource details and moderation status</p>
        </div>
        <a href="{{ route('admin.resources.index') }}" class="btn btn-secondary">
            ⬅️ Back to Resources
        </a>
    </div>

    <!-- Origin & Submitter Provenance Card -->
    <div class="card" style="padding: 1.25rem; {{ $resource->isCommunitySubmission() ? 'background: #faf5ff; border: 1px solid #e9d5ff;' : 'background: #f0fdf4; border: 1px solid #bbf7d0;' }}">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                    @if($resource->isCommunitySubmission())
                        <span class="badge" style="background: #8b5cf6; color: #ffffff; font-weight: 600; font-size: 0.775rem;">
                            👤 Community Submission
                        </span>
                        <span style="font-size: 0.8125rem; color: #6b21a8; font-weight: 500;">
                            Submitted via Telegram Bot
                        </span>
                    @else
                        <span class="badge" style="background: #10b981; color: #ffffff; font-weight: 600; font-size: 0.775rem;">
                            🛡️ Official PARSI Material
                        </span>
                        <span style="font-size: 0.8125rem; color: #047857; font-weight: 500;">
                            Uploaded by PARSI Team
                        </span>
                    @endif
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; margin-top: 0.65rem; font-size: 0.825rem; color: var(--text-main);">
                    @if($resource->submitter)
                        <div>
                            <span style="color: var(--text-muted);">Contributor:</span>
                            <strong>{{ $resource->submitter->first_name }} {{ $resource->submitter->last_name }}</strong>
                            @if($resource->submitter->username)
                                <a href="https://t.me/{{ $resource->submitter->username }}" target="_blank" style="color: var(--color-primary); text-decoration: none; margin-left: 0.25rem;">
                                    {{ '@' . $resource->submitter->username }}
                                </a>
                            @endif
                        </div>
                        @if($resource->submitter->telegram_user_id)
                            <div>
                                <span style="color: var(--text-muted);">Telegram ID:</span>
                                <code>{{ $resource->submitter->telegram_user_id }}</code>
                            </div>
                        @endif
                    @else
                        <div>
                            <span style="color: var(--text-muted);">Source:</span>
                            <strong>Direct Admin Upload</strong>
                        </div>
                    @endif

                    <div>
                        <span style="color: var(--text-muted);">Submitted:</span>
                        <span>{{ $resource->created_at ? $resource->created_at->format('M d, Y H:i') . ' (' . $resource->created_at->diffForHumans() . ')' : '—' }}</span>
                    </div>

                    @if($resource->reviewer)
                        <div>
                            <span style="color: var(--text-muted);">Reviewed by:</span>
                            <strong>{{ $resource->reviewer->first_name ?? $resource->reviewer->username }}</strong>
                            @if($resource->reviewed_at)
                                <span style="color: var(--text-muted);">({{ \Carbon\Carbon::parse($resource->reviewed_at)->format('M d, Y') }})</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Attached Files Management Card -->
    <div class="card" style="border: 1px solid var(--border); overflow: hidden;">
        <div class="card-header" style="background: #f8fafc;">
            <div>
                <h2 style="display: flex; align-items: center; gap: 0.5rem;">
                    📁 Attached Documents
                    <span class="badge" style="background: var(--primary-light); color: var(--primary); font-size: 0.75rem;">
                        {{ $resource->files->count() }} {{ \Illuminate\Support\Str::plural('file', $resource->files->count()) }}
                    </span>
                </h2>
                <p>Manage existing documents or delete obsolete ones</p>
            </div>
        </div>

        @if($resource->files->isEmpty())
            <div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.875rem;">
                ⚠️ No documents are currently attached to this resource. Use the upload area below to add file(s).
            </div>
        @else
            <div style="display: flex; flex-direction: column;">
                @foreach($resource->files as $file)
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border); background: #ffffff;">
                        <div style="display: flex; align-items: flex-start; gap: 0.75rem; max-width: 550px;">
                            <span style="font-size: 1.5rem; line-height: 1;">📄</span>
                            <div>
                                <strong style="font-size: 0.925rem; color: var(--text-main); word-break: break-all;">
                                    {{ $file->original_filename }}
                                </strong>
                                @php
                                    $pendingFileReports = $file->reports ? $file->reports->where('status', \App\Models\ResourceReport::STATUS_PENDING) : collect();
                                @endphp
                                @if($pendingFileReports->isNotEmpty())
                                    <span title="Reported issue by students" style="color: #ef4444; font-size: 1rem; margin-left: 0.35rem; font-weight: bold; vertical-align: middle;">❌</span>
                                    <a href="{{ route('admin.reports.show', $pendingFileReports->first()) }}" class="badge" style="background: #fee2e2; color: #b91c1c; border: 1px solid #f87171; font-size: 0.7rem; text-decoration: none; padding: 0.15rem 0.45rem; font-weight: 700; margin-left: 0.25rem;">
                                        Reported Issue ({{ $pendingFileReports->count() }})
                                    </a>
                                @endif
                                <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    Size: <strong>{{ $file->formatted_size }}</strong> &bull; Type: <code>{{ $file->mime_type }}</code>
                                </div>
                                <div style="display: flex; gap: 0.4rem; margin-top: 0.4rem; flex-wrap: wrap;">
                                    @if($file->telegram_file_id)
                                        <span class="badge badge-success" style="font-size: 0.7rem;" title="ID: {{ $file->telegram_file_id }}">
                                            ✅ Telegram Synced
                                        </span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: 0.7rem;">
                                            ⏳ Telegram Pending
                                        </span>
                                    @endif

                                    @if($file->drive_file_id)
                                        <span class="badge badge-info" style="font-size: 0.7rem;" title="ID: {{ $file->drive_file_id }}">
                                            📁 Drive Synced
                                        </span>
                                    @else
                                        <span class="badge badge-secondary" style="font-size: 0.7rem;">
                                            ⚪ No Drive Backup
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="{{ route('admin.resources.download', [$resource, $file]) }}" target="_blank" class="btn btn-secondary btn-sm" style="text-decoration: none;">
                                📥 Download
                            </a>

                            <form action="{{ route('admin.resources.files.destroy', [$resource, $file]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete \'{{ addslashes($file->original_filename) }}\'? This will remove it from local storage, Google Drive, and the bot.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    🗑️ Delete File
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Edit Form Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Resource Details</h2>
                <p>Modify course, title, academic year, and status</p>
            </div>
        </div>

        <form action="{{ route('admin.resources.update', $resource) }}" method="POST" enctype="multipart/form-data" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
            @csrf
            @method('PUT')

            <!-- Course Selection -->
            <div class="form-group">
                <label for="course_id">Course *</label>
                <select name="course_id" id="course_id" required>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ old('course_id', $resource->course_id) == $course->id ? 'selected' : '' }}>
                            {{ $course->name }} ({{ $course->course_code }})
                        </option>
                    @endforeach
                </select>
                @error('course_id') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <!-- Resource Type & Academic Year in 2 Columns -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="resource_type_id">Resource Type *</label>
                    <select name="resource_type_id" id="resource_type_id" required>
                        @foreach($resourceTypes as $type)
                            <option value="{{ $type->id }}" {{ old('resource_type_id', $resource->resource_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->display_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('resource_type_id') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="academic_year_id">Academic Year</label>
                    <select name="academic_year_id" id="academic_year_id">
                        <option value="">None / Optional</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ old('academic_year_id', $resource->academic_year_id) == $year->id ? 'selected' : '' }}>
                                {{ $year->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('academic_year_id') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Title -->
            <div class="form-group">
                <label for="title">Resource Title *</label>
                <input type="text" name="title" id="title" value="{{ old('title', $resource->title) }}" required>
                @error('title') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" rows="3" placeholder="Notes, topic summary, or lecturer info...">{{ old('description', $resource->description) }}</textarea>
                @error('description') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <!-- Exam Date & Language in 2 Columns -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label for="exam_date">Exam Date</label>
                    <input type="date" name="exam_date" id="exam_date" value="{{ old('exam_date', $resource->exam_date ? \Carbon\Carbon::parse($resource->exam_date)->format('Y-m-d') : '') }}">
                    @error('exam_date') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="language">Language</label>
                    <select name="language" id="language">
                        <option value="en" {{ old('language', $resource->language) === 'en' ? 'selected' : '' }}>English</option>
                        <option value="it" {{ old('language', $resource->language) === 'it' ? 'selected' : '' }}>Italian</option>
                        <option value="fa" {{ old('language', $resource->language) === 'fa' ? 'selected' : '' }}>Persian</option>
                    </select>
                    @error('language') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Status & Has Solutions in 2 Columns -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; align-items: center;">
                <div class="form-group">
                    <label for="status">Publication Status *</label>
                    <select name="status" id="status" required>
                        <option value="approved" {{ old('status', $resource->status) === 'approved' ? 'selected' : '' }}>Approved (Visible in Bot)</option>
                        <option value="pending" {{ old('status', $resource->status) === 'pending' ? 'selected' : '' }}>Pending Review</option>
                        <option value="rejected" {{ old('status', $resource->status) === 'rejected' ? 'selected' : '' }}>Rejected (Hidden)</option>
                    </select>
                    @error('status') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="margin-top: 1.25rem;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="has_solutions" value="1" {{ old('has_solutions', $resource->has_solutions) ? 'checked' : '' }}>
                        Includes Answers / Solutions
                    </label>
                </div>
            </div>

            <!-- Optional Add New / Replacement Files -->
            <div class="form-group" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 1.25rem;">
                <label style="font-weight: 600; color: var(--text-main); font-size: 0.9rem;">
                    ➕ Add New / Replacement Files (Optional)
                </label>
                <div class="file-dropzone" id="dropzone" style="margin-top: 0.5rem; background: #ffffff; position: relative;">
                    <input type="file" name="files[]" id="files" multiple accept=".pdf,.zip,.docx,.pptx" onchange="updateFileNames(this)" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                    <div class="icon" style="font-size: 1.75rem; margin-bottom: 0.35rem;">📁</div>
                    <div class="title" id="file-label" style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);">Click or Drag & Drop File(s) Here to Attach</div>
                    <div class="sub" style="font-size: 0.775rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Supports PDF, ZIP, DOCX, PPTX (up to 100MB per file). Newly added files will automatically sync to Telegram and Google Drive.
                    </div>
                </div>
                @error('files') <span class="error-msg">{{ $message }}</span> @enderror
                @error('files.*') <span class="error-msg">{{ $message }}</span> @enderror
                @error('file') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <!-- Form Actions -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="{{ route('admin.resources.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    💾 Save Changes
                </button>
            </div>
        </form>
    </div>

</div>

<style>
    .file-dropzone {
        border: 2px dashed var(--border);
        border-radius: 8px;
        padding: 1.5rem 1rem;
        text-align: center;
        background: #fdfdfd;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .file-dropzone:hover {
        border-color: var(--primary);
        background-color: #f8fafc;
    }

    .file-dropzone .title {
        color: var(--text-main);
    }
</style>

<script>
    function updateFileNames(input) {
        const label = document.getElementById('file-label');
        if (input.files && input.files.length > 0) {
            if (input.files.length === 1) {
                label.innerText = 'Selected: ' + input.files[0].name;
            } else {
                const names = Array.from(input.files).map(f => f.name).join(', ');
                label.innerText = `Selected (${input.files.length} files): ${names}`;
            }
            label.style.color = '#4f46e5';
        }
    }
</script>
@endsection
