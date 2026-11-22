@extends('admin.layout')

@section('title', $announcement->exists ? 'Edit announcement' : 'New announcement')

@section('content')
    <div class="card" style="max-width: 850px; margin-inline: auto;">
        <div class="card-header">
            <h1>{{ $announcement->exists ? 'Edit announcement' : 'New announcement' }}</h1>
            <a class="btn btn-secondary" href="{{ $announcement->exists ? route('admin.announcements.show', $announcement) : route('admin.announcements.index') }}">Cancel</a>
        </div>
        <form method="POST" class="form-grid" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}">
            @csrf
            @if ($announcement->exists)
                @method('PUT')
            @endif

            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="title">Title for admins</label>
                <input id="title" name="title" type="text" maxlength="150" value="{{ old('title', $announcement->title) }}" required>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="message">Telegram message</label>
                <textarea id="message" name="message" dir="auto" rows="7" maxlength="4096" required>{{ old('message', $announcement->message) }}</textarea>
                <input type="hidden" name="include_menu_button" value="0">
                <label class="checkbox-label" style="margin-top: 0;">
                    <input type="checkbox" name="include_menu_button" value="1" @checked(old('include_menu_button', $announcement->include_menu_button))>
                    Include an Open menu button
                </label>
            </div>

            <div style="grid-column: 1 / -1; border-top: 1px solid var(--border); padding-top: 1rem;">
                <h2>Audience filters</h2>
                <p style="color: var(--text-muted);">The bot finds recipients when you queue the announcement. With every filter blank, the audience is all unblocked students with a linked Telegram account.</p>
            </div>
            <fieldset style="grid-column: 1 / -1; border: 0;">
                <legend>Preferred languages</legend>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                    @php($selected = session()->hasOldInput() ? (array) old('languages', []) : $announcement->languages)
                    @foreach (\App\Models\Announcement::LANGUAGES as $code => $language)
                        <label class="checkbox-label" style="margin-top: 0.35rem;">
                            <input type="checkbox" name="languages[]" value="{{ $code }}" @checked(in_array($code, $selected, true))>
                            {{ $language }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <div class="form-group">
                <label for="minimum_submissions">Minimum submissions with files</label>
                <input id="minimum_submissions" name="minimum_submissions" type="number" min="0" step="1" value="{{ old('minimum_submissions', $announcement->minimum_submissions ?: '') }}" placeholder="Any">
            </div>
            <div class="form-group">
                <label for="joined_before">Joined before ({{ config('app.timezone') }})</label>
                <input id="joined_before" name="joined_before" type="datetime-local" value="{{ old('joined_before', $announcement->joined_before?->format('Y-m-d\TH:i')) }}" style="padding: 0.55rem 0.75rem; border: 1px solid var(--border); border-radius: 8px;">
            </div>
            <div style="grid-column: 1 / -1;">
                <input type="hidden" name="without_program" value="0">
                <label class="checkbox-label" style="margin-top: 0;">
                    <input type="checkbox" name="without_program" value="1" @checked(old('without_program', $announcement->without_program))>
                    Only students with no major assigned
                </label>
            </div>
            <div style="grid-column: 1 / -1;">
                <button class="btn btn-primary" type="submit">Save draft</button>
            </div>
        </form>
    </div>
@endsection
