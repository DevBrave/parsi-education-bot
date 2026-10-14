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

    <!-- Attached File Info Card -->
    @php
        $primaryFile = $resource->files->first();
    @endphp
    @if($primaryFile)
        <div class="card" style="padding: 1.25rem; background: #f8fafc; border: 1px solid var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Attached Document</span>
                    <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-main); margin-top: 0.2rem;">
                        📄 {{ $primaryFile->original_filename }}
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                        Size: <strong>{{ $primaryFile->formatted_size }}</strong> &bull; Type: <code>{{ $primaryFile->mime_type }}</code>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.35rem; align-items: flex-end;">
                    <!-- Telegram Badge -->
                    @if($primaryFile->telegram_file_id)
                        <span class="badge badge-success" title="ID: {{ $primaryFile->telegram_file_id }}">
                            ✅ Telegram Synced
                        </span>
                    @else
                        <span class="badge badge-warning">
                            ⏳ Telegram Pending
                        </span>
                    @endif

                    <!-- Google Drive Badge -->
                    @if($primaryFile->drive_file_id)
                        <span class="badge badge-info" title="ID: {{ $primaryFile->drive_file_id }}">
                            📁 Drive Synced
                        </span>
                    @else
                        <span class="badge badge-secondary">
                            ⚪ No Drive Backup
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Form Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Resource Details</h2>
                <p>Modify course, title, academic year, and status</p>
            </div>
        </div>

        <form action="{{ route('admin.resources.update', $resource) }}" method="POST" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
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
@endsection
