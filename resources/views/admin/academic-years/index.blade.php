@extends('admin.layout')

@section('title', 'Manage Academic Years')

@section('content')
<div style="display: flex; flex-direction: column; gap: 2rem;">

    <!-- Create New Academic Year Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2>➕ Add Academic Year</h2>
                <p>Register a new academic calendar year (e.g., 2024/2025)</p>
            </div>
        </div>

        <form action="{{ route('admin.academic-years.store') }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label for="label">Year Label *</label>
                    <input type="text" id="label" name="label" placeholder="2024/2025" maxlength="9" value="{{ old('label') }}" required>
                </div>

                <div class="form-group">
                    <label for="starts_on">Start Date *</label>
                    <input type="date" id="starts_on" name="starts_on" value="{{ old('starts_on', date('Y').'-10-01') }}" required>
                </div>

                <div class="form-group">
                    <label for="ends_on">End Date *</label>
                    <input type="date" id="ends_on" name="ends_on" value="{{ old('ends_on', (date('Y')+1).'-09-30') }}" required>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_current" value="1" {{ old('is_current') ? 'checked' : '' }}>
                        Set as Current Academic Year
                    </label>
                </div>
            </div>

            <div style="padding: 0 1.5rem 1.5rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">Save Academic Year</button>
            </div>
        </form>
    </div>

    <!-- Academic Years List Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2>📅 Academic Years List</h2>
                <p>All registered academic years in the system</p>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Label</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Resources</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($years as $year)
                        <tr>
                            <td>
                                <strong>{{ $year->label }}</strong>
                            </td>
                            <td>
                                {{ $year->starts_on?->format('d M Y') }} &rarr; {{ $year->ends_on?->format('d M Y') }}
                            </td>
                            <td>
                                @if($year->is_current)
                                    <span class="badge badge-success">⭐ Current Year</span>
                                @else
                                    <form action="{{ route('admin.academic-years.current', $year) }}" method="POST" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Set as current">Make Current</button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ $year->resources_count }} files</span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem; align-items: center; justify-content: flex-end;">
                                    <a href="{{ route('admin.academic-years.edit', $year) }}" class="btn btn-secondary btn-sm">Edit</a>
                                    @if($year->resources_count === 0)
                                        <form action="{{ route('admin.academic-years.destroy', $year) }}" method="POST" onsubmit="return confirm('Delete academic year {{ $year->label }}?');" style="display: inline;">
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
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                No academic years found. Use the form above to add one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
