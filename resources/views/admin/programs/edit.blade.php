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

                <div class="form-group" style="grid-column: span 2;">
                    <label style="font-weight: 600; color: var(--text-main);">Degree Level / مقطع تحصیلی *</label>
                    <div style="display: flex; gap: 0.75rem; margin-top: 0.35rem; flex-wrap: wrap;">
                        <label class="prog-degree-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                            <input type="radio" name="degree_level" value="BSc" class="prog-degree-radio" {{ old('degree_level', $program->degree_level) === 'BSc' ? 'checked' : '' }} required>
                            <span>🎓 Bachelor (BSc)</span>
                        </label>
                        <label class="prog-degree-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                            <input type="radio" name="degree_level" value="MSc" class="prog-degree-radio" {{ old('degree_level', $program->degree_level) === 'MSc' ? 'checked' : '' }} required>
                            <span>🎓 Master (MSc)</span>
                        </label>
                        <label class="prog-degree-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                            <input type="radio" name="degree_level" value="PhD" class="prog-degree-radio" {{ old('degree_level', $program->degree_level) === 'PhD' ? 'checked' : '' }} required>
                            <span>🎓 Doctorate (PhD)</span>
                        </label>
                    </div>
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

                <div class="form-group" id="programTracksWrapper" style="grid-column: span 2; display: none;">
                    <label style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <span style="font-weight: 600;">Specialization Tracks / گرایش‌ها <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">(Optional inner majors for Master degrees)</span></span>
                        <button type="button" id="btnAddTrack" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">➕ Add Track</button>
                    </label>
                    <div id="tracksListContainer" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                        @foreach($program->tracks as $idx => $tr)
                            <div class="track-row" style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="hidden" name="tracks[{{ $idx }}][id]" value="{{ $tr->id }}">
                                <input type="text" name="tracks[{{ $idx }}][name]" value="{{ old("tracks.{$idx}.name", $tr->name) }}" placeholder="Track Name (e.g. Artificial Intelligence)" style="flex: 2; font-size: 0.8125rem;" required>
                                <input type="text" name="tracks[{{ $idx }}][code]" value="{{ old("tracks.{$idx}.code", $tr->code) }}" placeholder="Code (e.g. AIDA)" style="flex: 1; max-width: 130px; font-size: 0.8125rem; text-transform: uppercase;">
                                <button type="button" class="btn btn-danger btn-sm btn-remove-track" style="padding: 0.4rem 0.65rem;" title="Remove Track">✕</button>
                            </div>
                        @endforeach
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.35rem;">Tracks will appear in the Telegram Bot as specialization options (e.g. AIDA, AICPS, etc.) for students in this Master's degree.</small>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const degreeRadios = document.querySelectorAll('.prog-degree-radio');
    const tracksWrapper = document.getElementById('programTracksWrapper');
    const tracksContainer = document.getElementById('tracksListContainer');
    const btnAddTrack = document.getElementById('btnAddTrack');
    let trackIndex = {{ $program->tracks->count() }};

    function updateTracksVisibility() {
        const checkedRadio = document.querySelector('.prog-degree-radio:checked');
        const isMaster = checkedRadio && checkedRadio.value.toUpperCase() === 'MSC';

        // Highlight active radio button
        document.querySelectorAll('.prog-degree-btn').forEach(btn => {
            const r = btn.querySelector('.prog-degree-radio');
            if (r && r.checked) {
                btn.style.borderColor = 'var(--primary, #3b82f6)';
                btn.style.background = '#eff6ff';
                btn.style.boxShadow = '0 0 0 1px var(--primary, #3b82f6)';
            } else {
                btn.style.borderColor = 'var(--border, #e2e8f0)';
                btn.style.background = '#fff';
                btn.style.boxShadow = 'none';
            }
        });

        if (tracksWrapper) {
            tracksWrapper.style.display = isMaster ? 'block' : 'none';
        }
    }

    if (btnAddTrack && tracksContainer) {
        btnAddTrack.addEventListener('click', function () {
            const idx = trackIndex++;
            const row = document.createElement('div');
            row.className = 'track-row';
            row.style.cssText = 'display: flex; gap: 0.5rem; align-items: center;';
            row.innerHTML = `
                <input type="text" name="tracks[${idx}][name]" placeholder="Track Name (e.g. Artificial Intelligence)" style="flex: 2; font-size: 0.8125rem;" required>
                <input type="text" name="tracks[${idx}][code]" placeholder="Code (e.g. AIDA)" style="flex: 1; max-width: 130px; font-size: 0.8125rem; text-transform: uppercase;">
                <button type="button" class="btn btn-danger btn-sm btn-remove-track" style="padding: 0.4rem 0.65rem;" title="Remove Track">✕</button>
            `;
            tracksContainer.appendChild(row);
        });
    }

    if (tracksContainer) {
        tracksContainer.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-remove-track')) {
                e.target.closest('.track-row').remove();
            }
        });
    }

    degreeRadios.forEach(r => {
        r.addEventListener('change', updateTracksVisibility);
    });

    updateTracksVisibility();
});
</script>
@endpush
