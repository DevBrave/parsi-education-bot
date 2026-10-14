@extends('admin.layout')

@section('title', 'Edit Resource Type')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>✏️ Edit Resource Type: {{ $resourceType->display_name }}</h2>
                <p>Modify category information, code, and display ordering</p>
            </div>
            <a href="{{ route('admin.resource-types.index') }}" class="btn btn-secondary btn-sm">
                &larr; Back to List
            </a>
        </div>

        <form action="{{ route('admin.resource-types.update', $resourceType) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group">
                    <label for="display_name">Display Name *</label>
                    <input type="text" id="display_name" name="display_name" placeholder="e.g. Past Exams" value="{{ old('display_name', $resourceType->display_name) }}" required>
                </div>

                <div class="form-group">
                    <label for="code">Unique Code *</label>
                    <input type="text" id="code" name="code" placeholder="e.g. past_exam" maxlength="50" value="{{ old('code', $resourceType->code) }}" required>
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" placeholder="0" min="0" value="{{ old('sort_order', $resourceType->sort_order) }}">
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="margin-top: 1.25rem;">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $resourceType->is_active) ? 'checked' : '' }}>
                        Type is Active
                    </label>
                </div>
            </div>

            <div style="padding: 0 1.5rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="{{ route('admin.resource-types.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Resource Type</button>
            </div>
        </form>
    </div>
</div>
@endsection
