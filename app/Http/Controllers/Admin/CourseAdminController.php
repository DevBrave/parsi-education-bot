<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseAdminController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $courseQuery = Course::with('programs')->orderBy('name');
        $programQuery = Program::where('is_active', true)->orderBy('name');

        // If user is a professor, restrict courses and programs to their assigned major
        if ($user && $user->isProfessor() && $user->program_id) {
            $programId = $user->program_id;
            
            $courseQuery->whereHas('programs', function ($q) use ($programId) {
                $q->where('programs.id', $programId);
            });

            $programQuery->where('id', $programId);
        }

        $courses = $courseQuery->get();
        $programs = $programQuery->get();

        return view('admin.courses.index', [
            'courses' => $courses,
            'programs' => $programs,
        ]);
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request)
    {
        if ($request->user() && ! $request->user()->canManageCatalog()) {
            abort(403, 'Unauthorized. Only Admins and Editors can modify courses.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'course_code' => ['required', 'string', 'max:30', 'unique:courses,course_code'],
            'credits' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'year' => ['nullable', 'integer', 'min:1', 'max:6'],
            'program_ids' => ['nullable', 'array'],
            'program_ids.*' => ['exists:programs,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $course = Course::create([
            'name' => $validated['name'],
            'course_code' => strtoupper(trim($validated['course_code'])),
            'credits' => $validated['credits'] ?? null,
            'year' => $validated['year'] ?? 1,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if (! empty($validated['program_ids'])) {
            $course->programs()->sync($validated['program_ids']);
        }

        return redirect()
            ->route('admin.courses.index')
            ->with('success', "Course '{$course->name}' added successfully!");
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course)
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();

        return view('admin.courses.edit', [
            'course' => $course->load('programs'),
            'programs' => $programs,
        ]);
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, Course $course)
    {
        if ($request->user() && ! $request->user()->canManageCatalog()) {
            abort(403, 'Unauthorized. Only Admins and Editors can modify courses.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'course_code' => ['required', 'string', 'max:30', Rule::unique('courses', 'course_code')->ignore($course->id)],
            'credits' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'year' => ['nullable', 'integer', 'min:1', 'max:6'],
            'program_ids' => ['nullable', 'array'],
            'program_ids.*' => ['exists:programs,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $course->update([
            'name' => $validated['name'],
            'course_code' => strtoupper(trim($validated['course_code'])),
            'credits' => $validated['credits'] ?? null,
            'year' => $validated['year'] ?? 1,
            'is_active' => $request->boolean('is_active'),
        ]);

        $course->programs()->sync($validated['program_ids'] ?? []);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', "Course '{$course->name}' updated successfully!");
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Course $course)
    {
        if (request()->user() && ! request()->user()->canManageCatalog()) {
            abort(403, 'Unauthorized. Only Admins and Editors can delete courses.');
        }

        $name = $course->name;
        $course->programs()->detach();
        $course->delete();

        return redirect()
            ->route('admin.courses.index')
            ->with('success', "Course '{$name}' deleted successfully.");
    }
}
