<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('website-login')]
class WebsiteHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_homepage_does_not_expose_an_authenticated_user_or_admin_access(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('auth.user', null)
                ->where('canAccessAdmin', false)
                ->has('telegramUrl')
            );
    }

    #[DataProvider('homepageRoles')]
    public function test_homepage_shares_the_correct_access_without_exposing_credentials(string $role, bool $canAccessAdmin): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('auth.user.id', $user->id)
                ->where('canAccessAdmin', $canAccessAdmin)
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token')
            );
    }

    public static function homepageRoles(): array
    {
        return [
            'student' => [User::ROLE_STUDENT, false],
            'unrecognized role' => ['unrecognized', false],
            'admin' => [User::ROLE_ADMIN, true],
            'moderator' => [User::ROLE_MODERATOR, true],
            'major admin' => [User::ROLE_MAJOR_ADMIN, true],
            'professor' => [User::ROLE_PROFESSOR, true],
            'editor' => [User::ROLE_EDITOR, true],
            'viewer' => [User::ROLE_VIEWER, true],
        ];
    }
}
