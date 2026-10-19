<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceReport;
use App\Models\ResourceType;
use App\Models\User;
use App\Telegram\Handlers\CallbackQueryHandler;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportAdminTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $moderator;
    protected User $profCompEng;
    protected User $viewer;
    protected User $student;
    protected Program $progCompEng;
    protected Program $progMechEng;
    protected Course $courseCompEng;
    protected Course $courseMechEng;
    protected Resource $resCompEng;
    protected Resource $resMechEng;

    protected function setUp(): void
    {
        parent::setUp();

        $this->progCompEng = Program::firstOrCreate(
            ['name' => 'Computer Engineering', 'degree_level' => 'Master'],
            ['is_active' => true, 'university' => 'Politecnico di Torino']
        );

        $this->progMechEng = Program::firstOrCreate(
            ['name' => 'Mechanical Engineering', 'degree_level' => 'Master'],
            ['is_active' => true, 'university' => 'Politecnico di Torino']
        );

        $this->courseCompEng = Course::firstOrCreate(
            ['course_code' => '01TESTCE'],
            ['name' => 'Computer Networks', 'year' => 1, 'is_active' => true]
        );
        $this->courseCompEng->programs()->syncWithoutDetaching([$this->progCompEng->id]);

        $this->courseMechEng = Course::firstOrCreate(
            ['course_code' => '02TESTME'],
            ['name' => 'Thermodynamics', 'year' => 1, 'is_active' => true]
        );
        $this->courseMechEng->programs()->syncWithoutDetaching([$this->progMechEng->id]);

        $type = ResourceType::firstOrCreate(
            ['code' => 'notes_test'],
            ['display_name' => 'Notes Test', 'is_active' => true]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_report_test@parsi.polito.it'],
            [
                'first_name' => 'Admin',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        $this->moderator = User::firstOrCreate(
            ['email' => 'moderator_report_test@parsi.polito.it'],
            [
                'first_name' => 'Mod',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_MODERATOR,
                'is_active' => true,
            ]
        );

        $this->profCompEng = User::firstOrCreate(
            ['email' => 'prof_ce_report_test@parsi.polito.it'],
            [
                'first_name' => 'ProfCE',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_PROFESSOR,
                'program_id' => $this->progCompEng->id,
                'is_active' => true,
            ]
        );

        $this->viewer = User::firstOrCreate(
            ['email' => 'viewer_report_test@parsi.polito.it'],
            [
                'first_name' => 'Viewer',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_VIEWER,
                'is_active' => true,
            ]
        );

        $this->student = User::firstOrCreate(
            ['telegram_user_id' => 999111222],
            [
                'first_name' => 'StudentUser',
                'username' => 'studuser',
                'role' => User::ROLE_STUDENT,
                'preferred_language' => 'en',
                'is_active' => true,
            ]
        );

        $this->resCompEng = Resource::create([
            'course_id' => $this->courseCompEng->id,
            'resource_type_id' => $type->id,
            'title' => 'Corrupted Networks Cheatsheet',
            'submitted_by' => $this->admin->id,
            'status' => 'approved',
        ]);

        $this->resMechEng = Resource::create([
            'course_id' => $this->courseMechEng->id,
            'resource_type_id' => $type->id,
            'title' => 'Faulty Thermo Exam',
            'submitted_by' => $this->admin->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_view_reports_index_and_details(): void
    {
        $report = ResourceReport::create([
            'resource_id' => $this->resCompEng->id,
            'user_id' => $this->student->id,
            'reason' => ResourceReport::REASON_CORRUPTED,
            'details' => 'PDF gives an error when opened on Acrobat',
            'status' => ResourceReport::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Corrupted Networks Cheatsheet');
        $response->assertSee('StudentUser');

        $showResponse = $this->actingAs($this->admin)->get(route('admin.reports.show', $report));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('PDF gives an error when opened on Acrobat');
    }

    public function test_moderator_can_resolve_and_dismiss_reports(): void
    {
        $report = ResourceReport::create([
            'resource_id' => $this->resCompEng->id,
            'user_id' => $this->student->id,
            'reason' => ResourceReport::REASON_WRONG_COURSE,
            'status' => ResourceReport::STATUS_PENDING,
        ]);

        // Resolve
        $response = $this->actingAs($this->moderator)->post(route('admin.reports.resolve', $report), [
            'admin_notes' => 'Fixed the course assignment',
        ]);
        $response->assertRedirect(route('admin.reports.index'));
        $this->assertEquals(ResourceReport::STATUS_RESOLVED, $report->fresh()->status);
        $this->assertEquals($this->moderator->id, $report->fresh()->resolved_by);

        // Verify show page renders 200 on resolved report
        $showResolved = $this->actingAs($this->admin)->get(route('admin.reports.show', $report->fresh()));
        $showResolved->assertStatus(200);
        $showResolved->assertSee('Resolution Info');

        // Dismiss
        $response = $this->actingAs($this->moderator)->post(route('admin.reports.dismiss', $report), [
            'admin_notes' => 'False alarm',
        ]);
        $response->assertRedirect(route('admin.reports.index'));
        $this->assertEquals(ResourceReport::STATUS_DISMISSED, $report->fresh()->status);
    }

    public function test_professor_scoped_to_assigned_major_reports(): void
    {
        $reportCE = ResourceReport::create([
            'resource_id' => $this->resCompEng->id,
            'user_id' => $this->student->id,
            'reason' => ResourceReport::REASON_CORRUPTED,
            'status' => ResourceReport::STATUS_PENDING,
        ]);

        $reportME = ResourceReport::create([
            'resource_id' => $this->resMechEng->id,
            'user_id' => $this->student->id,
            'reason' => ResourceReport::REASON_POOR_QUALITY,
            'status' => ResourceReport::STATUS_PENDING,
        ]);

        // Professor views reports list
        $response = $this->actingAs($this->profCompEng)->get(route('admin.reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Corrupted Networks Cheatsheet');
        $response->assertDontSee('Faulty Thermo Exam');

        // Can manage CE report
        $res = $this->actingAs($this->profCompEng)->post(route('admin.reports.resolve', $reportCE));
        $res->assertRedirect(route('admin.reports.index'));

        // Cannot manage ME report
        $fail = $this->actingAs($this->profCompEng)->post(route('admin.reports.resolve', $reportME));
        $fail->assertStatus(403);
    }

    public function test_viewer_has_read_only_access(): void
    {
        $report = ResourceReport::create([
            'resource_id' => $this->resCompEng->id,
            'user_id' => $this->student->id,
            'reason' => ResourceReport::REASON_CORRUPTED,
            'status' => ResourceReport::STATUS_PENDING,
        ]);

        // Can view list & show
        $this->actingAs($this->viewer)->get(route('admin.reports.index'))->assertStatus(200);
        $this->actingAs($this->viewer)->get(route('admin.reports.show', $report))->assertStatus(200);

        // Cannot resolve
        $this->actingAs($this->viewer)->post(route('admin.reports.resolve', $report))->assertStatus(403);
    }

    public function test_telegram_student_reporting_flow(): void
    {
        $file = \App\Models\ResourceFile::create([
            'resource_id' => $this->resCompEng->id,
            'original_filename' => 'CorruptedNetworks.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'sha256' => hash('sha256', 'test'),
            'telegram_file_id' => 'tg_file_test_123',
        ]);

        $handler = app(CallbackQueryHandler::class);

        // 1. Student submits a file-specific report via Telegram callback
        $callbackQuery = [
            'id' => 'cb_123',
            'data' => "report_submit_{$this->resCompEng->id}_{$file->id}_corrupted",
            'message' => [
                'chat' => ['id' => 999111222],
                'message_id' => 5001,
            ],
        ];

        $handler->handle($callbackQuery, $this->student);

        // Check report in database with resource_file_id
        $report = ResourceReport::where('resource_id', $this->resCompEng->id)
            ->where('resource_file_id', $file->id)
            ->where('user_id', $this->student->id)
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals($file->id, $report->resource_file_id);
        $this->assertEquals(ResourceReport::REASON_CORRUPTED, $report->reason);
        $this->assertEquals(ResourceReport::STATUS_PENDING, $report->status);

        // Verify that the Edit Resource panel displays the red cross icon ❌ next to the reported file
        $editResponse = $this->actingAs($this->admin)->get(route('admin.resources.edit', $this->resCompEng));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('❌');
        $editResponse->assertSee('Reported Issue (1)');
    }

    public function test_telegram_contact_report_menu_and_feedback_flow(): void
    {
        $msgHandler = app(\App\Telegram\Handlers\MessageHandler::class);

        // 1. Student taps "⚠️ Contact / Report"
        $msgHandler->handle([
            'chat' => ['id' => 999111222],
            'text' => '⚠️ Contact / Report',
        ], $this->student);

        // 2. Student starts writing feedback
        $cbHandler = app(CallbackQueryHandler::class);
        $cbHandler->handle([
            'id' => 'cb_contact_1',
            'data' => 'contact_prompt',
            'message' => [
                'chat' => ['id' => 999111222],
                'message_id' => 6001,
            ],
        ], $this->student);

        $this->assertEquals('awaiting_contact_message', \Illuminate\Support\Facades\Cache::get("user_contact_state:{$this->student->id}")['step'] ?? null);

        // 3. Student submits feedback text
        $msgHandler->handle([
            'chat' => ['id' => 999111222],
            'text' => 'Hello team, the new bot search feature is amazing!',
        ], $this->student);

        $this->assertNull(\Illuminate\Support\Facades\Cache::get("user_contact_state:{$this->student->id}"));
    }

    public function test_destroy_resource_deletes_only_reported_file_when_other_files_exist(): void
    {
        $file1 = \App\Models\ResourceFile::create([
            'resource_id' => $this->resCompEng->id,
            'original_filename' => 'BadFile.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'sha256' => hash('sha256', 'bad1'),
        ]);

        $file2 = \App\Models\ResourceFile::create([
            'resource_id' => $this->resCompEng->id,
            'original_filename' => 'GoodFile.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
            'sha256' => hash('sha256', 'good2'),
        ]);

        $report = ResourceReport::create([
            'resource_id' => $this->resCompEng->id,
            'resource_file_id' => $file1->id,
            'user_id' => $this->student->id,
            'reason' => ResourceReport::REASON_CORRUPTED,
            'status' => ResourceReport::STATUS_PENDING,
        ]);

        // Super Admin deletes via report
        $response = $this->actingAs($this->admin)->delete(route('admin.reports.resource.destroy', $report));
        $response->assertRedirect(route('admin.reports.index'));
        $response->assertSessionHas('success');

        // File 1 is deleted
        $this->assertDatabaseMissing('resource_files', ['id' => $file1->id]);

        // File 2 still exists
        $this->assertDatabaseHas('resource_files', ['id' => $file2->id]);

        // The parent resource still exists
        $this->assertDatabaseHas('resources', ['id' => $this->resCompEng->id]);

        // The report is marked as resolved
        $this->assertEquals(ResourceReport::STATUS_RESOLVED, $report->fresh()->status);
    }
}
