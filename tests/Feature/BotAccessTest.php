<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BotAccessSettings;
use App\Telegram\Middleware\BotAccessMiddleware;
use App\Telegram\TelegramUpdateDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BotAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_access_is_the_default_and_does_not_intercept_updates(): void
    {
        Http::fake();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->assertFalse(app(BotAccessSettings::class)->isRestricted());
        $this->assertFalse(app(BotAccessMiddleware::class)->handle([], $student));
        Http::assertNothingSent();
    }

    public function test_admin_can_save_local_expiry_as_utc_and_reopen_immediately(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-08 12:00:00', 'UTC'));
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $settings = app(BotAccessSettings::class);
        $this->assertFalse($settings->isRestricted()); // Populate default cache first.

        $this->actingAs($admin)->put(route('admin.bot-access.update'), [
            'mode' => 'staff_only', 'timezone' => 'Europe/Rome',
            'restricted_until' => '2026-11-08T15:00',
        ])->assertRedirect(route('admin.bot-access.edit'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bot_access_settings', [
            'id' => 1, 'mode' => 'staff_only', 'restricted_until' => '2026-11-08 14:00:00',
        ]);
        $this->assertTrue($settings->isRestricted());
        $this->get(route('admin.bot-access.edit'))->assertOk()->assertSee('2026-11-08T15:00');

        $this->put(route('admin.bot-access.update'), [
            'mode' => 'public', 'timezone' => 'Europe/Rome',
        ])->assertSessionHasNoErrors();
        $this->assertFalse($settings->isRestricted());
    }

    public function test_only_admins_can_manage_access_settings(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $this->actingAs($moderator)->get(route('admin.bot-access.edit'))->assertForbidden();
        $this->put(route('admin.bot-access.update'), [
            'mode' => 'public', 'timezone' => 'UTC',
        ])->assertForbidden();
        $this->assertDatabaseCount('bot_access_settings', 0);
    }

    public function test_restriction_requires_a_valid_future_expiry(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        foreach ([null, '2000-01-01T12:00', 'not-a-date'] as $until) {
            $this->actingAs($admin)->put(route('admin.bot-access.update'), [
                'mode' => 'staff_only', 'timezone' => 'UTC', 'restricted_until' => $until,
            ])->assertSessionHasErrors('restricted_until');
        }
        $this->assertDatabaseCount('bot_access_settings', 0);
    }

    public function test_dispatcher_denies_student_messages_uploads_callbacks_and_inline_queries(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        app(BotAccessSettings::class)->save('staff_only', CarbonImmutable::now()->addHour(), 'Europe/Rome');
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'preferred_language' => 'en']);
        $from = ['id' => $student->telegram_user_id, 'first_name' => $student->first_name];
        $chat = ['id' => $student->telegram_user_id, 'type' => 'private'];
        $dispatcher = app(TelegramUpdateDispatcher::class);
        $dispatcher->dispatch(['message' => ['from' => $from, 'chat' => $chat, 'text' => '/start']]);
        $dispatcher->dispatch(['message' => ['from' => $from, 'chat' => $chat, 'document' => ['file_id' => 'test']]]);
        $dispatcher->dispatch(['callback_query' => [
            'id' => 'callback1', 'from' => $from, 'data' => 'search_prompt', 'message' => ['chat' => $chat],
        ]]);
        $dispatcher->dispatch(['inline_query' => ['id' => 'inline1', 'from' => $from, 'query' => 'exam']]);

        Http::assertSentCount(4);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && str_contains($request['text'], 'temporarily available to staff only'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/answerCallbackQuery')
            && $request['callback_query_id'] === 'callback1');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/answerInlineQuery')
            && $request['results'] === '[]' && $request['cache_time'] === 0 && $request['is_personal'] === true);
    }

    public function test_all_staff_roles_bypass_restriction(): void
    {
        Http::fake();
        app(BotAccessSettings::class)->save('staff_only', CarbonImmutable::now()->addHour(), 'UTC');
        foreach ([User::ROLE_ADMIN, User::ROLE_MODERATOR, User::ROLE_MAJOR_ADMIN,
            User::ROLE_PROFESSOR, User::ROLE_EDITOR, User::ROLE_VIEWER] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse(app(BotAccessMiddleware::class)->handle([], $user));
        }
        Http::assertNothingSent();
    }

    public function test_expiry_reopens_access_without_waiting_for_cached_settings_to_expire(): void
    {
        Http::fake();
        $now = CarbonImmutable::now()->startOfSecond();
        $this->travelTo($now);
        $settings = app(BotAccessSettings::class);
        $settings->save('staff_only', $now->addSeconds(10), 'UTC');
        $this->assertTrue($settings->isRestricted());
        $this->travelTo($now->addSeconds(10));
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $this->assertFalse(app(BotAccessMiddleware::class)->handle([], $student));
        Http::assertNothingSent();
    }

    public function test_unrelated_group_messages_are_silent_but_addressed_search_is_denied(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        app(BotAccessSettings::class)->save('staff_only', CarbonImmutable::now()->addHour(), 'UTC');
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $message = [
            'from' => ['id' => $student->telegram_user_id, 'first_name' => $student->first_name],
            'chat' => ['id' => -100123, 'type' => 'supergroup'], 'text' => 'Hello everyone',
        ];
        app(TelegramUpdateDispatcher::class)->dispatch(['message' => $message]);
        Http::assertNothingSent();
        $message['text'] = '/search exams';
        app(TelegramUpdateDispatcher::class)->dispatch(['message' => $message]);
        Http::assertSentCount(1);
    }
}
