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
                    @php
                        $availableYears = ($user && $user->program) 
                            ? $user->program->defaultYears() 
                            : [1, 2, 3];
                    @endphp
                    <select id="year" name="year" required>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ old('year', '1') == $y ? 'selected' : '' }}>
                                {{ $y }}{{ $y == 1 ? 'st' : ($y == 2 ? 'nd' : 'rd') }} Year
                            </option>
                        @endforeach
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

                <div class="form-group" style="grid-column: span 2;">
                    <label>Specialization Tracks / گرایش‌ها (Leave empty if core course for all)</label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px; max-height: 140px; overflow-y: auto;">
                        @php
                            $allTracks = $programs->flatMap->tracks->unique('id');
                        @endphp
                        @forelse($allTracks as $track)
                            <label style="font-weight: normal; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.35rem; background: #faf5ff; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid #e9d5ff; cursor: pointer;">
                                <input type="checkbox" name="track_ids[]" value="{{ $track->id }}" {{ in_array($track->id, old('track_ids', [])) ? 'checked' : '' }}>
                                🎯 {{ $track->name }} ({{ $track->code ?? $track->program?->name }})
                            </label>
                        @empty
                            <span style="color: var(--text-muted); font-size: 0.8125rem; padding: 0.25rem;">No specialization tracks defined yet.</span>
                        @endforelse
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">Courses with no tracks selected will be treated as common core courses and visible to all students in the degree.</small>
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
        <div class="card-header" style="flex-wrap: wrap; gap: 1rem; justify-content: space-between; align-items: center;">
            <div>
                <h2>📖 Courses List ({{ $courses->count() }})</h2>
                <p>All active and registered courses available in the bot</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div style="padding: 1rem 1.5rem; background: #fafbfc; border-bottom: 1px solid var(--border);">
            <form method="GET" action="{{ route('admin.courses.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.85rem; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="filter_search" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">Search</label>
                    <input type="text" id="filter_search" name="search" placeholder="Course name or code..." value="{{ request('search') }}">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="filter_program" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">Degree Programme (Major)</label>
                    <select id="filter_program" name="program_id">
                        <option value="">All Programmes</option>
                        @foreach($programs as $p)
                            <option value="{{ $p->id }}" {{ request('program_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->degree_level ?? 'MSc' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="filter_year" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">Academic Year</label>
                    <select id="filter_year" name="year">
                        <option value="">All Years</option>
                        @for($y = 1; $y <= 5; $y++)
                            <option value="{{ $y }}" {{ request('year') == (string) $y ? 'selected' : '' }}>
                                {{ $y }}{{ $y == 1 ? 'st' : ($y == 2 ? 'nd' : ($y == 3 ? 'rd' : 'th')) }} Year
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="filter_status" style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">Status</label>
                    <select id="filter_status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                    @if(request()->anyFilled(['search', 'program_id', 'year', 'status']))
                        <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Reset</a>
                    @endif
                </div>
            </form>
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
                                <span class="badge badge-info">{{ $course->year }}</span>
                            </td>
                            <td>
                                {{ $course->credits ? $course->credits  : '—' }}
                            </td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                    @forelse($course->programs as $prog)
                                        <span class="badge badge-primary">{{ $prog->name }}</span>
                                    @empty
                                        <span style="color: var(--text-muted); font-size: 0.75rem;">None</span>
                                    @endforelse
                                </div>
                                @if($course->tracks->isNotEmpty())
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.35rem;">
                                        @foreach($course->tracks as $track)
                                            <span class="badge" style="background: rgba(147, 51, 234, 0.12); color: #7e22ce; border: 1px solid rgba(147, 51, 234, 0.25); font-size: 0.7rem;">🎯 {{ $track->code ?? $track->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
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
