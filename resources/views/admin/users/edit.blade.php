@extends('admin.layout')

@section('title', 'Edit User Role & Permissions')

@section('content')
<div style="max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- Breadcrumb / Header -->
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="{{ route('admin.users.index') }}" style="color: #4f46e5; text-decoration: none; font-size: 0.8125rem; font-weight: 600;">
                ← Back to Users & Roles
            </a>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-top: 0.25rem;">
                Edit User: {{ $user->first_name }} {{ $user->last_name }}
            </h1>
        </div>
        <span style="font-size: 0.8125rem; color: var(--text-muted); font-family: monospace;">
            User ID: #{{ $user->id }}
        </span>
    </div>

    @if($errors->any())
        <div class="alert alert-error" style="padding: 1rem; border-radius: 8px; font-size: 0.875rem;">
            <strong>Please resolve the following issues:</strong>
            <ul style="margin-top: 0.5rem; margin-left: 1.25rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="padding: 2rem;">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" style="display: flex; flex-direction: column; gap: 1.5rem;">
            @csrf
            @method('PUT')

            <!-- 1. Telegram Identity (Read-only reference) -->
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 1rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Telegram Username</span>
                    <div style="font-weight: 600; color: var(--text-main); margin-top: 0.2rem;">
                        {{ $user->username ? '@' . $user->username : 'None' }}
                    </div>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Telegram User ID</span>
                    <div style="font-weight: 600; font-family: monospace; color: var(--text-main); margin-top: 0.2rem;">
                        {{ $user->telegram_user_id ? '#' . $user->telegram_user_id : 'Web user only' }}
                    </div>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Member Since</span>
                    <div style="font-size: 0.825rem; color: var(--text-main); margin-top: 0.2rem;">
                        {{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}
                    </div>
                </div>
            </div>

            <!-- 2. Role Selection -->
            <div>
                <label for="role" style="display: block; font-weight: 600; color: var(--text-main); margin-bottom: 0.35rem;">
                    Platform Role <span style="color: #ef4444;">*</span>
                </label>
                <select name="role" id="roleSelect" required style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem; background: var(--bg-card); color: var(--text-main);">
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>
                        👑 Super Admin (Full access to all majors, courses, approvals, deletions, and user management)
                    </option>
                    <option value="moderator" {{ old('role', $user->role) === 'moderator' ? 'selected' : '' }}>
                        🛡️ Moderator (Can approve, reject, and edit materials across ALL majors, but cannot delete courses or manage users)
                    </option>
                    <option value="major_admin" {{ old('role', $user->role) === 'major_admin' ? 'selected' : '' }}>
                        🎓 Major Admin (Can view all resources, but ONLY edit, approve, or delete files for their assigned major)
                    </option>
                    <option value="professor" {{ old('role', $user->role) === 'professor' ? 'selected' : '' }}>
                        🎓 Professor (Academic reviewer with jurisdiction over their assigned major)
                    </option>
                    <option value="editor" {{ old('role', $user->role) === 'editor' ? 'selected' : '' }}>
                        ✏️ Editor (Can edit courses, programs, and catalog resources across all majors)
                    </option>
                    <option value="viewer" {{ old('role', $user->role) === 'viewer' ? 'selected' : '' }}>
                        👁️ Viewer (Read-only access to view resources, courses, and reports without editing rights)
                    </option>
                    <option value="student" {{ old('role', $user->role) === 'student' ? 'selected' : '' }}>
                        📱 Student (Normal Telegram bot user, no access to this web panel)
                    </option>
                </select>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">
                    Staff roles (Admin, Moderator, Major Admin, Professor, Editor, Viewer) can log in to the web panel. Students are blocked.
                </p>
            </div>

            <!-- 3. Assigned Major (Program) -->
            <div id="programSelectGroup" style="border: 1px solid #c7d2fe; background: #eef2ff; border-radius: 8px; padding: 1.25rem;">
                <label for="program_id" style="display: block; font-weight: 700; color: #3730a3; margin-bottom: 0.35rem;">
                    🎓 Assigned Major (Degree Programme)
                </label>
                <p style="font-size: 0.775rem; color: #4338ca; margin-bottom: 0.75rem;">
                    For <strong>Major Admins & Professors</strong>, this specifies their jurisdiction. They will only be authorized to approve, edit, or moderate study materials belonging to this degree program.
                </p>
                <select name="program_id" id="program_id" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #a5b4fc; border-radius: 6px; font-size: 0.875rem; background: #ffffff; color: var(--text-main);">
                    <option value="">— Select Degree Program —</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" {{ (int) old('program_id', $user->program_id) === $program->id ? 'selected' : '' }}>
                            {{ $program->name }} ({{ $program->degree_level }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 4. Basic Info -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label for="first_name" style="display: block; font-weight: 600; color: var(--text-main); margin-bottom: 0.35rem;">
                        First Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="first_name" 
                        id="first_name" 
                        value="{{ old('first_name', $user->first_name) }}" 
                        required 
                        style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem;"
                    >
                </div>
                <div>
                    <label for="last_name" style="display: block; font-weight: 600; color: var(--text-main); margin-bottom: 0.35rem;">
                        Last Name
                    </label>
                    <input 
                        type="text" 
                        name="last_name" 
                        id="last_name" 
                        value="{{ old('last_name', $user->last_name) }}" 
                        style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem;"
                    >
                </div>
            </div>

            <!-- 5. Web Credentials -->
            <div style="border-top: 1px solid var(--border); padding-top: 1.25rem; display: flex; flex-direction: column; gap: 1rem;">
                <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main);">🔑 Web Panel Login Credentials</h3>

                <div>
                    <label for="email" style="display: block; font-weight: 600; color: var(--text-main); margin-bottom: 0.35rem;">
                        Email Address (Used to log in)
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email', $user->email) }}" 
                        placeholder="user@parsi.polito.it"
                        style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem;"
                    >
                </div>

                <div>
                    <label for="password" style="display: block; font-weight: 600; color: var(--text-main); margin-bottom: 0.35rem;">
                        New Password <span style="font-weight: 400; color: var(--text-muted); font-size: 0.775rem;">(Leave blank to keep unchanged)</span>
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        placeholder="••••••••••••"
                        autocomplete="new-password"
                        style="width: 100%; padding: 0.6rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem;"
                    >
                </div>
            </div>

            <!-- Submit -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" style="padding: 0.65rem 1.25rem;">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.5rem;">
                    💾 Save User & Role
                </button>
            </div>

        </form>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('roleSelect');
        const programGroup = document.getElementById('programSelectGroup');
        const programInput = document.getElementById('program_id');

        function updateProgramVisibility() {
            if (['major_admin', 'professor'].includes(roleSelect.value)) {
                programGroup.style.display = 'block';
                programInput.setAttribute('required', 'required');
            } else {
                programInput.removeAttribute('required');
            }
        }

        roleSelect.addEventListener('change', updateProgramVisibility);
        updateProgramVisibility();
    });
</script>
@endsection
