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

    public function test_album_upload_updates_resource_description_when_caption_arrives_on_subsequent_file(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $course = Course::firstOrCreate(
            ['course_code' => 'ALBUM_TEST'],
            ['name' => 'Operating Systems', 'credits' => 6.0, 'is_active' => true]
        );

        $user = User::firstOrCreate(
            ['telegram_user_id' => 999111],
            ['first_name' => 'Uploader', 'role' => 'student', 'is_onboarded' => true]
        );

        // Simulate user selected course and specified title
        \Illuminate\Support\Facades\Cache::put("user_upload_state:{$user->id}", [
            'course_id' => $course->id,
            'title' => '20250127',
        ], now()->addMinutes(5));

        $uploadHandler = app(\App\Telegram\Handlers\FileUploadHandler::class);

        // 1. First file arrives WITHOUT caption
        $uploadHandler->handle([
            'chat' => ['id' => 999111],
            'document' => [
                'file_id' => 'file_part_1',
                'file_unique_id' => 'uniq_1',
                'file_name' => '20250127.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1000,
            ],
        ], $user);

        $state = \Illuminate\Support\Facades\Cache::get("user_upload_state:{$user->id}");
        $this->assertNotNull($state['resource_id'] ?? null);
        $resource = Resource::with('files')->find($state['resource_id']);
        $this->assertEquals('20250127', $resource->title);
        $this->assertCount(1, $resource->files);

        // 2. Second file arrives WITH caption (as is common in Telegram albums)
        $uploadHandler->handle([
            'chat' => ['id' => 999111],
            'document' => [
                'file_id' => 'file_part_2',
                'file_unique_id' => 'uniq_2',
                'file_name' => '20250127_sol.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1200,
            ],
            'caption' => '2025 Exams with solutions',
        ], $user);

        $resource->refresh();
        $this->assertEquals('20250127', $resource->title);
        $this->assertEquals('2025 Exams with solutions', $resource->description);
        $this->assertCount(2, $resource->files);
    }

    public function test_send_album_of_documents_with_caption_to_telegram(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        config(['telegram.admin_chat_id' => -1001234567890]);

        $course = Course::firstOrCreate(
            ['course_code' => 'ALBUM_TG_TEST'],
            ['name' => 'Algorithms and Data Structures', 'credits' => 8.0, 'is_active' => true]
        );

        $user = User::firstOrCreate(
            ['telegram_user_id' => 888123],
            ['first_name' => 'StudentUploader', 'username' => 'student_user', 'role' => 'student', 'is_onboarded' => true]
        );

        // 1. User starts submission session by choosing course and title
        \Illuminate\Support\Facades\Cache::put("user_upload_state:{$user->id}", [
            'course_id' => $course->id,
            'title' => '📚 Complete Algorithms 2025 Notes',
        ], now()->addMinutes(5));

        $uploadHandler = app(\App\Telegram\Handlers\FileUploadHandler::class);
        $mediaGroupId = 'album_group_987654321';

        // 2. File 1 of album arrives (Telegram sends it without caption)
        $uploadHandler->handle([
            'chat' => ['id' => 888123],
            'media_group_id' => $mediaGroupId,
            'document' => [
                'file_id' => 'tg_file_part_1',
                'file_unique_id' => 'uniq_doc_1',
                'file_name' => 'algorithms_lecture_part1.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 102400,
            ],
        ], $user);

        // 3. File 2 of album arrives (Telegram attaches the album caption to this file)
        $albumCaption = "📚 Complete Algorithms 2025 Notes\nAll chapters included with exam tips";
        $uploadHandler->handle([
            'chat' => ['id' => 888123],
            'media_group_id' => $mediaGroupId,
            'document' => [
                'file_id' => 'tg_file_part_2',
                'file_unique_id' => 'uniq_doc_2',
                'file_name' => 'algorithms_lecture_part2.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 204800,
            ],
            'caption' => $albumCaption,
        ], $user);

        $state = \Illuminate\Support\Facades\Cache::get("user_upload_state:{$user->id}");
        $resource = Resource::with('files')->find($state['resource_id']);

        // Assert Title is the first line of the caption
        $this->assertEquals('📚 Complete Algorithms 2025 Notes', $resource->title);
        // Assert Description is the entire caption
        $this->assertEquals($albumCaption, $resource->description);
        $this->assertCount(2, $resource->files);

        // 4. User finishes the upload (taps Done)
        $callbackHandler = app(\App\Telegram\Handlers\CallbackQueryHandler::class);
        $callbackHandler->handle([
            'id' => 'cb_finish_test',
            'data' => 'finish_upload',
            'message' => ['chat' => ['id' => 888123], 'message_id' => 9999],
        ], $user);

        // 5. Inspect what Telegram API received for the admin notification
        $adminTelegramRequests = Http::recorded(function (Request $request) {
            $data = $request->data();
            return isset($data['chat_id']) && (string) $data['chat_id'] === '-1001234567890';
        });

        $this->assertTrue($adminTelegramRequests->isNotEmpty(), 'An admin notification should have been sent to Telegram.');

        $firstPair = $adminTelegramRequests->first();
        $adminRequest = $firstPair[0];
        $adminPayload = $adminRequest->data();
        $sentCaption = $adminPayload['caption'] ?? $adminPayload['text'] ?? '';

        // Assert the caption sent to Telegram contains the Title, Description, and filenames
        $this->assertStringContainsString('📚 Complete Algorithms 2025 Notes', $sentCaption);
        $this->assertStringContainsString('algorithms_lecture_part1.pdf', $sentCaption);
        $this->assertStringContainsString('algorithms_lecture_part2.pdf', $sentCaption);

        echo PHP_EOL . "--- RESULT OF ALBUM UPLOAD TEST ---" . PHP_EOL;
        echo "Extracted Resource Title: " . $resource->title . PHP_EOL;
        echo "Extracted Resource Description: " . $resource->description . PHP_EOL;
        echo "Total Attached Files: " . $resource->files->count() . PHP_EOL;
        echo "Telegram Channel/Admin Notification:" . PHP_EOL;
        echo $sentCaption . PHP_EOL;
        echo "-----------------------------------" . PHP_EOL;
    }

    public function test_admin_approval_and_rejection_via_telegram_notifies_student_in_their_language(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $admin = User::firstOrNew(['telegram_user_id' => 999000]);
        $admin->forceFill([
            'first_name' => 'AdminMod',
            'role' => 'admin',
        ])->save();

        $student = User::firstOrCreate(
            ['telegram_user_id' => 888777],
            ['first_name' => 'PersianStudent', 'preferred_language' => 'fa']
        );

        $course = Course::firstOrCreate(
            ['course_code' => 'NOTIF_TEST'],
            ['name' => 'Data Science', 'credits' => 6.0, 'is_active' => true]
        );

        $type = ResourceType::firstOrCreate(
            ['code' => 'notes'],
            ['display_name' => 'Notes', 'sort_order' => 1]
        );

        $resource = Resource::create([
            'course_id' => $course->id,
            'resource_type_id' => $type->id,
            'submitted_by' => $student->id,
            'title' => 'Lecture Notes 2024',
            'status' => 'pending',
        ]);

        $callbackHandler = app(CallbackQueryHandler::class);

        // 1. Admin clicks [ ✅ Approve & Publish ]
        $callbackHandler->handle([
            'id' => 'cb_mod_approve',
            'data' => "admin_approve_{$resource->id}",
            'message' => [
                'chat' => ['id' => -1001234567890, 'type' => 'supergroup'],
                'message_id' => 7777,
                'caption' => "📥 New Material Uploaded\n\n⏳ Status: pending review",
            ],
        ], $admin);

        $resource->refresh();
        $this->assertEquals('approved', $resource->status);
        $this->assertEquals($admin->id, $resource->reviewed_by);

        // Verify student received the localized approval notification
        Http::assertSent(function (Request $request) use ($student) {
            $data = $request->data();
            return isset($data['chat_id'])
                && (int) $data['chat_id'] === $student->telegram_user_id
                && str_contains($data['text'] ?? '', 'خبر خوب');
        });

        // 2. Test rejection notification with another pending resource
        $resource2 = Resource::create([
            'course_id' => $course->id,
            'resource_type_id' => $type->id,
            'submitted_by' => $student->id,
            'title' => 'Incomplete Material',
            'status' => 'pending',
        ]);

        $callbackHandler->handle([
            'id' => 'cb_mod_reject',
            'data' => "admin_reject_{$resource2->id}",
            'message' => [
                'chat' => ['id' => -1001234567890, 'type' => 'supergroup'],
                'message_id' => 7778,
                'caption' => "📥 New Material Uploaded\n\n⏳ Status: pending review",
            ],
        ], $admin);

        $resource2->refresh();
        $this->assertEquals('rejected', $resource2->status);

        // Verify student received the rejection notification
        Http::assertSent(function (Request $request) use ($student) {
            $data = $request->data();
            return isset($data['chat_id'])
                && (int) $data['chat_id'] === $student->telegram_user_id
                && str_contains($data['text'] ?? '', 'اطلاعیه فایل ارسالی');
        });
    }

    public function test_submission_wizard_shows_resource_types_and_saves_selection_to_cache(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $program = Program::firstOrCreate(
            ['name' => 'Data Science & AI'],
            ['degree_level' => 'master', 'university' => 'PoliTo', 'is_active' => true]
        );

        $course = Course::firstOrCreate(
            ['course_code' => 'DS_AI_101'],
            ['name' => 'Machine Learning', 'credits' => 8.0, 'is_active' => true]
        );
        $course->programs()->syncWithoutDetaching([$program->id]);

        $pastExamType = ResourceType::firstOrCreate(
            ['code' => 'past-exams'],
            ['display_name' => 'Past Exams', 'sort_order' => 1, 'is_active' => true]
        );

        $student = User::firstOrCreate(
            ['telegram_user_id' => 777999],
            ['first_name' => 'DataStudent', 'preferred_language' => 'en', 'program_id' => $program->id]
        );

        $callbackHandler = app(CallbackQueryHandler::class);

        // 1. Student selects course in submit wizard
        $callbackHandler->handle([
            'id' => 'cb_sub_course',
            'data' => "submit_course_{$course->id}_{$program->id}_1",
            'message' => ['chat' => ['id' => 777999], 'message_id' => 101],
        ], $student);

        // Assert editMessageText was sent offering resource type buttons
        Http::assertSent(function (Request $request) use ($pastExamType, $course) {
            if (! str_ends_with($request->url(), '/editMessageText')) {
                return false;
            }
            $keyboard = $request['reply_markup']['inline_keyboard'] ?? [];
            $flattenedCallbacks = array_column(array_merge(...$keyboard), 'callback_data');

            return in_array("submit_type_{$course->id}_{$pastExamType->id}", $flattenedCallbacks, true);
        });

        // 2. Student selects resource type (Past Exams)
        $callbackHandler->handle([
            'id' => 'cb_sub_type',
            'data' => "submit_type_{$course->id}_{$pastExamType->id}",
            'message' => ['chat' => ['id' => 777999], 'message_id' => 101],
        ], $student);

        // Verify state is stored in cache with step awaiting_title and resource_type_id set
        $state = \Illuminate\Support\Facades\Cache::get("user_upload_state:{$student->id}");
        $this->assertNotNull($state);
        $this->assertEquals($course->id, $state['course_id']);
        $this->assertEquals($pastExamType->id, $state['resource_type_id']);
        $this->assertEquals('awaiting_title', $state['step']);
    }

    public function test_submission_multi_file_updates_single_feedback_message(): void
    {
        Http::fake([
            '*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 8888]]),
            '*' => Http::response(['ok' => true]),
        ]);

        $course = Course::firstOrCreate(
            ['course_code' => 'MULTI_EDIT_TEST'],
            ['name' => 'Computer Security', 'credits' => 6.0, 'is_active' => true]
        );

        $student = User::firstOrCreate(
            ['telegram_user_id' => 999888],
            ['first_name' => 'SecStudent', 'preferred_language' => 'en']
        );

        \Illuminate\Support\Facades\Cache::put("user_upload_state:{$student->id}", [
            'course_id' => $course->id,
            'title' => 'Security Lab Report',
            'step' => 'awaiting_file',
        ], now()->addMinutes(10));

        $uploadHandler = app(\App\Telegram\Handlers\FileUploadHandler::class);

        // 1. First file arrives
        $uploadHandler->handle([
            'chat' => ['id' => 999888],
            'document' => [
                'file_id' => 'tg_sec_file_1',
                'file_unique_id' => 'uniq_sec_1',
                'file_name' => 'report_part1.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 5000,
            ],
        ], $student);

        // Verifying sendMessage was called for the first file
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
            && (int) $request['chat_id'] === 999888);

        // Check that state has feedback_message_id = 8888
        $state = \Illuminate\Support\Facades\Cache::get("user_upload_state:{$student->id}");
        $this->assertEquals(8888, $state['feedback_message_id'] ?? null);

        // 2. Second file arrives in the same session
        $uploadHandler->handle([
            'chat' => ['id' => 999888],
            'document' => [
                'file_id' => 'tg_sec_file_2',
                'file_unique_id' => 'uniq_sec_2',
                'file_name' => 'report_part2.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 6000,
            ],
        ], $student);

        // Verifying editMessageText was called with message_id 8888 instead of a new sendMessage
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/editMessageText')
            && (int) $request['chat_id'] === 999888
            && (int) $request['message_id'] === 8888);
    }
}


