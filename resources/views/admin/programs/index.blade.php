@extends('admin.layout')

@section('title', 'Manage Degree Programmes')

@section('content')
    <div style="display: flex; flex-direction: column; gap: 2rem;">

        <!-- Create New Programme Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>➕ Add Degree Programme</h2>
                    <p>Register an academic study track or degree programme (e.g., Computer Engineering - MSc)</p>
                </div>
            </div>

            <form action="{{ route('admin.programs.store') }}" method="POST">
                @csrf
                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="name">Programme Name *</label>
                        <input type="text" id="name" name="name" placeholder="e.g. Computer Engineering"
                            value="{{ old('name') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="degree_level">Degree Level *</label>
                        <select id="degree_level" name="degree_level" required>
                            <option value="BSc" {{ old('degree_level') === 'BSc' ? 'selected' : '' }}>BSc (Bachelor's
                                Degree / Laurea Triennale)</option>
                            <option value="MSc" {{ old('degree_level', 'MSc') === 'MSc' ? 'selected' : '' }}>MSc
                                (Master's Degree / Laurea Magistrale)</option>
                            <option value="PhD" {{ old('degree_level') === 'PhD' ? 'selected' : '' }}>PhD (Doctorate /
                                Dottorato)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="university">University</label>
                        <input type="text" id="university" name="university" placeholder="Politecnico di Torino"
                            value="{{ old('university', 'Politecnico di Torino') }}">
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label" style="margin-top: 1.25rem;">
                            <input type="checkbox" name="is_active" value="1"
                                {{ old('is_active', true) ? 'checked' : '' }}>
                            Programme is Active
                        </label>
                    </div>
                </div>

                <div style="padding: 0 1.5rem 1.5rem; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Add Programme</button>
                </div>
            </form>
        </div>

        <!-- Programmes List Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h2>🎓 Degree Programmes List</h2>
                    <p>All registered study programmes and their course associations</p>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Programme Name</th>
                            <th>Level</th>
                            <th>University</th>
                            <th>Status</th>
                            <th>Courses</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programs as $program)
                            <tr>
                                <td>
                                    <strong>{{ $program->name }}</strong>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $program->degree_level }}</span>
                                </td>
                                <td>
                                    <span
                                        style="color: var(--text-muted); font-size: 0.85rem;">{{ $program->university }}</span>
                                </td>
                                <td>
                                    @if ($program->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ $program->courses_count }} courses</span>
                                </td>
                                <td style="text-align: right;">
                                    <div
                                        style="display: inline-flex; gap: 0.4rem; align-items: center; justify-content: flex-end;">
                                        <a href="{{ route('admin.programs.edit', $program) }}"
                                            class="btn btn-secondary btn-sm">Edit</a>
                                        @if ($program->courses_count === 0)
                                            <form action="{{ route('admin.programs.destroy', $program) }}" method="POST"
                                                onsubmit="return confirm('Delete programme {{ $program->name }} ({{ $program->degree_level }})?');"
                                                style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        @else
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">In use</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    No degree programmes found. Use the form above to add one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
