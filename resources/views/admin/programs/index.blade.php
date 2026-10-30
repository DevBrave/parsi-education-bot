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

                    <div class="form-group" style="grid-column: span 2;">
                        <label style="font-weight: 600; color: var(--text-main);">Degree Level / مقطع تحصیلی *</label>
                        <div style="display: flex; gap: 0.75rem; margin-top: 0.35rem; flex-wrap: wrap;">
                            <label class="prog-degree-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                                <input type="radio" name="degree_level" value="BSc" class="prog-degree-radio" {{ old('degree_level') === 'BSc' ? 'checked' : '' }} required>
                                <span>🎓 Bachelor (BSc)</span>
                            </label>
                            <label class="prog-degree-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                                <input type="radio" name="degree_level" value="MSc" class="prog-degree-radio" {{ old('degree_level', 'MSc') === 'MSc' ? 'checked' : '' }} required>
                                <span>🎓 Master (MSc)</span>
                            </label>
                            <label class="prog-degree-btn" style="flex: 1; min-width: 140px; display: flex; align-items: center; gap: 0.5rem; padding: 0.55rem 0.9rem; border: 1.5px solid var(--border); border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.8125rem; font-weight: 500; transition: all 0.15s ease;">
                                <input type="radio" name="degree_level" value="PhD" class="prog-degree-radio" {{ old('degree_level') === 'PhD' ? 'checked' : '' }} required>
                                <span>🎓 Doctorate (PhD)</span>
                            </label>
                        </div>
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

                    <div class="form-group" id="programTracksWrapper" style="grid-column: span 2; display: none;">
                        <label style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                            <span style="font-weight: 600;">Specialization Tracks / گرایش‌ها <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: normal;">(Optional inner majors for Master degrees)</span></span>
                            <button type="button" id="btnAddTrack" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">➕ Add Track</button>
                        </label>
                        <div id="tracksListContainer" style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                            @php
                                $oldTracks = old('tracks', []);
                            @endphp
                            @foreach($oldTracks as $idx => $tr)
                                <div class="track-row" style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="text" name="tracks[{{ $idx }}][name]" value="{{ $tr['name'] ?? '' }}" placeholder="Track Name (e.g. Artificial Intelligence and Data Analytics)" style="flex: 2; font-size: 0.8125rem;" required>
                                    <input type="text" name="tracks[{{ $idx }}][code]" value="{{ $tr['code'] ?? '' }}" placeholder="Code (e.g. AIDA)" style="flex: 1; max-width: 130px; font-size: 0.8125rem; text-transform: uppercase;">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-track" style="padding: 0.4rem 0.65rem;" title="Remove Track">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.35rem;">Tracks will appear in the Telegram Bot as specialization options (e.g. AIDA, AICPS, etc.) for students in this Master's degree.</small>
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
                                    @if($program->tracks->isNotEmpty())
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.35rem;">
                                            @foreach($program->tracks as $tr)
                                                <span class="badge" style="background: rgba(147, 51, 234, 0.12); color: #7e22ce; font-size: 0.7rem;">🎯 {{ $tr->code ?? $tr->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const degreeRadios = document.querySelectorAll('.prog-degree-radio');
    const tracksWrapper = document.getElementById('programTracksWrapper');
    const tracksContainer = document.getElementById('tracksListContainer');
    const btnAddTrack = document.getElementById('btnAddTrack');
    let trackIndex = {{ count(old('tracks', [])) }};

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
