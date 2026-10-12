<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Handlers\CallbackQueryHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TelegramDocumentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_document_is_sent_using_the_telegram_file_id(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $response = app(TelegramService::class)->sendDocument(42, 'telegram-file-id');

        $this->assertTrue($response->json('ok'));
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendDocument')
            && $request['chat_id'] === 42
            && $request['document'] === 'telegram-file-id');
        Http::assertSentCount(1);
    }

    public function test_telegram_failure_is_logged_and_returned(): void
    {
        Http::fake(['*' => Http::response(['ok' => false, 'description' => 'Invalid file'], 400)]);
        Log::shouldReceive('error')->once()->with('Telegram sendDocument failed', [
            'chat_id' => 42,
            'description' => 'Invalid file',
        ]);

        $response = app(TelegramService::class)->sendDocument(42, 'invalid-file');

        $this->assertFalse($response->json('ok'));
    }

    public function test_send_resource_with_drive_file_id_fallback_when_telegram_file_id_missing(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $program = Program::firstOrCreate(
            ['name' => 'Computer Engineering Test'],
            ['degree_level' => 'master', 'university' => 'PoliTo', 'is_active' => true]
        );

        $course = Course::firstOrCreate(
            ['course_code' => 'TEST_CRS'],
            ['name' => 'Computer Networks', 'credits' => 6.0, 'is_active' => true]
        );
        $course->programs()->syncWithoutDetaching([$program->id]);

        $type = ResourceType::firstOrCreate(
            ['code' => 'test-slides'],
            ['display_name' => 'Lecture Slides', 'is_active' => true]
        );

        $user = User::firstOrNew(['telegram_user_id' => 777111]);
        $user->forceFill([
            'first_name' => 'StudentUser',
            'preferred_language' => 'en',
            'program_id' => $program->id,
        ])->save();

        $resource = Resource::create([
            'course_id' => $course->id,
            'resource_type_id' => $type->id,
            'submitted_by' => $user->id,
            'title' => 'Lecture 1 Slides',
            'status' => 'approved',
        ]);

        ResourceFile::create([
            'resource_id' => $resource->id,
            'original_filename' => 'lecture1.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024 * 1024,
            'sha256' => hash('sha256', 'lecture1_content'),
            'telegram_file_id' => null,
            'drive_file_id' => '1AbCdEfGhIjKlMnOpQrStUvWxYz',
        ]);

        $handler = app(CallbackQueryHandler::class);
        $handler->handle([
            'id' => 'cb-drive-test',
            'data' => "send_resource_{$resource->id}",
            'message' => ['chat' => ['id' => 777111], 'message_id' => 999],
        ], $user);

        // Acknowledges callback query
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/answerCallbackQuery'));

        // Sends message with Google Drive link button
        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/sendMessage')) {
                return false;
            }

            $keyboard = $request['reply_markup']['inline_keyboard'] ?? [];
            if (empty($keyboard)) {
                return false;
            }

            $driveBtn = $keyboard[0][0] ?? null;

            return $driveBtn !== null
                && ($driveBtn['url'] ?? '') === 'https://drive.google.com/file/d/1AbCdEfGhIjKlMnOpQrStUvWxYz/view'
                && str_contains($request['text'], 'Lecture 1 Slides');
        });
    }

    public function test_send_resource_with_telegram_file_id_sends_document_directly(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $program = Program::firstOrCreate(
            ['name' => 'Computer Engineering Test 2'],
            ['degree_level' => 'master', 'university' => 'PoliTo', 'is_active' => true]
        );

        $course = Course::firstOrCreate(
            ['course_code' => 'TEST_CRS2'],
            ['name' => 'Calculus 1', 'credits' => 8.0, 'is_active' => true]
        );
        $course->programs()->syncWithoutDetaching([$program->id]);

        $type = ResourceType::firstOrCreate(
            ['code' => 'test-exam'],
            ['display_name' => 'Past Exams', 'is_active' => true]
        );

        $user = User::firstOrNew(['telegram_user_id' => 777222]);
        $user->forceFill([
            'first_name' => 'Alice',
            'preferred_language' => 'en',
            'program_id' => $program->id,
        ])->save();

        $resource = Resource::create([
            'course_id' => $course->id,
            'resource_type_id' => $type->id,
            'submitted_by' => $user->id,
            'title' => 'Exam 2024 Solution',
            'status' => 'approved',
        ]);

        ResourceFile::create([
            'resource_id' => $resource->id,
            'original_filename' => 'exam2024.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 512 * 1024,
            'sha256' => hash('sha256', 'exam2024_content'),
            'telegram_file_id' => 'tg_file_id_exam_999',
            'drive_file_id' => 'drive_backup_id_999',
        ]);

        $handler = app(CallbackQueryHandler::class);
        $handler->handle([
            'id' => 'cb-tg-test',
            'data' => "send_resource_{$resource->id}",
            'message' => ['chat' => ['id' => 777222], 'message_id' => 1000],
        ], $user);

        // Document sent directly via sendDocument
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendDocument')
            && $request['chat_id'] === 777222
            && $request['document'] === 'tg_file_id_exam_999');
    }
}
