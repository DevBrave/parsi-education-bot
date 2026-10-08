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

class ResourceAdminController extends Controller
{
    /**
     * Show the resource creation form.
     */
    public function create(Request $request)
    {
        $courses = Course::where('is_active', true)->get();
        $resourcesTypes = ResourceType::where('is_active', true)->get();
        $academicYears = AcademicYear::all();

        return view('admin.create', [
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
        // 1. Validation
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

        // 2. Check for duplicate file BEFORE storing to disk
        $existingFile = ResourceFile::where('sha256', $fileHash)->first();
        if ($existingFile) {
            return back()
                ->withInput()
                ->withErrors(['file' => 'This exact file was already uploaded.']);
        }

        // 3. Store file locally (fast, < 1 second)
        $storedPath = $file->store('resources');

        // 4. Save Resource (pending) + ResourceFile in a fast database transaction
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
                'status' => 'pending', // Pending until background worker finishes upload!
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

        // 5. Dispatch background job to sync with Telegram and Google Drive
        UploadResourceFileJob::dispatch($resourceFile, $storedPath, "📚 {$validated['title']}");

        // 6. Instant redirect back to user!
        return redirect()
            ->route('admin.resources.create')
            ->with('success', 'Resource uploaded! Syncing with Telegram and Google Drive in the background...');
    }
}

