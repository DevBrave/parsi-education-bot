@extends('admin.layout')

@section('title', 'Edit Degree Programme')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>✏️ Edit Degree Programme: {{ $program->name }}</h2>
                <p>Modify programme title, study level, and status</p>
            </div>
            <a href="{{ route('admin.programs.index') }}" class="btn btn-secondary btn-sm">
                &larr; Back to List
            </a>
        </div>

        <form action="{{ route('admin.programs.update', $program) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label for="name">Programme Name *</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Computer Engineering" value="{{ old('name', $program->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="degree_level">Degree Level *</label>
                    <select id="degree_level" name="degree_level" required>
                        <option value="MSc" {{ old('degree_level', $program->degree_level) === 'MSc' ? 'selected' : '' }}>MSc (Master's Degree / Laurea Magistrale)</option>
                        <option value="BSc" {{ old('degree_level', $program->degree_level) === 'BSc' ? 'selected' : '' }}>BSc (Bachelor's Degree / Laurea Triennale)</option>
                        <option value="PhD" {{ old('degree_level', $program->degree_level) === 'PhD' ? 'selected' : '' }}>PhD (Doctorate / Dottorato)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="university">University</label>
                    <input type="text" id="university" name="university" placeholder="Politecnico di Torino" value="{{ old('university', $program->university) }}">
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="margin-top: 1.25rem;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $program->is_active) ? 'checked' : '' }}>
                        Programme is Active
                    </label>
                </div>
            </div>

            <div style="padding: 0 1.5rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="{{ route('admin.programs.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Programme</button>
            </div>
        </form>
    </div>
</div>
@endsection
