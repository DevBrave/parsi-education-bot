<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('website-login')]
class WebsiteLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_custom_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/Login')
                ->where('auth.user', null)
                ->where('canAccessAdmin', false)
                ->where('status', null)
            );
    }

    public function test_the_login_page_receives_session_status_messages(): void
    {
        $this->withSession(['status' => 'Your password has been reset.'])
            ->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/Login')
                ->where('status', 'Your password has been reset.')
            );
    }

    #[DataProvider('userRoles')]
    public function test_students_and_staff_can_log_in_and_return_home(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('home', absolute: false))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_rotates_the_session_id(): void
    {
        $user = User::factory()->create();
        $this->withSession(['login-test' => true])->get(route('login'));
        $previousSessionId = session()->getId();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, session()->getId());
    }

    public function test_remember_me_sets_the_authentication_cookie(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ])->assertRedirect()
            ->assertCookie(Auth::guard('web')->getRecallerName());

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_without_remember_me_does_not_set_a_persistent_cookie(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect()
            ->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    public function test_wrong_password_is_rejected_without_exposing_it_in_the_session(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_unknown_email_is_rejected_with_the_same_generic_error(): void
    {
        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'missing@example.test',
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest();
    }

    #[DataProvider('missingCredentials')]
    public function test_login_requires_both_credentials(array $credentials, string $field): void
    {
        $this->from(route('login'))->post(route('login.store'), $credentials)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors($field);

        $this->assertGuest();
    }

    public function test_repeated_invalid_logins_are_throttled(): void
    {
        $credentials = ['email' => 'rate-limit@example.test', 'password' => 'incorrect'];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), $credentials)
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), $credentials)
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');

        $this->assertGuest();
    }

    public function test_staff_return_to_the_admin_page_they_requested_before_login(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_EDITOR]);
        $destination = route('admin.resources.index');

        $this->get($destination)->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect($destination);

        $this->get($destination)->assertOk();
    }

    public function test_a_student_login_does_not_bypass_admin_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_authenticated_users_are_redirected_away_from_the_login_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($user)->get(route('login'))->assertRedirect(route('home'));
    }

    public function test_logout_removes_authentication_and_admin_access(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)->withSession(['private-state' => 'secret'])
            ->post(route('logout'))
            ->assertRedirect(route('home'))
            ->assertSessionMissing('private-state');

        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public static function userRoles(): array
    {
        return [
            'student' => [User::ROLE_STUDENT],
            'admin' => [User::ROLE_ADMIN],
            'moderator' => [User::ROLE_MODERATOR],
            'major admin' => [User::ROLE_MAJOR_ADMIN],
            'professor' => [User::ROLE_PROFESSOR],
            'editor' => [User::ROLE_EDITOR],
            'viewer' => [User::ROLE_VIEWER],
        ];
    }

    public static function missingCredentials(): array
    {
        return [
            'missing email' => [['password' => 'password'], 'email'],
            'missing password' => [['email' => 'student@example.test'], 'password'],
            'empty email' => [['email' => '', 'password' => 'password'], 'email'],
            'empty password' => [['email' => 'student@example.test', 'password' => ''], 'password'],
        ];
    }
}
