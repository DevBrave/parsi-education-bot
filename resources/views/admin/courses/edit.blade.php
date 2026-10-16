@extends('admin.layout')

@section('title', 'Edit Course')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>✏️ Edit Course: {{ $course->name }}</h2>
                <p>Modify course information and associated degree programmes</p>
            </div>
            <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary btn-sm">
                &larr; Back to List
            </a>
        </div>

        <form action="{{ route('admin.courses.update', $course) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label for="name">Course Name *</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Computer Architecture" value="{{ old('name', $course->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="course_code">Course Code *</label>
                    <input type="text" id="course_code" name="course_code" placeholder="e.g. 01TXYOV" maxlength="30" value="{{ old('course_code', $course->course_code) }}" required>
                </div>

                <div class="form-group">
                    <label for="credits">Credits (CFU)</label>
                    <input type="number" id="credits" name="credits" placeholder="e.g. 6.0" step="0.5" min="1" max="30" value="{{ old('credits', $course->credits) }}">
                </div>

                <div class="form-group">
                    <label for="year">Academic Year *</label>
                    <select id="year" name="year" required>
                        <option value="1" {{ old('year', $course->year) == 1 ? 'selected' : '' }}>1st Year</option>
                        <option value="2" {{ old('year', $course->year) == 2 ? 'selected' : '' }}>2nd Year</option>
                        <option value="3" {{ old('year', $course->year) == 3 ? 'selected' : '' }}>3rd Year</option>
                        <option value="4" {{ old('year', $course->year) == 4 ? 'selected' : '' }}>4th Year</option>
                        <option value="5" {{ old('year', $course->year) == 5 ? 'selected' : '' }}>5th Year</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label>Associated Degree Programmes</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px; max-height: 180px; overflow-y: auto;">
                        @php
                            $selectedProgramIds = old('program_ids', $course->programs->pluck('id')->toArray());
                        @endphp
                        @foreach($programs as $program)
                            <label style="font-weight: normal; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.35rem; background: #f8fafc; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid var(--border); cursor: pointer;">
                                <input type="checkbox" name="program_ids[]" value="{{ $program->id }}" {{ in_array($program->id, $selectedProgramIds) ? 'checked' : '' }}>
                                {{ $program->name }} ({{ $program->degree_level ?? 'MSc' }})
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="margin-top: 0.5rem;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $course->is_active) ? 'checked' : '' }}>
                        Course is Active
                    </label>
                </div>
            </div>

            <div style="padding: 0 1.5rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Course</button>
            </div>
        </form>
    </div>
</div>
@endsection
