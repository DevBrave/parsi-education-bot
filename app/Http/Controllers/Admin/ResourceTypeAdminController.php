<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResourceType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResourceTypeAdminController extends Controller
{
    /**
     * Display a listing of resource types.
     */
    public function index()
    {
        $types = ResourceType::withCount('resources')
            ->orderBy('sort_order')
            ->get();

        return view('admin.resource-types.index', [
            'types' => $types,
        ]);
    }

    /**
     * Store a newly created resource type.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:resource_types,code'],
            'display_name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $type = ResourceType::create([
            'code' => strtolower(trim($validated['code'])),
            'display_name' => $validated['display_name'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.resource-types.index')
            ->with('success', "Resource Type '{$type->display_name}' created successfully!");
    }

    /**
     * Show the form for editing the specified resource type.
     */
    public function edit(ResourceType $resourceType)
    {
        return view('admin.resource-types.edit', [
            'resourceType' => $resourceType,
        ]);
    }

    /**
     * Update the specified resource type.
     */
    public function update(Request $request, ResourceType $resourceType)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('resource_types', 'code')->ignore($resourceType->id)],
            'display_name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $resourceType->update([
            'code' => strtolower(trim($validated['code'])),
            'display_name' => $validated['display_name'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.resource-types.index')
            ->with('success', "Resource Type '{$resourceType->display_name}' updated successfully!");
    }

    /**
     * Remove the specified resource type.
     */
    public function destroy(ResourceType $resourceType)
    {
        if ($resourceType->resources()->exists()) {
            return back()->withErrors([
                'delete' => "Cannot delete '{$resourceType->display_name}' because it has existing resources attached to it.",
            ]);
        }

        $name = $resourceType->display_name;
        $resourceType->delete();

        return redirect()
            ->route('admin.resource-types.index')
            ->with('success', "Resource Type '{$name}' deleted successfully.");
    }
}
