<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('website-login')]
class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('adminEndpoints')]
    public function test_guests_are_redirected_to_the_website_login(string $method, string $route): void
    {
        $this->call($method, route($route))->assertRedirect(route('login'));
    }

    #[DataProvider('adminEndpoints')]
    public function test_students_cannot_access_admin_routes(string $method, string $route): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->actingAs($student);
        $this->call($method, route($route))->assertForbidden();
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_enter_the_admin_panel_without_another_login(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_website_login_can_continue_to_an_intended_admin_page(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($staff);
    }

    public function test_unrecognized_roles_are_denied_admin_access(): void
    {
        $user = User::factory()->create(['role' => 'unrecognized']);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_viewers_cannot_manage_users_despite_having_panel_access(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $this->actingAs($viewer)->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_logout_returns_home_and_removes_panel_access(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post(route('admin.logout'))->assertRedirect(route('home'));

        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public static function adminEndpoints(): array
    {
        return [
            'dashboard' => ['GET', 'admin.dashboard'],
            'legacy login page' => ['GET', 'admin.login'],
            'users' => ['GET', 'admin.users.index'],
            'resources' => ['GET', 'admin.resources.index'],
            'logs' => ['GET', 'admin.logs.index'],
            'bot access settings' => ['GET', 'admin.bot-access.edit'],
            'legacy login submission' => ['POST', 'admin.login.submit'],
            'create resource type' => ['POST', 'admin.resource-types.store'],
            'create course' => ['POST', 'admin.courses.store'],
            'logout' => ['POST', 'admin.logout'],
        ];
    }

    public static function staffRoles(): array
    {
        return [
            'admin' => [User::ROLE_ADMIN],
            'moderator' => [User::ROLE_MODERATOR],
            'major admin' => [User::ROLE_MAJOR_ADMIN],
            'professor' => [User::ROLE_PROFESSOR],
            'editor' => [User::ROLE_EDITOR],
            'viewer' => [User::ROLE_VIEWER],
        ];
    }
}
