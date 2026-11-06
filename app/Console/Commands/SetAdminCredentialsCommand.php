<?php

namespace App\Console\Commands;


use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class SetAdminCredentialsCommand extends Command
{
    protected $signature = 'admin:set-credentials
                            {user : Telegram username, Telegram ID, email, or user ID of an existing user}
                            {--role= : Role to assign to the user}
                            {--email= : Email address to assign or update}';

    protected $description = 'Update a user and notify them through Telegram; generate a password only if none exists';

    public function handle(TelegramService $telegram): int
    {
        $roles = [
            User::ROLE_ADMIN,
            User::ROLE_MODERATOR,
            User::ROLE_EDITOR,
            User::ROLE_VIEWER,
            User::ROLE_PROFESSOR,
            User::ROLE_MAJOR_ADMIN,
            User::ROLE_STUDENT,
            User::ROLE_VIP
        ];
        $role = $this->option('role');
        if ($role !== null && ! in_array($role, $roles, true)) {
            $this->error('Invalid role. Allowed roles: ' . implode(', ', $roles));

            return self::FAILURE;
        }

        $identifier = $this->argument('user');
        $user = User::where(function ($query) use ($identifier) {
            $query->where('username', ltrim($identifier, '@'))->orWhere('email', $identifier);
            if (ctype_digit($identifier)) {
                $query->orWhere('telegram_user_id', $identifier)->orWhere('id', $identifier);
            }
        })->first();

        if (! $user) {
            $this->error("No user found matching '{$identifier}'. No accounts were changed.");

            return self::FAILURE;
        }
        if (! $user->telegram_user_id || $user->telegram_user_id <= 0) {
            $this->error('This user has no valid private Telegram chat ID. No accounts were changed.');

            return self::FAILURE;
        }
        if (! config('telegram.bot_token')) {
            $this->error('The Telegram bot token is not configured. No accounts were changed.');

            return self::FAILURE;
        }

        $email = $this->option('email') ?? $user->email ?? $this->ask('Enter user email');
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $password = filled($user->password) ? null : $this->generatePassword();
        $newRole = $role ?? $user->role;
        $passwordLine = $password !== null
            ? 'Password: <code>' . e($password) . "</code>\n"
            : "<code>Password: unchanged. Use your existing password.</code>\n";
        $message = "<b>Your PARSI account details</b>\n\n"
            . 'Email: <code>' . e($email) . "</code>\n"
            . $passwordLine
            . 'Role: ' . e($newRole) . "\n\n"
            . '<b>Your Major: <code>' . e($user->program?->name) . "</code> (The best Major)</b>\n\n"
            . 'Sign in: ' . e(route('admin.login')) . "\n\n"
            . 'Please Keep these credentials private.';

        try {
            DB::transaction(function () use ($user, $email, $password, $newRole, $message, $telegram) {
                $updates = [
                    'email' => $email,
                    'role' => $newRole,
                ];
                if ($password !== null) {
                    $updates['password'] = Hash::make($password);
                }
                $user->update($updates);

                $response = $telegram->sendMessage($user->telegram_user_id, $message);
                if (! $response->successful() || $response->json('ok') !== true) {
                    throw new RuntimeException('Telegram did not confirm delivery.');
                }
            });
        } catch (Throwable $exception) {
            $this->error('Credentials were not saved because saving or Telegram delivery failed. Ask the user to start/unblock the bot, check the bot configuration, and retry.');

            return self::FAILURE;
        }

        $this->info('Credentials saved and sent privately to the selected Telegram user.');
        if ($password === null) {
            $this->line('Existing password preserved.');
        }
        $this->line("User: @{$user->username} (ID: {$user->id})");
        $this->line("Email: {$email}");
        $this->line("Role: {$newRole}");

        return self::SUCCESS;
    }

    private function generatePassword(): string
    {
        $groups = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%&*+-=?'];
        $characters = [];
        foreach ($groups as $group) {
            $characters[] = $group[random_int(0, strlen($group) - 1)];
        }
        $pool = implode('', $groups);
        while (count($characters) < 10) {
            $characters[] = $pool[random_int(0, strlen($pool) - 1)];
        }
        for ($index = count($characters) - 1; $index > 0; $index--) {
            $swap = random_int(0, $index);
            [$characters[$index], $characters[$swap]] = [$characters[$swap], $characters[$index]];
        }

        return implode('', $characters);
    }
}
