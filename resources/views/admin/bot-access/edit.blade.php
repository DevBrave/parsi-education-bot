@extends('admin.layout')

@section('title', 'Bot Access')

@section('content')
    <div class="card" style="max-width: 720px; margin: 0 auto; padding: 2rem;">
        <h1>Bot Access</h1>
        <p style="margin: 1rem 0;">Current access: <strong>{{ $restricted ? 'Staff only' : 'Public' }}</strong></p>
        @if (!$restricted && $access['mode'] === 'staff_only')
            <p>The restriction has expired. Public access has resumed automatically.</p>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('admin.bot-access.update') }}"
            style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
            @csrf
            @method('PUT')
            <label for="mode">Access mode</label>
            <select id="mode" name="mode">
                <option value="public" @selected(old('mode', $restricted ? 'staff_only' : 'public') === 'public')>Public — everyone can use the bot</option>
                <option value="staff_only" @selected(old('mode', $restricted ? 'staff_only' : 'public') === 'staff_only')>Staff only — until the expiry below</option>
            </select>
            <label for="timezone">Timezone</label>
            <input id="timezone" name="timezone" value="{{ old('timezone', $access['timezone']) }}" placeholder="Europe/Rome" required>
            <label for="restricted_until">Restriction ends (in the timezone above)</label>
            <input type="datetime-local" id="restricted_until" name="restricted_until" value="{{ old('restricted_until', $untilLocal) }}">
            <p>Staff keep access. Messages, file uploads, buttons, and inline searches are restricted for everyone else.
                Public access resumes automatically at the expiry. Select Public to reopen immediately.</p>
            <button type="submit" class="btn btn-primary">Save access settings</button>
        </form>
    </div>
@endsection
