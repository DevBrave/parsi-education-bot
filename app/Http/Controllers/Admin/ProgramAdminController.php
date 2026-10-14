<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramAdminController extends Controller
{
    /**
     * Display a listing of degree programmes.
     */
    public function index()
    {
        $programs = Program::withCount('courses')
            ->orderBy('name')
            ->get();

        return view('admin.programs.index', [
            'programs' => $programs,
        ]);
    }

    /**
     * Store a newly created degree programme.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('programs', 'name')->where('degree_level', $request->degree_level),
            ],
            'degree_level' => ['required', 'string', 'max:20'],
            'university' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.unique' => 'A programme with this name and degree level already exists.',
        ]);

        $program = Program::create([
            'name' => trim($validated['name']),
            'degree_level' => trim($validated['degree_level']),
            'university' => ! empty($validated['university']) ? trim($validated['university']) : 'Politecnico di Torino',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.programs.index')
            ->with('success', "Degree programme '{$program->name}' ({$program->degree_level}) added successfully!");
    }

    /**
     * Show the form for editing the specified degree programme.
     */
    public function edit(Program $program)
    {
        return view('admin.programs.edit', [
            'program' => $program,
        ]);
    }

    /**
     * Update the specified degree programme.
     */
    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('programs', 'name')
                    ->where('degree_level', $request->degree_level)
                    ->ignore($program->id),
            ],
            'degree_level' => ['required', 'string', 'max:20'],
            'university' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.unique' => 'A programme with this name and degree level already exists.',
        ]);

        $program->update([
            'name' => trim($validated['name']),
            'degree_level' => trim($validated['degree_level']),
            'university' => ! empty($validated['university']) ? trim($validated['university']) : 'Politecnico di Torino',
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.programs.index')
            ->with('success', "Degree programme '{$program->name}' updated successfully!");
    }

    /**
     * Remove the specified degree programme.
     */
    public function destroy(Program $program)
    {
        if ($program->courses()->exists()) {
            return back()->withErrors([
                'delete' => "Cannot delete '{$program->name}' because it is linked to {$program->courses()->count()} course(s).",
            ]);
        }

        $name = $program->name;
        $level = $program->degree_level;
        $program->delete();

        return redirect()
            ->route('admin.programs.index')
            ->with('success', "Degree programme '{$name}' ({$level}) deleted successfully.");
    }
}
