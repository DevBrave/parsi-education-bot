<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    /**
     * Display a paginated listing of users with role filtering.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user && ! $user->canManageUsers()) {
            abort(403, 'Unauthorized. Only Super Administrators can manage user roles.');
        }

        $query = User::with('program')->latest();

        // 1. Role filter
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        // 2. Search filter (name, username, email, telegram ID)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $cleanTg = ltrim($search, '@');

            $query->where(function ($q) use ($search, $cleanTg) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$cleanTg}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telegram_user_id', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', User::ROLE_ADMIN)->count(),
            'moderators' => User::where('role', User::ROLE_MODERATOR)->count(),
            'major_admins' => User::whereIn('role', [User::ROLE_MAJOR_ADMIN, User::ROLE_PROFESSOR])->count(),
            'editors' => User::where('role', User::ROLE_EDITOR)->count(),
            'viewers' => User::where('role', User::ROLE_VIEWER)->count(),
            'students' => User::where('role', User::ROLE_STUDENT)->count(),
        ];

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the user's role and details.
     */
    public function edit(Request $request, User $user): View
    {
        if ($request->user() && ! $request->user()->canManageUsers()) {
            abort(403, 'Unauthorized. Only Super Administrators can manage user roles.');
        }

        $programs = Program::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.edit', [
            'user' => $user->load('program'),
            'programs' => $programs,
        ]);
    }

    /**
     * Update the user's role, assigned major, email, and password.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        if ($request->user() && ! $request->user()->canManageUsers()) {
            abort(403, 'Unauthorized. Only Super Administrators can manage user roles.');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_MODERATOR,
                User::ROLE_MAJOR_ADMIN,
                User::ROLE_PROFESSOR,
                User::ROLE_EDITOR,
                User::ROLE_VIEWER,
                User::ROLE_STUDENT,
            ])],
            'program_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($request->input('role'), [User::ROLE_MAJOR_ADMIN, User::ROLE_PROFESSOR], true)),
                'exists:programs,id',
            ],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'program_id.required' => 'A specific degree program (major) must be assigned for Major Admins and Professors.',
        ]);

        $isMajorScoped = in_array($validated['role'], [User::ROLE_MAJOR_ADMIN, User::ROLE_PROFESSOR], true);
        $updateData = [
            'first_name' => trim($validated['first_name']),
            'last_name' => ! empty($validated['last_name']) ? trim($validated['last_name']) : null,
            'email' => ! empty($validated['email']) ? trim($validated['email']) : null,
            'role' => $validated['role'],
            'program_id' => $isMajorScoped ? $validated['program_id'] : ($validated['program_id'] ?? $user->program_id),
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User '{$user->first_name} {$user->last_name}' updated successfully! Role set to {$user->role_label}.");
    }
}
