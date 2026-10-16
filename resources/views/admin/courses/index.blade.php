@extends('admin.layout')

@section('title', 'Manage Courses')

@section('content')
<div style="display: flex; flex-direction: column; gap: 2rem;">

    <!-- Create New Course Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2>➕ Add New Course</h2>
                <p>Register a university course and associate it with degree programmes</p>
            </div>
        </div>

        <form action="{{ route('admin.courses.store') }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label for="name">Course Name *</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Computer Architecture" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="course_code">Course Code *</label>
                    <input type="text" id="course_code" name="course_code" placeholder="e.g. 01TXYOV" maxlength="30" value="{{ old('course_code') }}" required>
                </div>

                <div class="form-group">
                    <label for="credits">Credits (CFU)</label>
                    <input type="number" id="credits" name="credits" placeholder="e.g. 6.0" step="1" min="1" max="30" value="{{ old('credits', '6.0') }}">
                </div>

                <div class="form-group">
                    <label for="year">Academic Year *</label>
                    <select id="year" name="year" required>
                        <option value="1" {{ old('year', '1') == '1' ? 'selected' : '' }}>1st Year</option>
                        <option value="2" {{ old('year') == '2' ? 'selected' : '' }}>2nd Year</option>
                        <option value="3" {{ old('year') == '3' ? 'selected' : '' }}>3rd Year</option>
                        <option value="4" {{ old('year') == '4' ? 'selected' : '' }}>4th Year</option>
                        <option value="5" {{ old('year') == '5' ? 'selected' : '' }}>5th Year</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label>Associated Degree Programmes</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px; max-height: 140px; overflow-y: auto;">
                        @foreach($programs as $program)
                            <label style="font-weight: normal; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.35rem; background: #f8fafc; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid var(--border);">
                                <input type="checkbox" name="program_ids[]" value="{{ $program->id }}" {{ in_array($program->id, old('program_ids', [])) ? 'checked' : '' }}>
                                {{ $program->name }} ({{ $program->degree_level ?? 'MSc' }})
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="margin-top: 0.5rem;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                        Active in Telegram Bot
                    </label>
                </div>
            </div>

            <div style="padding: 0 1.5rem 1.5rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">Save Course</button>
            </div>
        </form>
    </div>

    <!-- Courses List Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2>📖 Courses List ({{ $courses->count() }})</h2>
                <p>All active and registered courses available in the bot</p>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Year</th>
                        <th>CFU</th>
                        <th>Programmes</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                        <tr>
                            <td>
                                <span class="badge badge-secondary" style="font-family: monospace;">{{ $course->course_code }}</span>
                            </td>
                            <td>
                                <strong>{{ $course->name }}</strong>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $course->year_label }}</span>
                            </td>
                            <td>
                                {{ $course->credits ? $course->credits . ' CFU' : '—' }}
                            </td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                    @forelse($course->programs as $prog)
                                        <span class="badge badge-primary">{{ $prog->name }}</span>
                                    @empty
                                        <span style="color: var(--text-muted); font-size: 0.75rem;">None</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if($course->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem; align-items: center; justify-content: flex-end;">
                                    <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-secondary btn-sm">Edit</a>
                                    <form action="{{ route('admin.courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Delete course {{ $course->name }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                No courses found. Use the form above to add one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
