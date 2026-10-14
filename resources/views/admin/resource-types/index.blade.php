@extends('admin.layout')

@section('title', 'Manage Resource Types')

@section('content')
<div style="display: flex; flex-direction: column; gap: 2rem;">

    <!-- Create New Resource Type Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2>➕ Add Resource Type</h2>
                <p>Register a category for educational materials (e.g., Past Exams, Exercises, Summaries)</p>
            </div>
        </div>

        <form action="{{ route('admin.resource-types.store') }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label for="display_name">Display Name *</label>
                    <input type="text" id="display_name" name="display_name" placeholder="e.g. Past Exams" value="{{ old('display_name') }}" required>
                </div>

                <div class="form-group">
                    <label for="code">Unique Code (slug) *</label>
                    <input type="text" id="code" name="code" placeholder="e.g. past_exams" maxlength="50" value="{{ old('code') }}" required>
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order (Priority)</label>
                    <input type="number" id="sort_order" name="sort_order" placeholder="0" min="0" value="{{ old('sort_order', '0') }}">
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="margin-top: 1.25rem;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                        Active in Telegram Bot
                    </label>
                </div>
            </div>

            <div style="padding: 0 1.5rem 1.5rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">Save Resource Type</button>
            </div>
        </form>
    </div>

    <!-- Resource Types List Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2>🏷️ Resource Types List</h2>
                <p>Categories shown in Telegram when students browse a course</p>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Display Name</th>
                        <th>Code</th>
                        <th>Sort Order</th>
                        <th>Status</th>
                        <th>Files Count</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $type)
                        <tr>
                            <td>
                                <strong>{{ $type->display_name }}</strong>
                            </td>
                            <td>
                                <span class="badge badge-secondary" style="font-family: monospace;">{{ $type->code }}</span>
                            </td>
                            <td>
                                {{ $type->sort_order }}
                            </td>
                            <td>
                                @if($type->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-primary">{{ $type->resources_count }} files</span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem; align-items: center; justify-content: flex-end;">
                                    <a href="{{ route('admin.resource-types.edit', $type) }}" class="btn btn-secondary btn-sm">Edit</a>
                                    @if($type->resources_count === 0)
                                        <form action="{{ route('admin.resource-types.destroy', $type) }}" method="POST" onsubmit="return confirm('Delete {{ $type->display_name }}?');" style="display: inline;">
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
                                No resource types found. Use the form above to add one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
