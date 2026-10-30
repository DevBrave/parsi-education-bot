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
                    <label style="font-weight: 600; color: var(--text-main); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <span>Degree Level / مقطع تحصیلی</span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Filter programmes by degree level</span>
                    </label>
                    <div style="display: flex; gap: 0.75rem; margin-top: 0.35rem; flex-wrap: wrap;">
                        <label class="degree-level-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                            <input type="radio" name="degree_level_filter" value="all" class="degree-level-radio" checked>
                            <span>🌐 All Programmes</span>
                        </label>
                        <label class="degree-level-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                            <input type="radio" name="degree_level_filter" value="BSc" class="degree-level-radio">
                            <span>🎓 Bachelor (BSc)</span>
                        </label>
                        <label class="degree-level-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                            <input type="radio" name="degree_level_filter" value="MSc" class="degree-level-radio">
                            <span>🎓 Master (MSc)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label>Associated Degree Programmes</label>
                    <div id="programsContainer" style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px; max-height: 140px; overflow-y: auto;">
                        @foreach($programs as $program)
                            <label class="program-label-item" data-degree-level="{{ strtoupper($program->degree_level ?? 'MSC') }}" style="font-weight: normal; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.35rem; background: #f8fafc; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid var(--border); cursor: pointer;">
                                <input type="checkbox" class="program-checkbox" name="program_ids[]" value="{{ $program->id }}" data-degree-level="{{ strtoupper($program->degree_level ?? 'MSC') }}" {{ in_array($program->id, old('program_ids', [])) ? 'checked' : '' }}>
                                {{ $program->name }} ({{ $program->degree_level ?? 'MSc' }})
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="form-group" id="tracksSectionGroup" style="grid-column: span 2; display: none;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span>Specialization Tracks / گرایش‌ها <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">(Leave empty if core course for all)</span></span>
                        <span class="badge" style="background: rgba(147, 51, 234, 0.12); color: #7e22ce; font-size: 0.7rem; padding: 0.15rem 0.45rem;">Master's (MSc) Only</span>
                    </label>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px; max-height: 140px; overflow-y: auto;">
                        @php
                            $allTracks = $programs->flatMap->tracks->unique('id');
                        @endphp
                        @forelse($allTracks as $track)
                            <label class="track-label-item" data-program-id="{{ $track->program_id }}" style="font-weight: normal; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.35rem; background: #faf5ff; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid #e9d5ff; cursor: pointer;">
                                <input type="checkbox" class="track-checkbox" name="track_ids[]" value="{{ $track->id }}" {{ in_array($track->id, old('track_ids', [])) ? 'checked' : '' }}>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const degreeRadios = document.querySelectorAll('.degree-level-radio');
    const programLabels = document.querySelectorAll('.program-label-item');
    const programCheckboxes = document.querySelectorAll('.program-checkbox');
    const tracksSectionGroup = document.getElementById('tracksSectionGroup');
    const trackLabels = document.querySelectorAll('.track-label-item');
    const yearSelect = document.getElementById('year');

    function syncSpecializationTracks() {
        if (!tracksSectionGroup) return;

        const checkedPrograms = Array.from(programCheckboxes).filter(cb => cb.checked);
        
        // Check if at least one selected program is a Master (MSc)
        const hasMasterSelected = checkedPrograms.some(cb => {
            const level = (cb.dataset.degreeLevel || '').trim().toUpperCase();
            return level === 'MSC' || level === 'MASTER';
        });

        const selectedProgramIds = checkedPrograms.map(cb => String(cb.value));

        if (hasMasterSelected) {
            tracksSectionGroup.style.display = 'block';
            
            // Only show tracks that belong to selected Master programs
            trackLabels.forEach(label => {
                const progId = String(label.dataset.programId || '');
                const cb = label.querySelector('.track-checkbox');
                if (selectedProgramIds.includes(progId)) {
                    label.style.display = 'flex';
                    if (cb) cb.disabled = false;
                } else {
                    label.style.display = 'none';
                    if (cb) {
                        cb.checked = false;
                        cb.disabled = true;
                    }
                }
            });
        } else {
            // Bachelor-only or no program selected: hide tracks and uncheck/disable them
            tracksSectionGroup.style.display = 'none';
            trackLabels.forEach(label => {
                const cb = label.querySelector('.track-checkbox');
                if (cb) {
                    cb.checked = false;
                    cb.disabled = true;
                }
            });
        }
    }

    function filterProgramsByDegreeLevel(selectedLevel) {
        // Highlight active radio button visually
        document.querySelectorAll('.degree-level-btn').forEach(btn => {
            const radio = btn.querySelector('.degree-level-radio');
            if (radio && radio.checked) {
                btn.style.borderColor = 'var(--primary, #3b82f6)';
                btn.style.background = '#eff6ff';
                btn.style.boxShadow = '0 0 0 1px var(--primary, #3b82f6)';
            } else {
                btn.style.borderColor = 'var(--border, #e2e8f0)';
                btn.style.background = '#fff';
                btn.style.boxShadow = 'none';
            }
        });

        // Filter the visible program items
        programLabels.forEach(label => {
            const level = (label.dataset.degreeLevel || '').trim().toUpperCase();
            if (selectedLevel === 'all' || level === selectedLevel.toUpperCase()) {
                label.style.display = 'flex';
            } else {
                label.style.display = 'none';
                const cb = label.querySelector('.program-checkbox');
                if (cb && cb.checked) {
                    cb.checked = false;
                }
            }
        });

        // Adjust Academic Year options based on degree level
        if (yearSelect) {
            const year3Option = yearSelect.querySelector('option[value="3"]');
            if (selectedLevel.toUpperCase() === 'MSC') {
                if (year3Option) {
                    year3Option.style.display = 'none';
                    if (yearSelect.value === '3') {
                        yearSelect.value = '2';
                    }
                }
            } else {
                if (year3Option) {
                    year3Option.style.display = '';
                }
            }
        }

        // Re-evaluate tracks
        syncSpecializationTracks();
    }

    degreeRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            filterProgramsByDegreeLevel(this.value);
        });
    });

    programCheckboxes.forEach(cb => {
        cb.addEventListener('change', syncSpecializationTracks);
    });

    // Detect if initial programs are all BSc or all MSc
    const initialChecked = Array.from(programCheckboxes).filter(cb => cb.checked);
    if (initialChecked.length > 0) {
        const allBSc = initialChecked.every(cb => (cb.dataset.degreeLevel || '').toUpperCase() === 'BSC');
        const allMSc = initialChecked.every(cb => (cb.dataset.degreeLevel || '').toUpperCase() === 'MSC');
        if (allBSc) {
            const r = document.querySelector('.degree-level-radio[value="BSc"]');
            if (r) r.checked = true;
        } else if (allMSc) {
            const r = document.querySelector('.degree-level-radio[value="MSc"]');
            if (r) r.checked = true;
        }
    }

    const activeRadio = document.querySelector('.degree-level-radio:checked');
    filterProgramsByDegreeLevel(activeRadio ? activeRadio.value : 'all');
});
</script>
@endpush
