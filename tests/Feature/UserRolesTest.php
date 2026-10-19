<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $editor;
    protected User $viewer;
    protected User $profCompEng;
    protected User $student;
    protected Program $progCompEng;
    protected Program $progMechEng;
    protected Course $courseCompEng;
    protected Course $courseMechEng;
    protected ResourceType $resourceType;
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
            ['course_code' => '01DXUPW'],
            ['name' => 'Distributed Systems', 'year' => 1, 'is_active' => true]
        );
        $this->courseCompEng->programs()->syncWithoutDetaching([$this->progCompEng->id]);

        $this->courseMechEng = Course::firstOrCreate(
            ['course_code' => '02MECHW'],
            ['name' => 'Fluid Dynamics', 'year' => 1, 'is_active' => true]
        );
        $this->courseMechEng->programs()->syncWithoutDetaching([$this->progMechEng->id]);

        $this->resourceType = ResourceType::firstOrCreate(
            ['code' => 'lecture_notes'],
            ['display_name' => 'Lecture Notes', 'is_active' => true]
        );

        $this->admin = User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'superadmin@parsi.polito.it',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->editor = User::create([
            'first_name' => 'Content',
            'last_name' => 'Editor',
            'email' => 'editor@parsi.polito.it',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_EDITOR,
        ]);

        $this->viewer = User::create([
            'first_name' => 'Read',
            'last_name' => 'Only',
            'email' => 'viewer@parsi.polito.it',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_VIEWER,
        ]);

        $this->profCompEng = User::create([
            'first_name' => 'Prof',
            'last_name' => 'Turing',
            'email' => 'turing@parsi.polito.it',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_PROFESSOR,
            'program_id' => $this->progCompEng->id,
        ]);

        $this->student = User::create([
            'first_name' => 'Ali',
            'last_name' => 'Student',
            'email' => 'ali@polito.it',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_STUDENT,
        ]);

        $this->resCompEng = Resource::create([
            'course_id' => $this->courseCompEng->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'CompEng Notes',
            'status' => 'pending',
            'submitted_by' => $this->student->id,
        ]);

        $this->resMechEng = Resource::create([
            'course_id' => $this->courseMechEng->id,
            'resource_type_id' => $this->resourceType->id,
            'title' => 'MechEng Notes',
            'status' => 'pending',
            'submitted_by' => $this->student->id,
        ]);
    }

    public function test_admin_can_access_user_management(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['search' => 'superadmin']));
        $response->assertOk();
        $response->assertSee('Users & Roles', false);
        $response->assertSee('superadmin@parsi.polito.it');
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $this->actingAs($this->editor)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($this->viewer)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($this->profCompEng)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_viewer_can_see_resources_list_but_cannot_create_or_delete(): void
    {
        $response = $this->actingAs($this->viewer)->get(route('admin.resources.index'));
        $response->assertOk();
        $response->assertSee('CompEng Notes');
        $response->assertSee('View Only');
        $response->assertDontSee('➕ Upload New Resource');

        // Cannot create
        $this->actingAs($this->viewer)->get(route('admin.resources.create'))->assertForbidden();

        // Cannot delete
        $this->actingAs($this->viewer)
            ->delete(route('admin.resources.destroy', $this->resCompEng))
            ->assertForbidden();
    }

    public function test_editor_can_modify_courses_and_edit_resources(): void
    {
        $response = $this->actingAs($this->editor)->get(route('admin.courses.index'));
        $response->assertOk();

        // Can edit resource
        $responseEdit = $this->actingAs($this->editor)->get(route('admin.resources.edit', $this->resCompEng));
        $responseEdit->assertOk();

        // Cannot delete resource
        $this->actingAs($this->editor)
            ->delete(route('admin.resources.destroy', $this->resCompEng))
            ->assertForbidden();
    }

    public function test_professor_can_view_all_resources_but_only_mutate_their_major(): void
    {
        // 1. Professor can view list and sees both resources
        $response = $this->actingAs($this->profCompEng)->get(route('admin.resources.index'));
        $response->assertOk();
        $response->assertSee('CompEng Notes');
        $response->assertSee('MechEng Notes');

        // 2. Can edit their major's resource
        $responseMyMajor = $this->actingAs($this->profCompEng)->get(route('admin.resources.edit', $this->resCompEng));
        $responseMyMajor->assertOk();

        // 3. Blocked from editing other major's resource
        $this->actingAs($this->profCompEng)
            ->get(route('admin.resources.edit', $this->resMechEng))
            ->assertForbidden();

        // 4. Blocked from deleting other major's resource
        $this->actingAs($this->profCompEng)
            ->delete(route('admin.resources.destroy', $this->resMechEng))
            ->assertForbidden();
    }

    public function test_student_is_denied_access_to_admin_panel(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.dashboard'));
        $response->assertForbidden();
    }

    public function test_admin_can_update_user_role_and_major(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->student), [
            'first_name' => 'Ali',
            'last_name' => 'Professor',
            'role' => User::ROLE_MAJOR_ADMIN,
            'program_id' => $this->progCompEng->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->student->refresh();
        $this->assertEquals(User::ROLE_MAJOR_ADMIN, $this->student->role);
        $this->assertEquals($this->progCompEng->id, $this->student->program_id);
    }

    public function test_moderator_can_edit_all_majors_but_cannot_manage_users(): void
    {
        $moderator = User::create([
            'first_name' => 'Moe',
            'email' => 'mod@parsi.polito.it',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_MODERATOR,
        ]);

        // Can edit CompEng and MechEng resources
        $this->actingAs($moderator)->get(route('admin.resources.edit', $this->resCompEng))->assertOk();
        $this->actingAs($moderator)->get(route('admin.resources.edit', $this->resMechEng))->assertOk();

        // Cannot access user management
        $this->actingAs($moderator)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_major_admin_is_restricted_to_their_major(): void
    {
        $majorAdmin = User::create([
            'first_name' => 'Sina',
            'email' => 'sina@parsi.polito.it',
            'password' => Hash::make('password123'),
            'role' => User::ROLE_MAJOR_ADMIN,
            'program_id' => $this->progCompEng->id,
        ]);

        // Can edit CompEng
        $this->actingAs($majorAdmin)->get(route('admin.resources.edit', $this->resCompEng))->assertOk();

        // Cannot edit MechEng
        $this->actingAs($majorAdmin)->get(route('admin.resources.edit', $this->resMechEng))->assertForbidden();
    }
}
