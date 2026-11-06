<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SetAdminCredentialsCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_existing_telegram_student_gets_credentials_and_role(): void
    {
        config(['telegram.bot_token' => 'test-token']);
        Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => 10]])]);
        $user = User::create([
            'telegram_user_id' => 987654321,
            'username' => 'credentials_test_user',
            'first_name' => 'Test',
            'role' => User::ROLE_STUDENT,
        ]);
        $count = User::count();

        $this->artisan('admin:set-credentials', [
            'user' => '@credentials_test_user',
            '--email' => 'credentials-test@example.com',
            '--role' => 'editor',
        ])->assertSuccessful();

        $user->refresh();
        $this->assertSame('editor', $user->role);
        $this->assertSame('credentials-test@example.com', $user->email);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($user) {
            $this->assertSame($user->telegram_user_id, $request['chat_id']);
            $this->assertStringContainsString('credentials-test@example.com', $request['text']);
            preg_match('/Password: <code>(.*?)<\/code>/', $request['text'], $matches);
            $password = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            $this->assertSame(10, strlen($password));
            $this->assertMatchesRegularExpression('/[A-Z]/', $password);
            $this->assertMatchesRegularExpression('/[a-z]/', $password);
            $this->assertMatchesRegularExpression('/[0-9]/', $password);
            $this->assertMatchesRegularExpression('/[^a-zA-Z0-9]/', $password);
            $this->assertTrue(Hash::check($password, $user->password));

            return str_ends_with($request->url(), '/sendMessage');
        });
        $this->assertSame($count, User::count());
    }

    public function test_failed_telegram_delivery_keeps_existing_credentials_and_role(): void
    {
        config(['telegram.bot_token' => 'test-token']);
        Http::fake(['*' => Http::response(['ok' => false, 'description' => 'Forbidden'], 403)]);
        $user = User::create([
            'telegram_user_id' => 987654323,
            'username' => 'delivery_failure_test',
            'first_name' => 'Test',
            'email' => 'original@example.com',
            'password' => Hash::make('original-password'),
            'role' => User::ROLE_STUDENT,
        ]);
        $original = $user->getAttributes();

        $this->artisan('admin:set-credentials', [
            'user' => '@delivery_failure_test',
            '--email' => 'replacement@example.com',
            '--role' => 'editor',
        ])->assertFailed();

        $this->assertSame($original, $user->fresh()->getAttributes());
        Http::assertSentCount(1);
    }

    public function test_existing_password_is_preserved_when_email_and_role_change(): void
    {
        config(['telegram.bot_token' => 'test-token']);
        Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => 11]])]);
        $user = User::create([
            'telegram_user_id' => 987654324,
            'username' => 'keep_password_test',
            'first_name' => 'Test',
            'email' => 'old-address@example.com',
            'password' => Hash::make('keep-this-password'),
            'role' => User::ROLE_STUDENT,
        ]);
        $originalHash = $user->password;

        $this->artisan('admin:set-credentials', [
            'user' => '@keep_password_test',
            '--email' => 'new-address@example.com',
            '--role' => 'viewer',
        ])->expectsOutput('Existing password preserved.')->assertSuccessful();

        $user->refresh();
        $this->assertSame($originalHash, $user->password);
        $this->assertSame('new-address@example.com', $user->email);
        $this->assertSame('viewer', $user->role);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) use ($user, $originalHash) {
            $this->assertStringContainsString('Password: unchanged.', $request['text']);
            $this->assertStringNotContainsString($originalHash, $request['text']);
            $this->assertStringNotContainsString('keep-this-password', $request['text']);
            $this->assertStringNotContainsString('Password: <code>', $request['text']);

            return $request['chat_id'] === $user->telegram_user_id;
        });
    }

    public function test_missing_username_does_not_update_a_different_staff_account(): void
    {
        $user = User::create([
            'telegram_user_id' => 987654322,
            'username' => 'existing_staff_test',
            'first_name' => 'Staff',
            'role' => User::ROLE_ADMIN,
        ]);
        $original = $user->getAttributes();

        $this->artisan('admin:set-credentials', [
            'user' => '@nonexistent_credentials_test_user',
            '--email' => 'wrong-account@example.com',
            '--role' => 'editor',
        ])->assertFailed();

        $this->assertSame($original, $user->fresh()->getAttributes());
    }
}
