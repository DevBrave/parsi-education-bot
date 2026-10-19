@extends('admin.layout')

@section('title', 'User & Role Management')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- Top Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">👥 Users & Roles</h1>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage platform administrators, editors, viewers, professors, and students</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;">
        <div class="card" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.25rem;">
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 500;">Total Users</span>
            <span style="font-size: 1.6rem; font-weight: 700; color: var(--text-main);">{{ number_format($stats['total']) }}</span>
        </div>

        <div class="card" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #4f46e5;">
            <span style="font-size: 0.8125rem; color: #4338ca; font-weight: 600;">👑 Super Admins</span>
            <span style="font-size: 1.6rem; font-weight: 700; color: #3730a3;">{{ number_format($stats['admins']) }}</span>
        </div>

        <div class="card" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #0d9488;">
            <span style="font-size: 0.8125rem; color: #0f766e; font-weight: 600;">🛡️ Moderators</span>
            <span style="font-size: 1.6rem; font-weight: 700; color: #115e59;">{{ number_format($stats['moderators']) }}</span>
        </div>

        <div class="card" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #059669;">
            <span style="font-size: 0.8125rem; color: #047857; font-weight: 600;">🎓 Major Admins</span>
            <span style="font-size: 1.6rem; font-weight: 700; color: #065f46;">{{ number_format($stats['major_admins']) }}</span>
        </div>

        <div class="card" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #0284c7;">
            <span style="font-size: 0.8125rem; color: #0369a1; font-weight: 600;">✏️ Editors</span>
            <span style="font-size: 1.6rem; font-weight: 700; color: #075985;">{{ number_format($stats['editors']) }}</span>
        </div>

        <div class="card" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #64748b;">
            <span style="font-size: 0.8125rem; color: #475569; font-weight: 600;">👁️ Viewers</span>
            <span style="font-size: 1.6rem; font-weight: 700; color: #334155;">{{ number_format($stats['viewers']) }}</span>
        </div>

        <div class="card" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.25rem; border-left: 4px solid #d97706;">
            <span style="font-size: 0.8125rem; color: #b45309; font-weight: 600;">📱 Students</span>
            <span style="font-size: 1.6rem; font-weight: 700; color: #92400e;">{{ number_format($stats['students']) }}</span>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card" style="padding: 1.25rem;">
        <form method="GET" action="{{ route('admin.users.index') }}" style="display: flex; flex-direction: column; gap: 1rem;">
            
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                <span style="font-size: 0.8125rem; font-weight: 600; color: var(--text-muted); margin-right: 0.25rem;">Role Filter:</span>
                
                <a href="{{ route('admin.users.index', array_merge(request()->except('role', 'page'))) }}" 
                   class="btn {{ !request('role') ? 'btn-primary' : 'btn-secondary' }}" 
                   style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">
                   All Roles
                </a>

                <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['role' => 'admin'])) }}" 
                   class="btn {{ request('role') === 'admin' ? 'btn-primary' : 'btn-secondary' }}" 
                   style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">
                   👑 Admin
                </a>

                <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['role' => 'moderator'])) }}" 
                   class="btn {{ request('role') === 'moderator' ? 'btn-primary' : 'btn-secondary' }}" 
                   style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">
                   🛡️ Moderator
                </a>

                <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['role' => 'major_admin'])) }}" 
                   class="btn {{ request('role') === 'major_admin' ? 'btn-primary' : 'btn-secondary' }}" 
                   style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">
                   🎓 Major Admin
                </a>

                <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['role' => 'editor'])) }}" 
                   class="btn {{ request('role') === 'editor' ? 'btn-primary' : 'btn-secondary' }}" 
                   style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">
                   ✏️ Editor
                </a>

                <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['role' => 'viewer'])) }}" 
                   class="btn {{ request('role') === 'viewer' ? 'btn-primary' : 'btn-secondary' }}" 
                   style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">
                   👁️ Viewer
                </a>

                <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['role' => 'student'])) }}" 
                   class="btn {{ request('role') === 'student' ? 'btn-primary' : 'btn-secondary' }}" 
                   style="padding: 0.35rem 0.75rem; font-size: 0.775rem;">
                   📱 Student
                </a>
            </div>

            <div style="display: flex; gap: 0.75rem; align-items: center; max-width: 500px;">
                @if(request('role'))
                    <input type="hidden" name="role" value="{{ request('role') }}">
                @endif
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Search by name, @username, email, or Telegram ID..." 
                    style="flex: 1; padding: 0.5rem 0.85rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.875rem;"
                >
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                    🔍 Search
                </button>
                @if(request('search') || request('role'))
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" style="padding: 0.5rem 0.85rem; font-size: 0.875rem;">
                        Reset
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- Users Table -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="compact-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="min-width: 180px;">User</th>
                        <th style="min-width: 120px;">Role</th>
                        <th style="min-width: 180px;">Assigned Major</th>
                        <th style="min-width: 180px;">Web Login / Email</th>
                        <th style="min-width: 120px;">Telegram ID</th>
                        <th style="text-align: right; min-width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <!-- 1. Name & Username -->
                            <td>
                                <div>
                                    <strong style="color: var(--text-main); font-size: 0.875rem;">
                                        {{ $user->first_name }} {{ $user->last_name }}
                                    </strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">
                                        @if($user->username)
                                            <a href="https://t.me/{{ $user->username }}" target="_blank" style="color: #4f46e5; text-decoration: none;">
                                                {{ '@' . $user->username }} ↗
                                            </a>
                                        @else
                                            <span style="color: var(--text-muted);">No @username</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- 2. Role Badge -->
                            <td>
                                @if($user->role === 'admin')
                                    <span style="background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; border-radius: 4px; padding: 2px 7px; font-size: 0.725rem; font-weight: 700;">
                                        👑 Super Admin
                                    </span>
                                @elseif($user->role === 'moderator')
                                    <span style="background: #f0fdfa; color: #0f766e; border: 1px solid #99f6e4; border-radius: 4px; padding: 2px 7px; font-size: 0.725rem; font-weight: 700;">
                                        🛡️ Moderator
                                    </span>
                                @elseif($user->role === 'major_admin')
                                    <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 4px; padding: 2px 7px; font-size: 0.725rem; font-weight: 700;">
                                        🎓 Major Admin
                                    </span>
                                @elseif($user->role === 'editor')
                                    <span style="background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; border-radius: 4px; padding: 2px 7px; font-size: 0.725rem; font-weight: 700;">
                                        ✏️ Editor
                                    </span>
                                @elseif($user->role === 'professor')
                                    <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 4px; padding: 2px 7px; font-size: 0.725rem; font-weight: 700;">
                                        🎓 Professor
                                    </span>
                                @elseif($user->role === 'viewer')
                                    <span style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 7px; font-size: 0.725rem; font-weight: 700;">
                                        👁️ Viewer
                                    </span>
                                @else
                                    <span style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a; border-radius: 4px; padding: 2px 7px; font-size: 0.725rem; font-weight: 600;">
                                        📱 Student
                                    </span>
                                @endif
                            </td>

                            <!-- 3. Assigned Major -->
                            <td>
                                @if($user->program)
                                    <div>
                                        <span style="font-weight: 600; color: var(--text-main); font-size: 0.825rem;">
                                            {{ $user->program->name }}
                                        </span>
                                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.1rem;">
                                            {{ $user->program->degree_level }}
                                        </div>
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">— None assigned —</span>
                                @endif
                            </td>

                            <!-- 4. Email / Login -->
                            <td>
                                @if($user->email)
                                    <span style="font-size: 0.8rem; font-family: monospace; color: var(--text-main);">
                                        {{ $user->email }}
                                    </span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">(No web login email)</span>
                                @endif
                            </td>

                            <!-- 5. Telegram ID -->
                            <td>
                                @if($user->telegram_user_id)
                                    <span style="font-family: monospace; font-size: 0.775rem; color: var(--text-muted);">
                                        #{{ $user->telegram_user_id }}
                                    </span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.75rem;">Web only</span>
                                @endif
                            </td>

                            <!-- 6. Actions -->
                            <td style="text-align: right;">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.75rem; border-radius: 4px;">
                                    Edit Role ⚙️
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                                <div>
                                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
                                    <strong>No users found</strong>
                                    <p style="margin-top: 0.25rem; font-size: 0.8125rem;">Try adjusting your search criteria or role filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border);">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
