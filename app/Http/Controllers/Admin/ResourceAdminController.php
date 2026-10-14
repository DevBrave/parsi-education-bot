<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UploadResourceFileJob;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResourceAdminController extends Controller
{
    /**
     * Display a listing of all educational resources with platform sync statuses.
     */
    public function index(Request $request)
    {
        $query = Resource::with(['course.programs', 'resourceType', 'academicYear', 'files', 'submitter'])
            ->latest();
        dd($query);
        // 1. Search Filter
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('course', function ($courseQ) use ($search) {
                        $courseQ->where('name', 'like', "%{$search}%")
                            ->orWhere('course_code', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Course Filter
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->input('course_id'));
        }

        // 3. Resource Type Filter
        if ($request->filled('resource_type_id')) {
            $query->where('resource_type_id', $request->input('resource_type_id'));
        }

        // 4. Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 5. Platform Sync Filter (Optional)
        if ($request->input('platform') === 'telegram_synced') {
            $query->whereHas('files', fn ($q) => $q->whereNotNull('telegram_file_id'));
        } elseif ($request->input('platform') === 'telegram_pending') {
            $query->whereHas('files', fn ($q) => $q->whereNull('telegram_file_id'));
        } elseif ($request->input('platform') === 'drive_synced') {
            $query->whereHas('files', fn ($q) => $q->whereNotNull('drive_file_id'));
        }

        $resources = $query->paginate(15)->withQueryString();

        // Platform Sync Stats
        $stats = [
            'total' => Resource::count(),
            'approved' => Resource::where('status', 'approved')->count(),
            'telegram_synced' => ResourceFile::whereNotNull('telegram_file_id')->count(),
            'drive_synced' => ResourceFile::whereNotNull('drive_file_id')->count(),
            'pending' => Resource::where('status', 'pending')->count(),
        ];

        $courses = Course::where('is_active', true)->orderBy('name')->get();
        $resourceTypes = ResourceType::where('is_active', true)->orderBy('sort_order')->get();
        $academicYears = AcademicYear::all();

        return view('admin.resources.index', [
            'resources' => $resources,
            'stats' => $stats,
            'courses' => $courses,
            'resourceTypes' => $resourceTypes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Show the resource creation form.
     */
    public function create(Request $request)
    {
        $courses = Course::where('is_active', true)->orderBy('name')->get();
        $resourcesTypes = ResourceType::where('is_active', true)->orderBy('sort_order')->get();
        $academicYears = AcademicYear::all();

        return view('admin.resources.create', [
            'courses' => $courses,
            'resourceTypes' => $resourcesTypes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Store a newly created resource and dispatch background sync to Telegram and Google Drive.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'resource_type_id' => ['required', 'exists:resource_types,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_date' => ['nullable', 'date'],
            'language' => ['nullable', 'string', 'in:en,it,fa'],
            'file' => ['required', 'file', 'mimes:pdf,zip,docx,pptx', 'max:51200'], // max 50MB
        ]);

        $file = $request->file('file');
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Check for duplicate file
        $existingFile = ResourceFile::where('sha256', $fileHash)->first();
        if ($existingFile) {
            return back()
                ->withInput()
                ->withErrors(['file' => 'This exact file was already uploaded.']);
        }

        // Store file locally
        $storedPath = $file->store('resources');

        // Save Resource + ResourceFile
        $resourceFile = DB::transaction(function () use ($validated, $request, $file, $fileHash) {
            $resource = Resource::create([
                'course_id' => $validated['course_id'],
                'resource_type_id' => $validated['resource_type_id'],
                'academic_year_id' => $validated['academic_year_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'exam_date' => $validated['exam_date'] ?? null,
                'language' => $validated['language'] ?? 'en',
                'has_solutions' => $request->boolean('has_solutions'),
                'status' => 'pending',
                'submitted_by' => auth()->id(),
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return $resource->files()->create([
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'sha256' => $fileHash,
            ]);
        });

        // Dispatch background sync job
        UploadResourceFileJob::dispatch($resourceFile, $storedPath, "📚 {$validated['title']}");

        return redirect()
            ->route('admin.resources.index')
            ->with('success', 'Resource uploaded! Syncing with Telegram and Google Drive in the background...');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resource $resource)
    {
        $resource->load(['course', 'resourceType', 'academicYear', 'files']);

        $courses = Course::where('is_active', true)->orderBy('name')->get();
        $resourceTypes = ResourceType::where('is_active', true)->orderBy('sort_order')->get();
        $academicYears = AcademicYear::all();

        return view('admin.resources.edit', [
            'resource' => $resource,
            'courses' => $courses,
            'resourceTypes' => $resourceTypes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Resource $resource)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'resource_type_id' => ['required', 'exists:resource_types,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_date' => ['nullable', 'date'],
            'language' => ['nullable', 'string', 'in:en,it,fa'],
            'status' => ['required', 'string', 'in:approved,pending,rejected'],
        ]);

        $resource->update([
            'course_id' => $validated['course_id'],
            'resource_type_id' => $validated['resource_type_id'],
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'exam_date' => $validated['exam_date'] ?? null,
            'language' => $validated['language'] ?? 'en',
            'has_solutions' => $request->boolean('has_solutions'),
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.resources.index')
            ->with('success', "Resource '{$resource->title}' updated successfully!");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        $title = $resource->title;

        // Delete associated files
        foreach ($resource->files as $file) {
            $file->delete();
        }

        $resource->delete();

        return redirect()
            ->route('admin.resources.index')
            ->with('success', "Resource '{$title}' deleted successfully!");
    }
}
