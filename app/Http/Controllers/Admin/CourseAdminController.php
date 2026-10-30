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
        $courseQuery = Course::with(['programs', 'tracks'])
            ->orderByRaw('year IS NULL, year ASC')
            ->orderBy('name', 'asc');

        $programQuery = Program::with('tracks')
            ->where('is_active', true)
            ->orderBy('degree_level', 'asc')
            ->orderBy('name', 'asc');
        // If user is a professor, restrict courses and programs to their assigned major
        if ($user && $user->isProfessor() && $user->program_id) {
            $programId = $user->program_id;

            $courseQuery->whereHas('programs', function ($q) use ($programId) {
                $q->where('programs.id', $programId);
            });

            $programQuery->where('id', $programId);
        }

        // 1. Filter by Program (Major)
        if ($request->filled('program_id')) {
            $filterProgramId = (int) $request->input('program_id');
            $courseQuery->whereHas('programs', function ($q) use ($filterProgramId) {
                $q->where('programs.id', $filterProgramId);
            });
        }

        // 2. Filter by Academic Year
        if ($request->filled('year')) {
            $courseQuery->where('year', (int) $request->input('year'));
        }

        // 3. Search Keyword (Name or Code)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $courseQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('course_code', 'like', "%{$search}%");
            });
        }

        // 4. Status Filter
        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $courseQuery->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $courseQuery->where('is_active', false);
            }
        }

        $courses = $courseQuery->get();
        $programs = $programQuery->get();

        return view('admin.courses.index', [
            'courses' => $courses,
            'programs' => $programs,
            'user' => $user,
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
            'track_ids' => ['nullable', 'array'],
            'track_ids.*' => ['exists:tracks,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $course = Course::create([
            'name' => $validated['name'],
            'course_code' => strtoupper(trim($validated['course_code'])),
            'credits' => $validated['credits'] ?? null,
            'year' => $validated['year'] ?? 1,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $course->programs()->sync($validated['program_ids'] ?? []);
        $course->tracks()->sync($validated['track_ids'] ?? []);
        $course->clearCourseCaches();

        log_rbac("Course #{$course->id} ('{$course->name}', code: {$course->course_code}) created", $request->user(), [
            'course_id' => $course->id,
            'course_code' => $course->course_code,
        ]);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', "Course '{$course->name}' added successfully!");
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Request $request, Course $course)
    {
        $user = $request->user();
        $programQuery = Program::with('tracks')->where('is_active', true)->orderBy('name');
        if ($user && $user->isProfessor() && $user->program_id) {
            $programQuery->where('id', $user->program_id);
        }
        $programs = $programQuery->get();

        return view('admin.courses.edit', [
            'course' => $course->load(['programs', 'tracks']),
            'programs' => $programs,
            'user' => $user,
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
            'track_ids' => ['nullable', 'array'],
            'track_ids.*' => ['exists:tracks,id'],
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
        $course->tracks()->sync($validated['track_ids'] ?? []);
        $course->clearCourseCaches();

        log_rbac("Course #{$course->id} ('{$course->name}', code: {$course->course_code}) updated", $request->user(), [
            'course_id' => $course->id,
            'course_code' => $course->course_code,
        ]);

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
        $courseId = $course->id;
        $courseCode = $course->course_code;
        $course->clearCourseCaches();
        $course->programs()->detach();
        $course->delete();

        log_rbac("Course #{$courseId} ('{$name}', code: {$courseCode}) deleted", request()->user(), [
            'course_id' => $courseId,
            'course_code' => $courseCode,
        ]);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', "Course '{$name}' deleted successfully.");
    }
}
