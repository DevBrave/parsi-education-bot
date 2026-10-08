<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Upload Educational Resource</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg: #f8fafc;
            --card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --error: #ef4444;
            --radius: 12px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .container {
            width: 100%;
            max-width: 720px;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .header {
            padding: 2rem 2.5rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header p {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        form {
            padding: 2rem 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 640px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            .header, form {
                padding: 1.5rem;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
        }

        .required::after {
            content: " *";
            color: var(--error);
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.925rem;
            color: var(--text-main);
            background-color: #fff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            outline: none;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* File Upload Box */
        .file-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 1.75rem 1rem;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }

        .file-dropzone:hover {
            border-color: var(--primary);
            background: #f1f5f9;
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
            font-weight: 600;
            font-size: 0.95rem;
            color: #334155;
        }

        .file-dropzone .sub {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        /* Checkbox style */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            user-select: none;
            margin-top: 0.25rem;
        }

        .checkbox-group input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .error-msg {
            color: var(--error);
            font-size: 0.775rem;
            margin-top: 0.25rem;
        }

        /* Submit Button */
        .btn-submit {
            background-color: var(--primary);
            color: #fff;
            padding: 0.85rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        .btn-submit:active {
            transform: scale(0.99);
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>📚 Upload Resource</h1>
            <p>Upload a material to Google Drive and sync with Telegram Bot</p>
        </div>

        @if(session('success'))
            <div style="margin: 1.5rem 2.5rem 0; padding: 1rem; background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ route('admin.resources.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Course Selection -->
            <div class="form-group">
                <label for="course_id" class="required">Course</label>
                <select name="course_id" id="course_id" required>
                    <option value="">Choose Course </option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->name }} ({{ $course->course_code }})
                        </option>
                    @endforeach
                </select>
                @error('course_id') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <!-- Resource Type & Academic Year in 2 Columns -->
            <div class="grid-2">
                <div class="form-group">
                    <label for="resource_type_id" class="required">Resource Type</label>
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
                <label for="title" class="required">Resource Title</label>
                <input type="text" name="title" id="title" placeholder="e.g. Final Exam - January 2026" value="{{ old('title') }}" required>
                @error('title') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" placeholder="Optional notes or details about the material...">{{ old('description') }}</textarea>
                @error('description') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <!-- Exam Date & Language in 2 Columns -->
            <div class="grid-2">
                <div class="form-group">
                    <label for="exam_date">Exam Date</label>
                    <input type="date" name="exam_date" id="exam_date" value="{{ old('exam_date') }}">
                    @error('exam_date') <span class="error-msg">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="language">Language</label>
                    <select name="language" id="language">
                        <option value="en" {{ old('language', 'en') == 'en' ? 'selected' : '' }}>🇬🇧 English</option>
                        <option value="it" {{ old('language') == 'it' ? 'selected' : '' }}>🇮🇹 Italian</option>
                        <option value="fa" {{ old('language') == 'fa' ? 'selected' : '' }}>🇮🇷 Persian</option>
                    </select>
                    @error('language') <span class="error-msg">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Has Solutions Checkbox -->
            <label class="checkbox-group">
                <input type="checkbox" name="has_solutions" value="1" {{ old('has_solutions') ? 'checked' : '' }}>
                <span>Includes Answers / Solutions</span>
            </label>

            <!-- File Upload -->
            <div class="form-group">
                <label class="required">Attach File</label>
                <div class="file-dropzone" id="dropzone">
                    <input type="file" name="file" id="file" required onchange="updateFileName(this)">
                    <div class="icon">📁</div>
                    <div class="title" id="file-label">Click or Drag & Drop File Here</div>
                    <div class="sub">Supports PDF, ZIP, DOCX, PPTX (up to 50MB)</div>
                </div>
                @error('file') <span class="error-msg">{{ $message }}</span> @enderror
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-submit">
                🚀 Upload & Sync to Telegram
            </button>
        </form>
    </div>

    <script>
        function updateFileName(input) {
            const label = document.getElementById('file-label');
            if (input.files && input.files[0]) {
                label.innerText = 'Selected: ' + input.files[0].name;
                label.style.color = '#4f46e5';
            }
        }
    </script>
</body>
</html>
