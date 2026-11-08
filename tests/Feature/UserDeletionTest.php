<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_edit_page_and_permanently_delete_another_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($admin)->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee(route('admin.users.destroy', $user), false);

        $this->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_other_staff_cannot_delete_users(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $user = User::factory()->create();

        $this->actingAs($moderator)->delete(route('admin.users.destroy', $user))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
