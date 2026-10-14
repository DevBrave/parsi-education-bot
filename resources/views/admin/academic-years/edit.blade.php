@extends('admin.layout')

@section('title', 'Edit Academic Year')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>✏️ Edit Academic Year: {{ $academicYear->label }}</h2>
                <p>Update academic calendar details and active status</p>
            </div>
            <a href="{{ route('admin.academic-years.index') }}" class="btn btn-secondary btn-sm">
                &larr; Back to List
            </a>
        </div>

        <form action="{{ route('admin.academic-years.update', $academicYear) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group">
                    <label for="label">Year Label *</label>
                    <input type="text" id="label" name="label" placeholder="2024/2025" maxlength="9" value="{{ old('label', $academicYear->label) }}" required>
                </div>

                <div class="form-group">
                    <label for="starts_on">Start Date *</label>
                    <input type="date" id="starts_on" name="starts_on" value="{{ old('starts_on', $academicYear->starts_on?->format('Y-m-d')) }}" required>
                </div>

                <div class="form-group">
                    <label for="ends_on">End Date *</label>
                    <input type="date" id="ends_on" name="ends_on" value="{{ old('ends_on', $academicYear->ends_on?->format('Y-m-d')) }}" required>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_current" value="1" {{ old('is_current', $academicYear->is_current) ? 'checked' : '' }}>
                        Set as Current Academic Year
                    </label>
                </div>
            </div>

            <div style="padding: 0 1.5rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="{{ route('admin.academic-years.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Academic Year</button>
            </div>
        </form>
    </div>
</div>
@endsection
