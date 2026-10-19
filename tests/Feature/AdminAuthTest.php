<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertOk();
        $response->assertSee('PARSI Admin');
        $response->assertSee('Sign in to manage educational materials');
    }

    public function test_admin_can_authenticate_with_valid_credentials(): void
    {
        $admin = User::create([
            'first_name' => 'Sara',
            'last_name' => 'Admin',
            'email' => 'sara@parsi.polito.it',
            'password' => Hash::make('secret1234'),
            'role' => 'admin',
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => 'sara@parsi.polito.it',
            'password' => 'secret1234',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_authenticate_with_username(): void
    {
        $admin = User::create([
            'first_name' => 'Hamid',
            'username' => 'testadminuser',
            'email' => 'hamid@parsi.polito.it',
            'password' => Hash::make('secret1234'),
            'role' => 'admin',
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => 'testadminuser',
            'password' => 'secret1234',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_cannot_authenticate_with_invalid_password(): void
    {
        $admin = User::create([
            'first_name' => 'Sara',
            'email' => 'sara2@parsi.polito.it',
            'password' => Hash::make('correct_password'),
            'role' => 'admin',
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => 'sara2@parsi.polito.it',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_non_admin_user_is_denied_access(): void
    {
        $student = User::create([
            'first_name' => 'Ali',
            'email' => 'student@polito.it',
            'password' => Hash::make('password123'),
            'role' => 'student',
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => 'student@polito.it',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $admin = User::create([
            'first_name' => 'AdminUser',
            'email' => 'adminuser@parsi.polito.it',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_unauthenticated_user_accessing_admin_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('admin.login'));
    }
}
