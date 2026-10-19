<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SetAdminCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:set-credentials 
                            {user? : Username, Telegram ID, Email, or User ID of the admin} 
                            {password? : New admin password} 
                            {--email= : Email address to assign or update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set email and password for a specific administrator account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $staffUsers = User::whereIn('role', [
            User::ROLE_ADMIN,
            User::ROLE_EDITOR,
            User::ROLE_VIEWER,
            User::ROLE_PROFESSOR,
        ])->get();

        if ($staffUsers->isEmpty()) {
            $this->error('No staff users found in the database. Creating a new admin user...');
            $email = $this->option('email') 
                ?? $this->argument('user') 
                ?? $this->ask('Enter admin email', 'admin@parsi.polito.it');
            $password = $this->argument('password') ?? $this->secret('Enter admin password');

            $newUser = User::create([
                'first_name' => 'Admin',
                'last_name' => 'PARSI',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
            ]);

            $this->info("✅ Admin user created with email: {$newUser->email}");

            return self::SUCCESS;
        }

        $userParam = $this->argument('user');
        $admin = null;

        // 1. If a specific user identifier was passed, search across all users
        if ($userParam) {
            $cleanParam = ltrim($userParam, '@');
            $admin = User::where('username', $cleanParam)
                ->orWhere('email', $userParam)
                ->orWhere('telegram_user_id', $userParam)
                ->orWhere('id', $userParam)
                ->first();

            if (! $admin) {
                $this->warn("No user found matching '{$userParam}'.");
            }
        }

        // 2. If no user param or no match, and only 1 staff user exists
        if (! $admin && $staffUsers->count() === 1) {
            $admin = $staffUsers->first();
        }

        // 3. If multiple staff users exist and none selected, prompt choice
        if (! $admin && $staffUsers->count() > 1) {
            $choices = $staffUsers->mapWithKeys(function (User $u) {
                $label = "ID: {$u->id} | {$u->first_name} {$u->last_name} (@" . ($u->username ?? 'no-username') . ") [{$u->role_label}] | {$u->email}";
                return [$u->id => $label];
            })->toArray();

            $selectedId = $this->choice('Select the account you want to update', $choices);
            $admin = $staffUsers->firstWhere('id', $selectedId);
        }

        if (! $admin) {
            $this->error('Could not determine which admin account to update.');
            return self::FAILURE;
        }

        $this->info("👤 Selected Admin: {$admin->first_name} {$admin->last_name} (@{$admin->username}, ID: {$admin->id})");

        // Determine Email
        $newEmail = $this->option('email');
        if (! $newEmail) {
            // If userParam looks like an email and wasn't the password, or keep existing
            if ($userParam && filter_var($userParam, FILTER_VALIDATE_EMAIL)) {
                $newEmail = $userParam;
            } else {
                $newEmail = $admin->email ?? $this->ask('Enter admin email', 'admin@parsi.polito.it');
            }
        }

        // Determine Password
        $newPassword = $this->argument('password');
        if (! $newPassword) {
            $newPassword = $this->ask('Enter new password', 'password123');
        }

        $admin->update([
            'email' => $newEmail,
            'password' => Hash::make($newPassword),
        ]);

        $this->info('✅ Successfully updated admin credentials!');
        $this->line("👤 Admin: {$admin->first_name} {$admin->last_name} (@{$admin->username})");
        $this->line("📧 Email: {$admin->email}");
        $this->line("🔑 Password: [Updated successfully]");

        return self::SUCCESS;
    }
}
