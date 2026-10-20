<?php

namespace Tests\Feature;

use App\Http\Controllers\TelegramWebhookController;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class TelegramMenuTest extends TestCase
{
    public function test_start_shows_a_persistent_reply_keyboard_and_escapes_the_name(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $user = new User(['first_name' => '<Student>']);

        $this->invokeHandler('handleMessage', [
            ['chat' => ['id' => 42], 'text' => '/start'], $user,
        ]);

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
            && $request['reply_markup']['keyboard'][0][0]['text'] === '📚 Study Resources'
            && $request['reply_markup']['is_persistent'] === true
            && $request['reply_markup']['one_time_keyboard'] === false
            && str_contains($request['text'], '&lt;Student&gt;'));
        Http::assertSentCount(1);
    }

    public function test_back_to_main_closes_the_inline_menu_and_restores_the_reply_keyboard(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $this->invokeHandler('handleCallbackQuery', [
            [
                'id' => 'callback-1',
                'data' => 'back_main',
                'message' => ['chat' => ['id' => 42], 'message_id' => 7],
            ],
            new User(['first_name' => 'Student']),
        ]);

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/answerCallbackQuery')
            && $request['callback_query_id'] === 'callback-1');
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/editMessageText')
            && $request['message_id'] === 7
            && $request['reply_markup']['inline_keyboard'] === []);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
            && isset($request['reply_markup']['keyboard'])
            && ! isset($request['reply_markup']['inline_keyboard']));
        Http::assertSentCount(3);
    }

    public function test_callback_without_a_chat_message_is_acknowledged_without_editing(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $this->invokeHandler('handleCallbackQuery', [
            ['id' => 'callback-2', 'data' => 'back_main', 'inline_message_id' => 'inline-1'],
            new User(['first_name' => 'Student']),
        ]);

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/answerCallbackQuery'));
        Http::assertSentCount(1);
    }

    public function test_persian_user_receives_persian_menu(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $user = new User(['first_name' => 'Ali', 'preferred_language' => 'fa']);

        $this->invokeHandler('handleMessage', [
            ['chat' => ['id' => 99], 'text' => '/start'], $user,
        ]);

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
            && $request['reply_markup']['keyboard'][0][0]['text'] === '📚 منابع درسی'
            && str_contains($request['text'], 'به ربات تلگرام انجمن پارسی خوش آمدید'));
    }

    public function test_italian_user_receives_italian_menu(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $user = new User(['first_name' => 'Marco', 'preferred_language' => 'it']);

        $this->invokeHandler('handleMessage', [
            ['chat' => ['id' => 100], 'text' => '/start'], $user,
        ]);

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
            && $request['reply_markup']['keyboard'][0][0]['text'] === '📚 Materiali Didattici'
            && str_contains($request['text'], 'Benvenuto su PARSI Education Bot'));
    }

    public function test_search_prompt_and_execution_returns_matching_courses_and_resources(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $course = \App\Models\Course::firstOrCreate(
            ['course_code' => 'SEARCH_CRS_1'],
            ['name' => 'Quantum Physics 101', 'credits' => 6.0, 'is_active' => true]
        );

        $resourceType = \App\Models\ResourceType::firstOrCreate(
            ['code' => 'notes_search'],
            ['display_name' => 'Lecture Notes', 'is_active' => true]
        );

        $student = User::firstOrNew(['telegram_user_id' => 999333]);
        $student->forceFill([
            'first_name' => 'SearchUser',
            'preferred_language' => 'en',
        ])->save();

        $resource = \App\Models\Resource::create([
            'course_id' => $course->id,
            'resource_type_id' => $resourceType->id,
            'title' => 'Quantum Physics Comprehensive Summary',
            'status' => 'approved',
            'submitted_by' => $student->id,
        ]);

        // 1. User taps Search Resources button
        $this->invokeHandler('handleMessage', [
            ['chat' => ['id' => 999333], 'text' => '🔎 Search Resources'], $student,
        ]);

        $this->assertEquals(['step' => 'awaiting_search_query'], \Illuminate\Support\Facades\Cache::get("user_search_state:{$student->id}"));

        Http::assertSent(function (Request $request) {
            return str_ends_with($request->url(), '/sendMessage')
                && str_contains($request['text'], 'Search Materials & Courses');
        });

        // 2. User types search keyword "Quantum"
        $this->invokeHandler('handleMessage', [
            ['chat' => ['id' => 999333], 'text' => 'Quantum'], $student,
        ]);

        // Assert results were found and returned with inline keyboard containing the course and resource
        Http::assertSent(function (Request $request) use ($course, $resource) {
            if (! str_ends_with($request->url(), '/sendMessage')) {
                return false;
            }
            $keyboard = $request['reply_markup']['inline_keyboard'] ?? [];
            $flattened = array_column(array_merge(...$keyboard), 'callback_data');

            return in_array("course_{$course->id}_0_all", $flattened, true)
                && in_array("send_resource_{$resource->id}", $flattened, true);
        });
    }

    public function test_direct_search_command_executes_immediately(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $course = \App\Models\Course::firstOrCreate(
            ['course_code' => 'ALGO_SRCH'],
            ['name' => 'Distributed Algorithms', 'credits' => 8.0, 'is_active' => true]
        );

        $student = User::firstOrNew(['telegram_user_id' => 999444]);
        $student->forceFill(['first_name' => 'AlgoStudent', 'preferred_language' => 'en'])->save();

        $this->invokeHandler('handleMessage', [
            ['chat' => ['id' => 999444], 'text' => '/search Distributed'], $student,
        ]);

        Http::assertSent(function (Request $request) use ($course) {
            if (! str_ends_with($request->url(), '/sendMessage')) {
                return false;
            }
            $keyboard = $request['reply_markup']['inline_keyboard'] ?? [];
            $flattened = array_column(array_merge(...$keyboard), 'callback_data');

            return in_array("course_{$course->id}_0_all", $flattened, true);
        });
    }

    private function invokeHandler(string $method, array $arguments): void
    {
        $controller = app(TelegramWebhookController::class);
        (new ReflectionMethod($controller, $method))->invokeArgs($controller, $arguments);
    }
}
