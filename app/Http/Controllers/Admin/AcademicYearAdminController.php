<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicYearAdminController extends Controller
{
    /**
     * Display a listing of academic years.
     */
    public function index()
    {
        $years = AcademicYear::withCount('resources')
            ->orderByDesc('starts_on')
            ->get();

        return view('admin.academic-years.index', [
            'years' => $years,
        ]);
    }

    /**
     * Store a newly created academic year.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:9', 'unique:academic_years,label'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $isCurrent = $request->boolean('is_current');

        if ($isCurrent) {
            AcademicYear::query()->update(['is_current' => false]);
        }

        AcademicYear::create([
            'label' => $validated['label'],
            'starts_on' => $validated['starts_on'],
            'ends_on' => $validated['ends_on'],
            'is_current' => $isCurrent,
        ]);

        return redirect()
            ->route('admin.academic-years.index')
            ->with('success', "Academic Year '{$validated['label']}' created successfully!");
    }

    /**
     * Show the form for editing the specified academic year.
     */
    public function edit(AcademicYear $academicYear)
    {
        return view('admin.academic-years.edit', [
            'academicYear' => $academicYear,
        ]);
    }

    /**
     * Update the specified academic year.
     */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:9', Rule::unique('academic_years', 'label')->ignore($academicYear->id)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $isCurrent = $request->boolean('is_current');

        if ($isCurrent) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_current' => false]);
        }

        $academicYear->update([
            'label' => $validated['label'],
            'starts_on' => $validated['starts_on'],
            'ends_on' => $validated['ends_on'],
            'is_current' => $isCurrent,
        ]);

        return redirect()
            ->route('admin.academic-years.index')
            ->with('success', "Academic Year '{$academicYear->label}' updated successfully!");
    }

    /**
     * Mark an academic year as the current one.
     */
    public function setCurrent(AcademicYear $academicYear)
    {
        AcademicYear::query()->update(['is_current' => false]);
        $academicYear->update(['is_current' => true]);

        return redirect()
            ->route('admin.academic-years.index')
            ->with('success', "Academic Year '{$academicYear->label}' set as current.");
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->resources()->exists()) {
            return back()->withErrors([
                'delete' => "Cannot delete '{$academicYear->label}' because it is linked to existing resources.",
            ]);
        }

        $label = $academicYear->label;
        $academicYear->delete();

        return redirect()
            ->route('admin.academic-years.index')
            ->with('success', "Academic Year '{$label}' deleted successfully.");
    }
}
