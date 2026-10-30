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
                    @php
                        $availableYears = ($user && $user->program) 
                            ? $user->program->defaultYears() 
                            : [1, 2, 3];
                    @endphp
                    <select id="year" name="year" required>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ old('year', $course->year) == $y ? 'selected' : '' }}>
                                {{ $y }}{{ $y == 1 ? 'st' : ($y == 2 ? 'nd' : 'rd') }} Year
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    @php
                        $selectedProgramIds = old('program_ids', $course->programs->pluck('id')->toArray());
                        $hasBSc = $course->programs->contains(fn($p) => strtoupper($p->degree_level ?? '') === 'BSC');
                        $hasMSc = $course->programs->contains(fn($p) => strtoupper($p->degree_level ?? '') === 'MSC');
                        $initialDegreeLevel = ($hasMSc && !$hasBSc) ? 'MSc' : (($hasBSc && !$hasMSc) ? 'BSc' : 'all');
                    @endphp
                    <label style="font-weight: 600; color: var(--text-main); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <span>Degree Level / مقطع تحصیلی</span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">Filter programmes by degree level</span>
                    </label>
                    <div style="display: flex; gap: 0.75rem; margin-top: 0.35rem; flex-wrap: wrap;">
                        <label class="degree-level-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                            <input type="radio" name="degree_level_filter" value="all" class="degree-level-radio" {{ $initialDegreeLevel === 'all' ? 'checked' : '' }}>
                            <span>🌐 All Programmes</span>
                        </label>
                        <label class="degree-level-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                            <input type="radio" name="degree_level_filter" value="BSc" class="degree-level-radio" {{ $initialDegreeLevel === 'BSc' ? 'checked' : '' }}>
                            <span>🎓 Bachelor (BSc)</span>
                        </label>
                        <label class="degree-level-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                            <input type="radio" name="degree_level_filter" value="MSc" class="degree-level-radio" {{ $initialDegreeLevel === 'MSc' ? 'checked' : '' }}>
                            <span>🎓 Master (MSc)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label>Associated Degree Programmes</label>
                    <div id="programsContainer" style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px; max-height: 180px; overflow-y: auto;">
                        @foreach($programs as $program)
                            <label class="program-label-item" data-degree-level="{{ strtoupper($program->degree_level ?? 'MSC') }}" style="font-weight: normal; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.35rem; background: #f8fafc; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid var(--border); cursor: pointer;">
                                <input type="checkbox" class="program-checkbox" name="program_ids[]" value="{{ $program->id }}" data-degree-level="{{ strtoupper($program->degree_level ?? 'MSC') }}" {{ in_array($program->id, $selectedProgramIds) ? 'checked' : '' }}>
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
                    <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px; max-height: 180px; overflow-y: auto;">
                        @php
                            $selectedTrackIds = old('track_ids', $course->tracks->pluck('id')->toArray());
                            $allTracks = $programs->flatMap->tracks->unique('id');
                        @endphp
                        @forelse($allTracks as $track)
                            <label class="track-label-item" data-program-id="{{ $track->program_id }}" style="font-weight: normal; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.35rem; background: #faf5ff; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid #e9d5ff; cursor: pointer;">
                                <input type="checkbox" class="track-checkbox" name="track_ids[]" value="{{ $track->id }}" {{ in_array($track->id, $selectedTrackIds) ? 'checked' : '' }}>
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

    const activeRadio = document.querySelector('.degree-level-radio:checked');
    filterProgramsByDegreeLevel(activeRadio ? activeRadio.value : 'all');
});
</script>
@endpush
