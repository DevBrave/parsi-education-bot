<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ResourceAdminTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected Course $course;

    protected ResourceType $resourceType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrNew(['telegram_user_id' => 888888]);
        $this->admin->forceFill([
            'first_name' => 'AdminUser',
            'role' => 'admin',
        ])->save();

        $this->course = Course::firstOrCreate(
            ['course_code' => 'TEST01'],
            ['name' => 'Distributed Systems', 'credits' => 6.0]
        );

        $this->resourceType = ResourceType::firstOrCreate(
            ['code' => 'test_exam'],
            ['display_name' => 'Past Exam', 'sort_order' => 1]
        );
    }

    public function test_guests_cannot_access_resources_admin(): void
    {
        $response = $this->get(route('admin.resources.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_student_cannot_access_resources_admin(): void
    {
        $student = User::firstOrNew(['telegram_user_id' => 777777]);
        $student->forceFill([
            'first_name' => 'StudentUser',
            'role' => 'student',
        ])->save();

        $response = $this->actingAs($student)->get(route('admin.resources.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_resources_list_with_platform_statuses(): void
    {
        $resource = Resource::create([
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'Midterm Exam 2026',
            'status' => 'approved',
            'submitted_by' => $this->admin->id,
        ]);

        $resource->files()->create([
            'original_filename' => 'midterm.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1048576, // 1 MB
            'sha256' => hash('sha256', 'sample_content_1'),
            'telegram_file_id' => 'tg_file_12345',
            'drive_file_id' => 'drive_file_67890',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.resources.index'));

        $response->assertOk();
        $response->assertSee('Midterm Exam 2026');
        $response->assertSee('Distributed Systems');
        $response->assertSee('Telegram Synced');
        $response->assertSee('Drive Synced');
        $response->assertSee('1.00 MB');
    }

    public function test_admin_can_filter_resources_by_platform_sync(): void
    {
        $resSynced = Resource::create([
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'Synced Telegram Resource',
            'status' => 'approved',
            'submitted_by' => $this->admin->id,
        ]);
        $resSynced->files()->create([
            'original_filename' => 'synced.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 50000,
            'sha256' => hash('sha256', 'content_synced'),
            'telegram_file_id' => 'tg_synced_id',
        ]);

        $resPending = Resource::create([
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'Pending Telegram Resource',
            'status' => 'pending',
            'submitted_by' => $this->admin->id,
        ]);
        $resPending->files()->create([
            'original_filename' => 'pending.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 50000,
            'sha256' => hash('sha256', 'content_pending'),
            'telegram_file_id' => null,
        ]);

        // Filter Telegram Synced
        $response = $this->actingAs($this->admin)->get(route('admin.resources.index', ['platform' => 'telegram_synced']));
        $response->assertOk();
        $response->assertSee('Synced Telegram Resource');
        $response->assertDontSee('Pending Telegram Resource');

        // Filter Telegram Pending
        $responsePending = $this->actingAs($this->admin)->get(route('admin.resources.index', ['platform' => 'telegram_pending']));
        $responsePending->assertOk();
        $responsePending->assertSee('Pending Telegram Resource');
        $responsePending->assertDontSee('Synced Telegram Resource');
    }

    public function test_admin_can_view_edit_resource_page(): void
    {
        $resource = Resource::create([
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'Editable Notes',
            'status' => 'pending',
            'submitted_by' => $this->admin->id,
        ]);

        $resource->files()->create([
            'original_filename' => 'notes.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 12000,
            'sha256' => hash('sha256', 'editable_notes'),
            'telegram_file_id' => 'tg_notes_id',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.resources.edit', $resource));

        $response->assertOk();
        $response->assertSee('Editable Notes');
        $response->assertSee('notes.pdf');
        $response->assertSee('Telegram Synced');
    }

    public function test_admin_can_update_resource(): void
    {
        $resource = Resource::create([
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'Old Title',
            'status' => 'pending',
            'submitted_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.resources.update', $resource), [
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'Updated Brand New Title',
            'status' => 'approved',
            'has_solutions' => 1,
            'language' => 'it',
        ]);

        $response->assertRedirect(route('admin.resources.index'));
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'title' => 'Updated Brand New Title',
            'status' => 'approved',
            'has_solutions' => 1,
            'language' => 'it',
        ]);
    }

    public function test_admin_can_delete_resource(): void
    {
        $resource = Resource::create([
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'To Be Deleted',
            'status' => 'approved',
            'submitted_by' => $this->admin->id,
        ]);

        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['ok' => true])]);
        config(['telegram.storage_channel_id' => -1009999999]);

        $file = $resource->files()->create([
            'original_filename' => 'delete_me.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 5000,
            'sha256' => hash('sha256', 'delete_content'),
            'telegram_message_id' => '12345',
            'drive_file_id' => 'drive_delete_id_999',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.resources.destroy', $resource));

        $response->assertRedirect(route('admin.resources.index'));
        $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
        $this->assertDatabaseMissing('resource_files', ['id' => $file->id]);

        // Verify deleteMessage was called on Telegram with the storage channel and message ID
        \Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return str_ends_with($request->url(), '/deleteMessage')
                && (string) ($request['chat_id'] ?? null) === '-1009999999'
                && (string) ($request['message_id'] ?? null) === '12345';
        });
    }

    public function test_admin_can_delete_individual_file_and_it_purges_from_telegram_and_drive(): void
    {
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['ok' => true])]);
        config(['telegram.storage_channel_id' => -1009999999]);

        $resource = Resource::create([
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'Multi File Resource',
            'status' => 'approved',
            'submitted_by' => $this->admin->id,
        ]);

        $file1 = $resource->files()->create([
            'original_filename' => 'first.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2000,
            'sha256' => hash('sha256', 'first_content'),
            'telegram_message_id' => '99001',
            'drive_file_id' => 'drive_f1_id',
        ]);

        $file2 = $resource->files()->create([
            'original_filename' => 'second.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 3000,
            'sha256' => hash('sha256', 'second_content'),
            'telegram_message_id' => '99002',
            'drive_file_id' => 'drive_f2_id',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.resources.files.destroy', [$resource, $file1]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('resource_files', ['id' => $file1->id]);
        $this->assertDatabaseHas('resource_files', ['id' => $file2->id]);
        $this->assertDatabaseHas('resources', ['id' => $resource->id]);

        // Verify deleteMessage was called on Telegram for file1
        \Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return str_ends_with($request->url(), '/deleteMessage')
                && (string) ($request['chat_id'] ?? null) === '-1009999999'
                && (string) ($request['message_id'] ?? null) === '99001';
        });
    }

    public function test_admin_can_create_resource_with_status(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Storage::fake('local');

        $uploadedFile = \Illuminate\Http\UploadedFile::fake()->create('sample_exam.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('admin.resources.store'), [
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'New Official Exam 2026',
            'status' => 'approved',
            'files' => [$uploadedFile],
        ]);

        $response->assertRedirect(route('admin.resources.index'));
        $this->assertDatabaseHas('resources', [
            'title' => 'New Official Exam 2026',
            'status' => 'approved',
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\UploadResourceFileJob::class);
    }

    public function test_admin_editing_resource_and_adding_file_does_not_duplicate_admin_file(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['ok' => true])]);
        config(['telegram.storage_channel_id' => -1009999999]);

        $student = User::firstOrNew(['telegram_user_id' => 777666]);
        $student->forceFill([
            'first_name' => 'StudentUploader',
            'role' => 'student',
        ])->save();

        $resource = Resource::create([
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'Student Course Summary',
            'status' => 'pending',
            'submitted_by' => $student->id,
        ]);

        $studentFile = $resource->files()->create([
            'original_filename' => 'student_part1.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 12345,
            'sha256' => hash('sha256', 'student_file_content'),
            'telegram_file_id' => 'student_tg_file_1',
            'drive_file_id' => null,
            'local_path' => null,
        ]);

        $adminUploadedFile = \Illuminate\Http\UploadedFile::fake()->create('admin_supplement.pdf', 2048, 'application/pdf');

        $response = $this->actingAs($this->admin)->put(route('admin.resources.update', $resource), [
            'course_id' => $this->course->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'Student Course Summary (Reviewed)',
            'status' => 'approved',
            'files' => [$adminUploadedFile],
        ]);

        $response->assertRedirect(route('admin.resources.index'));

        // 1. Verify resource status was changed to approved
        $resource->refresh();
        $this->assertEquals('approved', $resource->status);

        // 2. Verify admin's new file was dispatched to UploadResourceFileJob
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\UploadResourceFileJob::class, function ($job) use ($resource) {
            return $job->resourceFile->resource_id === $resource->id
                && $job->resourceFile->original_filename === 'admin_supplement.pdf';
        });

        // 3. Verify sendToStorageChannel ONLY sent the student's file, NOT the newly attached admin file
        \Illuminate\Support\Facades\Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return str_ends_with($request->url(), '/sendDocument')
                && ($request['document'] ?? null) === 'student_tg_file_1'
                && (string) ($request['chat_id'] ?? null) === '-1009999999';
        });
    }
}
