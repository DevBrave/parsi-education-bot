<?php

namespace Tests\Feature;

use App\Http\Controllers\TelegramWebhookController;
use App\Models\Course;
use App\Models\Program;
use App\Models\Track;
use App\Models\User;
use App\Telegram\Keyboards\ProgramMenuKeyboard;
use Database\Seeders\MasterTrackSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class MasterTrackTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_track_seeder_creates_msc_program_and_seven_tracks(): void
    {
        $this->seed(MasterTrackSeeder::class);

        $msc = Program::where('name', 'Computer Engineering')
            ->where('degree_level', 'MSc')
            ->first();

        $this->assertNotNull($msc);
        $this->assertCount(7, $msc->tracks);

        $expectedCodes = ['AIDA', 'AICPS', 'CGM', 'CNI', 'ESSD', 'HCI', 'SE'];
        $this->assertEqualsCanonicalizing($expectedCodes, $msc->tracks->pluck('code')->all());
    }

    public function test_program_with_tracks_shows_track_menu_on_selection(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $this->seed(MasterTrackSeeder::class);

        $msc = Program::where('name', 'Computer Engineering')->where('degree_level', 'MSc')->first();
        $oldProgram = Program::create(['name' => 'Automotive Engineering', 'degree_level' => 'BSc']);
        $user = User::factory()->create(['preferred_language' => 'en', 'program_id' => $oldProgram->id]);

        $this->invokeHandler('handleCallbackQuery', [
            [
                'id' => 'callback-prog',
                'data' => "prog_{$msc->id}",
                'message' => ['chat' => ['id' => 12345], 'message_id' => 99],
            ],
            $user,
        ]);

        Http::assertSent(function (Request $request) use ($msc) {
            if (! str_ends_with($request->url(), '/editMessageText')) {
                return false;
            }
            $keyboard = $request['reply_markup']['inline_keyboard'] ?? [];
            $buttons = collect($keyboard)->flatten(1);

            // Checks that track buttons are present
            $hasTrackButton = $buttons->contains(fn ($btn) => str_starts_with($btn['callback_data'] ?? '', 'track_'));
            $hasAllTracks = $buttons->contains(fn ($btn) => $btn['callback_data'] === "track_{$msc->id}_all");

            return $hasTrackButton && $hasAllTracks;
        });
    }

    public function test_selecting_track_updates_user_and_shows_years(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $this->seed(MasterTrackSeeder::class);

        $msc = Program::where('name', 'Computer Engineering')->where('degree_level', 'MSc')->first();
        $track = $msc->tracks->firstWhere('code', 'AIDA');
        $user = User::factory()->create(['preferred_language' => 'en', 'program_id' => $msc->id]);

        $this->invokeHandler('handleCallbackQuery', [
            [
                'id' => 'callback-track',
                'data' => "track_{$track->id}",
                'message' => ['chat' => ['id' => 12345], 'message_id' => 100],
            ],
            $user,
        ]);

        $user->refresh();
        $this->assertEquals($track->id, $user->track_id);

        Http::assertSent(function (Request $request) use ($msc) {
            if (! str_ends_with($request->url(), '/editMessageText')) {
                return false;
            }
            $keyboard = $request['reply_markup']['inline_keyboard'] ?? [];
            $buttons = collect($keyboard)->flatten(1);

            return $buttons->contains(fn ($btn) => str_starts_with($btn['callback_data'] ?? '', "course_year_{$msc->id}_"));
        });
    }

    public function test_course_filtering_respects_tracks_and_common_core_courses(): void
    {
        $this->seed(MasterTrackSeeder::class);
        Cache::flush();

        $msc = Program::where('name', 'Computer Engineering')->where('degree_level', 'MSc')->first();
        $trackAida = $msc->tracks->firstWhere('code', 'AIDA');
        $trackCni = $msc->tracks->firstWhere('code', 'CNI');

        // 1. Core Course (no track)
        $coreCourse = Course::create([
            'name' => 'Advanced Machine Learning Core',
            'course_code' => 'CORE01',
            'year' => 1,
            'is_active' => true,
        ]);
        $coreCourse->programs()->attach($msc->id);

        // 2. AIDA specific course
        $aidaCourse = Course::create([
            'name' => 'Deep Learning Analytics',
            'course_code' => 'AIDA01',
            'year' => 1,
            'is_active' => true,
        ]);
        $aidaCourse->programs()->attach($msc->id);
        $aidaCourse->tracks()->attach($trackAida->id);

        // 3. CNI specific course
        $cniCourse = Course::create([
            'name' => 'Cloud Networking Architecture',
            'course_code' => 'CNI01',
            'year' => 1,
            'is_active' => true,
        ]);
        $cniCourse->programs()->attach($msc->id);
        $cniCourse->tracks()->attach($trackCni->id);

        // AIDA student should see Core + AIDA (NOT CNI)
        $aidaKeyboard = ProgramMenuKeyboard::courses($msc, 'en', 1, 1, 10, $trackAida->id);
        $aidaButtons = collect($aidaKeyboard['inline_keyboard'])->flatten(1)->pluck('text')->all();

        $this->assertTrue(collect($aidaButtons)->contains(fn ($t) => str_contains($t, 'Advanced Machine Learning Core')));
        $this->assertTrue(collect($aidaButtons)->contains(fn ($t) => str_contains($t, 'Deep Learning Analytics')));
        $this->assertFalse(collect($aidaButtons)->contains(fn ($t) => str_contains($t, 'Cloud Networking Architecture')));

        // CNI student should see Core + CNI (NOT AIDA)
        $cniKeyboard = ProgramMenuKeyboard::courses($msc, 'en', 1, 1, 10, $trackCni->id);
        $cniButtons = collect($cniKeyboard['inline_keyboard'])->flatten(1)->pluck('text')->all();

        $this->assertTrue(collect($cniButtons)->contains(fn ($t) => str_contains($t, 'Advanced Machine Learning Core')));
        $this->assertTrue(collect($cniButtons)->contains(fn ($t) => str_contains($t, 'Cloud Networking Architecture')));
        $this->assertFalse(collect($cniButtons)->contains(fn ($t) => str_contains($t, 'Deep Learning Analytics')));

        // "All Tracks" student ($trackId = null) should see all 3 courses
        $allKeyboard = ProgramMenuKeyboard::courses($msc, 'en', 1, 1, 10, null);
        $allButtons = collect($allKeyboard['inline_keyboard'])->flatten(1)->pluck('text')->all();

        $this->assertTrue(collect($allButtons)->contains(fn ($t) => str_contains($t, 'Advanced Machine Learning Core')));
        $this->assertTrue(collect($allButtons)->contains(fn ($t) => str_contains($t, 'Deep Learning Analytics')));
        $this->assertTrue(collect($allButtons)->contains(fn ($t) => str_contains($t, 'Cloud Networking Architecture')));
    }

    public function test_admin_can_assign_tracks_to_course(): void
    {
        $this->seed(MasterTrackSeeder::class);
        $msc = Program::where('name', 'Computer Engineering')->where('degree_level', 'MSc')->first();
        $track = $msc->tracks->first();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'name' => 'Specialized Track Topic',
            'course_code' => 'TRK101',
            'credits' => 6,
            'year' => 2,
            'program_ids' => [$msc->id],
            'track_ids' => [$track->id],
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.courses.index'));

        $course = Course::where('course_code', 'TRK101')->first();
        $this->assertNotNull($course);
        $this->assertTrue($course->tracks->contains('id', $track->id));
        $this->assertTrue($course->programs->contains('id', $msc->id));
    }

    protected function invokeHandler(string $method, array $parameters): mixed
    {
        $controller = app(TelegramWebhookController::class);
        $reflection = new ReflectionMethod($controller, $method);

        return $reflection->invokeArgs($controller, $parameters);
    }
}
