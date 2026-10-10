<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use App\Telegram\TelegramUpdateDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnboardingMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['ok' => true])]);

        // Ensure a sample program exists
        Program::firstOrCreate(
            ['name' => 'Computer Engineering', 'degree_level' => 'MSc'],
            [
                'university' => 'Politecnico di Torino',
                'is_active' => true,
            ]
        );
    }

    public function test_new_user_without_language_is_prompted_for_language(): void
    {
        $dispatcher = app(TelegramUpdateDispatcher::class);

        $payload = [
            'update_id' => 101,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 5001],
                'from' => [
                    'id' => 5001,
                    'first_name' => 'Neda',
                    'username' => 'neda_polito',
                ],
                'text' => '/start',
            ],
        ];

        $dispatcher->dispatch($payload);

        // Verify user was created with null language and null program
        $user = User::where('telegram_user_id', 5001)->first();
        $this->assertNotNull($user);
        $this->assertNull($user->preferred_language);
        $this->assertNull($user->program_id);

        // Verify language prompt with 3 language buttons was sent
        Http::assertSent(function (Request $request) {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains($request['text'], 'Welcome to PARSI PoliTo Bot')
                && isset($request['reply_markup']['inline_keyboard'][0])
                && $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] === 'onboard_lang_en';
        });
    }

    public function test_user_selecting_language_is_prompted_for_degree_programme(): void
    {
        $user = User::create([
            'telegram_user_id' => 5002,
            'first_name' => 'Marco',
            'preferred_language' => null,
            'program_id' => null,
        ]);

        $dispatcher = app(TelegramUpdateDispatcher::class);

        $payload = [
            'update_id' => 102,
            'callback_query' => [
                'id' => 'cb_lang_italian',
                'from' => ['id' => 5002, 'first_name' => 'Marco'],
                'message' => ['chat' => ['id' => 5002], 'message_id' => 10],
                'data' => 'onboard_lang_it',
            ],
        ];

        $dispatcher->dispatch($payload);

        $user->refresh();
        $this->assertEquals('it', $user->preferred_language);
        $this->assertNull($user->program_id);

        // Verify message was edited to show degree programmes in Italian
        Http::assertSent(function (Request $request) {
            return str_ends_with($request->url(), '/editMessageText')
                && str_contains($request['text'], 'Seleziona il tuo Corso di Laurea')
                && isset($request['reply_markup']['inline_keyboard'][0])
                && str_starts_with($request['reply_markup']['inline_keyboard'][0][0]['callback_data'], 'onboard_prog_');
        });
    }

    public function test_user_selecting_programme_completes_onboarding(): void
    {
        $program = Program::first();

        $user = User::create([
            'telegram_user_id' => 5003,
            'first_name' => 'Ali',
            'preferred_language' => 'fa',
            'program_id' => null,
        ]);

        $dispatcher = app(TelegramUpdateDispatcher::class);

        $payload = [
            'update_id' => 103,
            'callback_query' => [
                'id' => 'cb_prog_select',
                'from' => ['id' => 5003, 'first_name' => 'Ali'],
                'message' => ['chat' => ['id' => 5003], 'message_id' => 11],
                'data' => 'onboard_prog_'.$program->id,
            ],
        ];

        $dispatcher->dispatch($payload);

        $user->refresh();
        $this->assertEquals($program->id, $user->program_id);

        // Verify completion confirmation and main menu in Persian
        Http::assertSent(function (Request $request) {
            return str_ends_with($request->url(), '/sendMessage')
                && isset($request['reply_markup']['keyboard'])
                && $request['reply_markup']['keyboard'][0][0]['text'] === '📚 فایل‌های آموزشی';
        });
    }

    public function test_fully_onboarded_user_bypasses_onboarding(): void
    {
        $program = Program::first();

        $user = User::create([
            'telegram_user_id' => 5004,
            'first_name' => 'Emma',
            'preferred_language' => 'en',
            'program_id' => $program->id,
        ]);

        $dispatcher = app(TelegramUpdateDispatcher::class);

        $payload = [
            'update_id' => 104,
            'message' => [
                'message_id' => 20,
                'chat' => ['id' => 5004],
                'from' => ['id' => 5004, 'first_name' => 'Emma'],
                'text' => '/start',
            ],
        ];

        $dispatcher->dispatch($payload);

        // Normal welcome message sent by MessageHandler directly
        Http::assertSent(function (Request $request) {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains($request['text'], 'Welcome to PARSI Education Bot, Emma')
                && isset($request['reply_markup']['keyboard']);
        });
    }
}
