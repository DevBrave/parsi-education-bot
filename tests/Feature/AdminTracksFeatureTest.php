<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Program;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTracksFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_courses_index_page_renders_degree_level_and_tracks_sections(): void
    {
        $msc = Program::create(['name' => 'Computer Engineering', 'degree_level' => 'MSc']);
        $track = Track::create(['program_id' => $msc->id, 'name' => 'Artificial Intelligence', 'code' => 'AIDA', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->get(route('admin.courses.index'));

        $response->assertOk();
        $response->assertSee('Degree Level / مقطع تحصیلی');
        $response->assertSee('Specialization Tracks / گرایش‌ها');
        $response->assertSee('AIDA');
    }

    public function test_courses_store_saves_selected_tracks_to_database(): void
    {
        $msc = Program::create(['name' => 'Computer Engineering', 'degree_level' => 'MSc']);
        $trackA = Track::create(['program_id' => $msc->id, 'name' => 'Track A', 'code' => 'TRA', 'is_active' => true]);
        $trackB = Track::create(['program_id' => $msc->id, 'name' => 'Track B', 'code' => 'TRB', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('admin.courses.store'), [
            'name' => 'Deep Learning',
            'course_code' => 'DL101',
            'credits' => 6,
            'year' => 1,
            'program_ids' => [$msc->id],
            'track_ids' => [$trackA->id, $trackB->id],
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.courses.index'));

        $course = Course::where('course_code', 'DL101')->first();
        $this->assertNotNull($course);
        $this->assertCount(2, $course->tracks);
        $this->assertTrue($course->tracks->contains('id', $trackA->id));
        $this->assertTrue($course->tracks->contains('id', $trackB->id));
    }

    public function test_courses_update_syncs_tracks_in_database(): void
    {
        $msc = Program::create(['name' => 'Computer Engineering', 'degree_level' => 'MSc']);
        $trackA = Track::create(['program_id' => $msc->id, 'name' => 'Track A', 'code' => 'TRA', 'is_active' => true]);
        $trackB = Track::create(['program_id' => $msc->id, 'name' => 'Track B', 'code' => 'TRB', 'is_active' => true]);

        $course = Course::create([
            'name' => 'Robotics',
            'course_code' => 'ROB101',
            'year' => 2,
            'is_active' => true,
        ]);
        $course->programs()->sync([$msc->id]);
        $course->tracks()->sync([$trackA->id]);

        $this->assertCount(1, $course->tracks);

        // Update to only have trackB
        $response = $this->actingAs($this->admin)->put(route('admin.courses.update', $course), [
            'name' => 'Advanced Robotics',
            'course_code' => 'ROB101',
            'credits' => 8,
            'year' => 2,
            'program_ids' => [$msc->id],
            'track_ids' => [$trackB->id],
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.courses.index'));

        $course->refresh();
        $this->assertEquals('Advanced Robotics', $course->name);
        $this->assertCount(1, $course->tracks);
        $this->assertTrue($course->tracks->contains('id', $trackB->id));
        $this->assertFalse($course->tracks->contains('id', $trackA->id));
    }

    public function test_programs_index_page_renders_degree_level_radios_and_tracks_container(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.programs.index'));

        $response->assertOk();
        $response->assertSee('Degree Level / مقطع تحصیلی');
        $response->assertSee('Specialization Tracks / گرایش‌ها');
        $response->assertSee('btnAddTrack');
    }

    public function test_programs_store_saves_master_tracks_to_database(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.programs.store'), [
            'name' => 'Data Science and Engineering',
            'degree_level' => 'MSc',
            'university' => 'Politecnico di Torino',
            'is_active' => 1,
            'tracks' => [
                ['name' => 'Data Analytics', 'code' => 'DA'],
                ['name' => 'Big Data Technologies', 'code' => 'BDT'],
            ],
        ]);

        $response->assertRedirect(route('admin.programs.index'));

        $program = Program::where('name', 'Data Science and Engineering')->first();
        $this->assertNotNull($program);
        $this->assertEquals('MSc', $program->degree_level);
        $this->assertCount(2, $program->tracks);

        $this->assertDatabaseHas('tracks', [
            'program_id' => $program->id,
            'name' => 'Data Analytics',
            'code' => 'DA',
        ]);
        $this->assertDatabaseHas('tracks', [
            'program_id' => $program->id,
            'name' => 'Big Data Technologies',
            'code' => 'BDT',
        ]);
    }

    public function test_programs_update_syncs_tracks_correctly(): void
    {
        $program = Program::create([
            'name' => 'Electronic Engineering',
            'degree_level' => 'MSc',
            'university' => 'Politecnico di Torino',
            'is_active' => true,
        ]);
        $oldTrack = Track::create([
            'program_id' => $program->id,
            'name' => 'Old Track',
            'code' => 'OLD',
            'is_active' => true,
        ]);

        // Update: rename old track and add new track
        $response = $this->actingAs($this->admin)->put(route('admin.programs.update', $program), [
            'name' => 'Electronic Engineering',
            'degree_level' => 'MSc',
            'university' => 'Politecnico di Torino',
            'is_active' => 1,
            'tracks' => [
                ['id' => $oldTrack->id, 'name' => 'Micro-electronics', 'code' => 'MICRO'],
                ['name' => 'Embedded Systems', 'code' => 'EMB'],
            ],
        ]);

        $response->assertRedirect(route('admin.programs.index'));

        $program->refresh();
        $this->assertCount(2, $program->tracks);
        $this->assertDatabaseHas('tracks', [
            'id' => $oldTrack->id,
            'name' => 'Micro-electronics',
            'code' => 'MICRO',
        ]);
        $this->assertDatabaseHas('tracks', [
            'program_id' => $program->id,
            'name' => 'Embedded Systems',
            'code' => 'EMB',
        ]);
    }

    public function test_programs_update_to_bachelor_clears_tracks(): void
    {
        $program = Program::create([
            'name' => 'Mechanical Engineering',
            'degree_level' => 'MSc',
            'university' => 'Politecnico di Torino',
            'is_active' => true,
        ]);
        Track::create([
            'program_id' => $program->id,
            'name' => 'Mechatronics',
            'code' => 'MECH',
            'is_active' => true,
        ]);

        $this->assertCount(1, $program->tracks);

        // Change level to BSc
        $response = $this->actingAs($this->admin)->put(route('admin.programs.update', $program), [
            'name' => 'Mechanical Engineering',
            'degree_level' => 'BSc',
            'university' => 'Politecnico di Torino',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.programs.index'));

        $program->refresh();
        $this->assertEquals('BSc', $program->degree_level);
        $this->assertCount(0, $program->tracks);
    }
}

