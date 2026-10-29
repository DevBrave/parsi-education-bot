<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use App\Telegram\TelegramUpdateDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramGroupSearchTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['telegram.bot_username' => 'parsi_politobot']);
    }

    public function test_group_mention_triggers_search_without_back_navigation(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $program = Program::create(['name' => 'Computer Engineering', 'degree_level' => 'MSc', 'is_active' => true]);
        $course = Course::create(['name' => 'Algorithms & Data Structures', 'course_code' => '01ALG', 'is_active' => true]);
        $course->programs()->attach($program->id);

        $dispatcher = app(TelegramUpdateDispatcher::class);

        $payload = [
            'message' => [
                'message_id' => 101,
                'chat' => [
                    'id' => -1001234567890,
                    'type' => 'supergroup',
                    'title' => 'Polito Students Group',
                ],
                'from' => [
                    'id' => 999888,
                    'first_name' => 'Sara',
                    'username' => 'sara_student',
                ],
                'text' => '@parsi_politobot Algorithms',
            ],
        ];

        $dispatcher->dispatch($payload);

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/sendMessage')) {
                return false;
            }

            $chatId = $request['chat_id'];
            $inlineKeyboard = $request['reply_markup']['inline_keyboard'] ?? [];

            // Verify message was sent to the group chat
            if ($chatId !== -1001234567890) {
                return false;
            }

            // Verify no back buttons in inline keyboard
            foreach ($inlineKeyboard as $row) {
                foreach ($row as $btn) {
                    if (in_array($btn['callback_data'] ?? '', ['back_main', 'search_prompt'], true)) {
                        return false;
                    }
                }
            }

            // Verify course button is present
            $hasCourseBtn = false;
            foreach ($inlineKeyboard as $row) {
                foreach ($row as $btn) {
                    if (str_contains($btn['text'] ?? '', 'Algorithms & Data Structures')) {
                        $hasCourseBtn = true;
                    }
                }
            }

            return $hasCourseBtn;
        });
    }

    public function test_inline_query_returns_courses_and_resources(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $user = User::create(['telegram_user_id' => 11223344, 'first_name' => 'Uploader']);
        $course = Course::create(['name' => 'Linear Algebra', 'course_code' => '01ALG', 'is_active' => true]);
        $type = ResourceType::firstOrCreate(['code' => 'exam'], ['display_name' => 'Exam', 'is_active' => true]);
        $resource = Resource::create([
            'title' => '2024 Winter Exam',
            'course_id' => $course->id,
            'resource_type_id' => $type->id,
            'status' => 'approved',
            'submitted_by' => $user->id,
        ]);

        $dispatcher = app(TelegramUpdateDispatcher::class);

        $payload = [
            'inline_query' => [
                'id' => 'query_abc_123',
                'from' => [
                    'id' => 999888,
                    'first_name' => 'Sara',
                    'username' => 'sara_student',
                ],
                'query' => 'Linear Algebra',
                'offset' => '',
            ],
        ];

        $dispatcher->dispatch($payload);

        Http::assertSent(function (Request $request) {
            return str_ends_with($request->url(), '/answerInlineQuery')
                && $request['inline_query_id'] === 'query_abc_123'
                && str_contains($request['results'], 'Linear Algebra');
        });
    }

    public function test_chosen_inline_result_increments_downloads_and_inline_shares_count(): void
    {
        $channelMock = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $channelMock->shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/Resource #\d+ selected via inline query/'), \Mockery::type('array'));

        \Illuminate\Support\Facades\Log::shouldReceive('channel')
            ->with('telegram')
            ->andReturn($channelMock);

        $user = User::create(['telegram_user_id' => 55667788, 'first_name' => 'Uploader2']);
        $course = Course::create(['name' => 'Physics I', 'course_code' => '01PHY', 'is_active' => true]);
        $type = ResourceType::firstOrCreate(['code' => 'notes'], ['display_name' => 'Notes', 'is_active' => true]);
        $resource = Resource::create([
            'title' => 'Mechanics Summary',
            'course_id' => $course->id,
            'resource_type_id' => $type->id,
            'status' => 'approved',
            'submitted_by' => $user->id,
            'downloads_count' => 5,
            'inline_shares_count' => 2,
        ]);

        $dispatcher = app(TelegramUpdateDispatcher::class);

        $payload = [
            'chosen_inline_result' => [
                'result_id' => "res_{$resource->id}",
                'from' => [
                    'id' => 123456,
                    'first_name' => 'Student',
                    'username' => 'polito_student',
                ],
                'query' => 'Physics',
            ],
        ];

        $dispatcher->dispatch($payload);

        $resource->refresh();
        $this->assertEquals(6, $resource->downloads_count);
        $this->assertEquals(3, $resource->inline_shares_count);
    }
}
