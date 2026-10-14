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
        $response->assertForbidden();
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

        $file = $resource->files()->create([
            'original_filename' => 'delete_me.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 5000,
            'sha256' => hash('sha256', 'delete_content'),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.resources.destroy', $resource));

        $response->assertRedirect(route('admin.resources.index'));
        $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
        $this->assertDatabaseMissing('resource_files', ['id' => $file->id]);
    }
}
