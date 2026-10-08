<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TestResourceSeeder extends Seeder
{
    public function run(): void
    {
        // Create an initial admin user (simulates an authorized Telegram admin)
        User::firstOrCreate(
            ['telegram_user_id' => 123456789],
            [
                'username' => 'devbrave',
                'first_name' => 'Hamidreza',
                'last_name' => 'Ramezani',
                'preferred_language' => 'fa',
                'role' => 'admin',
            ],
        );
    }
}
