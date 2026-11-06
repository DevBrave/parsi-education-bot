<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_block_and_unblock_without_changing_user_details(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $name = $user->first_name;

        foreach ([true, false] as $blocked) {
            $this->actingAs($admin)->patch(route('admin.users.blocked', $user), [
                'is_blocked' => $blocked,
            ])->assertRedirect(route('admin.users.edit', $user));

            $user->refresh();
            $this->assertSame($blocked, $user->is_blocked);
            $this->assertSame($name, $user->first_name);
            $this->assertSame(User::ROLE_STUDENT, $user->role);
        }
    }

    public function test_other_staff_cannot_block_users(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $user = User::factory()->create();

        $this->actingAs($moderator)->patch(route('admin.users.blocked', $user), [
            'is_blocked' => true,
        ])->assertForbidden();

        $this->assertFalse($user->fresh()->is_blocked);
    }
}
