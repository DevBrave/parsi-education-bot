<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\LogAdminController;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogAdminTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $moderator;
    protected User $viewer;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_log_test@parsi.it'],
            [
                'first_name' => 'Admin',
                'username' => 'admin_logger',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('secret123'),
                'telegram_user_id' => 888001,
            ]
        );

        $this->moderator = User::firstOrCreate(
            ['email' => 'mod_log_test@parsi.it'],
            [
                'first_name' => 'Mod',
                'username' => 'mod_logger',
                'role' => User::ROLE_MODERATOR,
                'password' => Hash::make('secret123'),
                'telegram_user_id' => 888002,
            ]
        );

        $this->viewer = User::firstOrCreate(
            ['email' => 'viewer_log_test@parsi.it'],
            [
                'first_name' => 'Viewer',
                'username' => 'viewer_logger',
                'role' => User::ROLE_VIEWER,
                'password' => Hash::make('secret123'),
                'telegram_user_id' => 888003,
            ]
        );

        $this->student = User::firstOrCreate(
            ['telegram_user_id' => 888004],
            [
                'first_name' => 'Student',
                'username' => 'student_logger',
                'role' => User::ROLE_STUDENT,
            ]
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.logs.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_student_and_viewer_are_forbidden(): void
    {
        $responseStudent = $this->actingAs($this->student)->get(route('admin.logs.index'));
        $responseStudent->assertForbidden();

        $responseViewer = $this->actingAs($this->viewer)->get(route('admin.logs.index'));
        $responseViewer->assertForbidden();
    }

    public function test_admin_and_moderator_can_view_logs_dashboard(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(route('admin.logs.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Live System Logs');
        $responseAdmin->assertSee('Master Application');
        $responseAdmin->assertSee('Telegram Bot');
        $responseAdmin->assertSee('Google Drive');

        $responseMod = $this->actingAs($this->moderator)->get(route('admin.logs.index'));
        $responseMod->assertOk();
    }

    public function test_can_switch_channels(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.logs.index', ['channel' => 'telegram']));
        $response->assertOk();
        $response->assertSee('Telegram Bot');
    }

    public function test_can_download_log_file(): void
    {
        // Ensure log file exists
        $logPath = storage_path('logs/laravel.log');
        if (! File::exists($logPath)) {
            File::put($logPath, "[2026-10-20 15:00:00] local.INFO: Sample log entry\n");
        }

        $response = $this->actingAs($this->admin)->get(route('admin.logs.download', ['channel' => 'master']));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }

    public function test_only_super_admin_can_purge_log_file(): void
    {
        // Moderator cannot clear
        $responseMod = $this->actingAs($this->moderator)->post(route('admin.logs.clear', ['channel' => 'telegram']));
        $responseMod->assertForbidden();

        // Super Admin can clear
        $responseAdmin = $this->actingAs($this->admin)->post(route('admin.logs.clear', ['channel' => 'telegram']));
        $responseAdmin->assertRedirect();
        $responseAdmin->assertSessionHas('success');
    }

    public function test_parse_log_line_extracts_structured_data(): void
    {
        $controller = new LogAdminController();

        $line = '[2026-10-20 12:34:56] local.ERROR: Telegram webhook failed {"chat_id":12345,"reason":"blocked"}';
        $parsed = $controller->parseLogLine($line);

        $this->assertNotNull($parsed);
        $this->assertEquals('2026-10-20 12:34:56', $parsed['date']);
        $this->assertEquals('ERROR', $parsed['level']);
        $this->assertEquals('Telegram webhook failed', $parsed['message']);
        $this->assertEquals(12345, $parsed['context']['chat_id']);
        $this->assertEquals('blocked', $parsed['context']['reason']);
    }

    public function test_admin_can_poll_new_logs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        File::put($logPath, "[2026-10-20 12:00:00] local.INFO: Baseline log\n");
        $initialSize = File::size($logPath);

        // Append a new line
        File::append($logPath, "[2026-10-20 12:01:00] local.WARNING: New warning detected\n");

        $response = $this->actingAs($this->admin)->getJson(route('admin.logs.poll', [
            'channel' => 'master',
            'since_pos' => $initialSize,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'lines',
            'new_pos',
            'file_size',
            'file_size_formatted',
            'truncated',
        ]);

        $data = $response->json();
        $this->assertCount(1, $data['lines']);
        $this->assertEquals('WARNING', $data['lines'][0]['level']);
        $this->assertEquals('New warning detected', $data['lines'][0]['message']);
        $this->assertFalse($data['truncated']);
    }

    public function test_student_cannot_poll_logs(): void
    {
        $response = $this->actingAs($this->student)->getJson(route('admin.logs.poll'));
        $response->assertForbidden();
    }
}
