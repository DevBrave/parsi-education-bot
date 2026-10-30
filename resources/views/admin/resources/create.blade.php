@extends('admin.layout')

@section('title', 'Upload Educational Resource')

@section('content')
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <div>
            <h2>📚 Upload Resource</h2>
            <p>Upload a material and sync with Telegram Bot and Google Drive</p>
        </div>
        <a href="{{ route('admin.resources.index') }}" class="btn btn-secondary">
            ⬅️ Back to Resources
        </a>
    </div>

    <form action="{{ route('admin.resources.store') }}" method="POST" enctype="multipart/form-data" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.25rem;">
        @csrf

        <!-- Course Selection -->
        <div class="form-group">
            <label for="course_id">Course *</label>
            <select name="course_id" id="course_id" required>
                <option value="">Choose Course</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
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
                    <option value="">Select Type</option>
                    @foreach($resourceTypes as $type)
                        <option value="{{ $type->id }}" {{ old('resource_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->display_name }}
                        </option>
                    @endforeach
                </select>
                @error('resource_type_id') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="academic_year_id">Academic Year</label>
                <select name="academic_year_id" id="academic_year_id">
                    <option value="">Optional</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
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
            <input type="text" name="title" id="title" placeholder="e.g. Final Exam - January 2026" value="{{ old('title') }}" required>
            @error('title') <span class="error-msg">{{ $message }}</span> @enderror
        </div>

        <!-- Description -->
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="3" placeholder="Optional notes or details about the material...">{{ old('description') }}</textarea>
            @error('description') <span class="error-msg">{{ $message }}</span> @enderror
        </div>

        <!-- Exam Date & Language in 2 Columns -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label for="exam_date">Exam Date</label>
                <input type="date" name="exam_date" id="exam_date" value="{{ old('exam_date') }}">
                @error('exam_date') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="language">Language</label>
                <select name="language" id="language">
                    <option value="en" {{ old('language', 'en') == 'en' ? 'selected' : '' }}>English</option>
                    <option value="it" {{ old('language') == 'it' ? 'selected' : '' }}>Italian</option>
                    <option value="fa" {{ old('language') == 'fa' ? 'selected' : '' }}>Persian</option>
                </select>
                @error('language') <span class="error-msg">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Status & Has Solutions in 2 Columns -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; align-items: center; margin-bottom: 1rem;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="status">Publication Status *</label>
                <select name="status" id="status" required>
                    <option value="approved" {{ old('status', 'approved') === 'approved' ? 'selected' : '' }}>✅ Approved (Visible in Bot & Sync to Drive)</option>
                    <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>⏳ Pending Review (Draft - Telegram Only)</option>
                    <option value="rejected" {{ old('status') === 'rejected' ? 'selected' : '' }}>❌ Rejected</option>
                </select>
                @error('status') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="margin-bottom: 0; padding-top: 1.5rem;">
                <label class="checkbox-label">
                    <input type="checkbox" name="has_solutions" value="1" {{ old('has_solutions') ? 'checked' : '' }}>
                    Includes Answers / Solutions
                </label>
            </div>
        </div>

        <!-- File Upload -->
        <div class="form-group">
            <label>Attach Files * (Select one or more)</label>
            <div class="file-dropzone" id="dropzone">
                <input type="file" name="files[]" id="file" multiple required onchange="updateFileName(this)">
                <div class="icon">📁</div>
                <div class="title" id="file-label">Click or Drag & Drop File(s) Here</div>
                <div class="sub">Supports PDF, ZIP, DOCX, PPTX (up to 500MB per file)</div>
            </div>
            @error('files') <span class="error-msg">{{ $message }}</span> @enderror
            @error('files.*') <span class="error-msg">{{ $message }}</span> @enderror

            <!-- Real-time Upload Progress Bar -->
            <div id="uploadProgressContainer" style="display: none; margin-top: 1rem; padding: 0.85rem; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.4rem;">
                    <span id="uploadProgressText">Uploading: 0%</span>
                    <span id="uploadSizeText" style="color: var(--text-muted); font-weight: normal;">0 MB / 0 MB</span>
                </div>
                <div style="height: 10px; background: #e2e8f0; border-radius: 5px; overflow: hidden;">
                    <div id="uploadProgressBar" style="height: 100%; width: 0%; background: #3b82f6; transition: width 0.15s ease;"></div>
                </div>
                <small id="uploadNoticeText" style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.35rem; display: block;">
                    ⏳ Uploading file(s) to server... Please keep this page open.
                </small>
            </div>
        </div>

        <!-- Submit Button -->
        <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
            <button type="submit" id="btnSubmitUpload" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 0.95rem;">
                🚀 Upload & Sync to Telegram
            </button>
        </div>
    </form>
</div>

<style>
    .file-dropzone {
        border: 2px dashed var(--border);
        border-radius: 8px;
        padding: 2rem 1.5rem;
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

    .file-dropzone input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .file-dropzone .icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .file-dropzone .title {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-main);
    }

    .file-dropzone .sub {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 0.25rem;
    }
</style>

<script>
    function updateFileName(input) {
        const label = document.getElementById('file-label');
        if (input.files && input.files.length > 0) {
            let totalBytes = 0;
            const names = Array.from(input.files).map(f => {
                totalBytes += f.size;
                return f.name;
            });
            const sizeMB = (totalBytes / (1024 * 1024)).toFixed(1);
            if (input.files.length === 1) {
                label.innerText = `Selected: ${names[0]} (${sizeMB} MB)`;
            } else {
                label.innerText = `Selected (${input.files.length} files, ${sizeMB} MB total): ${names.join(', ')}`;
            }
            label.style.color = '#4f46e5';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form[action="{{ route('admin.resources.store') }}"]');
        const fileInput = document.getElementById('file');
        const submitBtn = document.getElementById('btnSubmitUpload');
        const progressContainer = document.getElementById('uploadProgressContainer');
        const progressBar = document.getElementById('uploadProgressBar');
        const progressText = document.getElementById('uploadProgressText');
        const sizeText = document.getElementById('uploadSizeText');
        const noticeText = document.getElementById('uploadNoticeText');

        if (!form || !fileInput || !submitBtn) return;

        form.addEventListener('submit', function (e) {
            if (!fileInput.files || fileInput.files.length === 0) return;

            e.preventDefault();

            // Prepare UI for upload
            submitBtn.disabled = true;
            submitBtn.innerText = '⏳ Uploading in progress...';
            progressContainer.style.display = 'block';

            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.onprogress = function (event) {
                if (event.lengthComputable) {
                    const percent = Math.round((event.loaded / event.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressText.innerText = `Uploading: ${percent}%`;
                    const loadedMB = (event.loaded / (1024 * 1024)).toFixed(1);
                    const totalMB = (event.total / (1024 * 1024)).toFixed(1);
                    sizeText.innerText = `${loadedMB} MB / ${totalMB} MB`;

                    if (percent >= 100) {
                        progressBar.style.background = '#10b981';
                        progressText.innerText = 'Processing on server...';
                        noticeText.innerText = '✅ File uploaded! The server is now scanning security and saving to database...';
                    }
                }
            };

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 400) {
                    // Redirect back to resources list or the response URL
                    window.location.href = "{{ route('admin.resources.index') }}";
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerText = '🚀 Upload & Sync to Telegram';
                    progressBar.style.background = '#ef4444';
                    progressText.innerText = 'Upload failed';
                    noticeText.innerText = '❌ Error ' + xhr.status + ': Server upload failed. Check server maximum upload limits.';
                }
            };

            xhr.onerror = function () {
                submitBtn.disabled = false;
                submitBtn.innerText = '🚀 Upload & Sync to Telegram';
                progressBar.style.background = '#ef4444';
                progressText.innerText = 'Connection Error';
                noticeText.innerText = '❌ Upload aborted or timed out. Check server upload_max_filesize and client_max_body_size.';
            };

            xhr.send(formData);
        });
    });
</script>
@endsection
