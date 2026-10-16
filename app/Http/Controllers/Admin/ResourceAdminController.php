<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UploadResourceFileJob;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Services\GoogleDriveService;
use App\Services\ResourceModerationService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        // 5. Source Filter (Community vs Official PARSI)
        if ($request->input('source') === 'community') {
            $query->whereHas('submitter', fn ($q) => $q->where('role', '!=', 'admin'));
        } elseif ($request->input('source') === 'official') {
            $query->where(function ($q) {
                $q->whereNull('submitted_by')
                    ->orWhereHas('submitter', fn ($sub) => $sub->where('role', 'admin'));
            });
        }

        // 6. Platform Sync Filter (Optional)
        if ($request->input('platform') === 'telegram_synced') {
            $query->whereHas('files', fn ($q) => $q->whereNotNull('telegram_file_id'));
        } elseif ($request->input('platform') === 'telegram_pending') {
            $query->whereHas('files', fn ($q) => $q->whereNull('telegram_file_id'));
        } elseif ($request->input('platform') === 'drive_synced') {
            $query->whereHas('files', fn ($q) => $q->whereNotNull('drive_file_id'));
        }

        $resources = $query->paginate(15)->withQueryString();
        
        // Platform Sync & Source Stats
        $stats = [
            'total' => Resource::count(),
            'approved' => Resource::where('status', 'approved')->count(),
            'community' => Resource::whereHas('submitter', fn ($q) => $q->where('role', '!=', 'admin'))->count(),
            'official' => Resource::where(function ($q) {
                $q->whereNull('submitted_by')
                    ->orWhereHas('submitter', fn ($sub) => $sub->where('role', 'admin'));
            })->count(),
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
                'local_path' => $storedPath,
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
        
        $resource->load(['course', 'resourceType', 'academicYear', 'files', 'submitter', 'reviewer']);

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
    public function update(Request $request, Resource $resource, ResourceModerationService $moderation)
    {
        $oldStatus = $resource->status;

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'resource_type_id' => ['required', 'exists:resource_types,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_date' => ['nullable', 'date'],
            'language' => ['nullable', 'string', 'in:en,it,fa'],
            'status' => ['required', 'string', 'in:approved,pending,rejected'],
            'file' => ['nullable', 'file', 'mimes:pdf,zip,docx,pptx', 'max:51200'],
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

        // If admin uploaded a file to replace/sync with Google Drive & Local Storage
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $storedPath = $file->store('resources');
            $fullPath = Storage::disk('local')->path($storedPath);
            $driveService = app(GoogleDriveService::class);
            $driveFileId = null;

            if ($driveService->isConfigured()) {
                $course = Course::find($validated['course_id']);
                $type = ResourceType::find($validated['resource_type_id']);
                $courseFolderId = $course ? $driveService->getOrCreateFolder($course->name) : null;
                $typeFolderId = $type ? $driveService->getOrCreateFolder($type->display_name, $courseFolderId) : $courseFolderId;
                $driveFileId = $driveService->uploadFile(
                    filePath: $fullPath,
                    filename: $file->getClientOriginalName(),
                    mimeType: $file->getClientMimeType(),
                    folderId: $typeFolderId,
                );
            }

            $primaryFile = $resource->files->first();
            if ($primaryFile) {
                $primaryFile->update([
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'local_path' => $storedPath,
                    'drive_file_id' => $driveFileId ?: $primaryFile->drive_file_id,
                ]);
            } else {
                $resource->files()->create([
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'local_path' => $storedPath,
                    'drive_file_id' => $driveFileId,
                ]);
            }
        }

        // If status changed and reviewer is available, trigger student notification
        $newStatus = $validated['status'];
        if ($oldStatus !== $newStatus && $request->user()) {
            if ($newStatus === 'approved') {
                $moderation->approve($resource, $request->user());
            } elseif ($newStatus === 'rejected') {
                $moderation->reject($resource, $request->user());
            }
        }

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

    /**
     * Download or preview an attached resource document.
     */
    public function downloadFile(Resource $resource, ResourceFile $file, TelegramService $telegram)
    {
        // 1. If stored on local disk (fastest, full bandwidth)
        if ($file->local_path && Storage::disk('local')->exists($file->local_path)) {
            return Storage::disk('local')->download($file->local_path, $file->original_filename);
        }

        // 2. If backed up on Google Drive (redirect directly to high-speed CDN download)
        if ($file->drive_file_id) {
            return redirect()->away("https://drive.google.com/uc?id={$file->drive_file_id}&export=download");
        }

        // 3. If stored on Telegram, attempt Bot API download (works for files up to 20MB)
        if ($file->telegram_file_id) {
            $telegramFile = $telegram->getFile($file->telegram_file_id);
            if ($telegramFile && isset($telegramFile['file_path'])) {
                $downloadUrl = $telegram->getFileDownloadUrl($telegramFile['file_path']);
                $response = Http::timeout(60)->get($downloadUrl);

                if ($response->successful()) {
                    $mime = $file->mime_type ?? 'application/octet-stream';

                    return response($response->body(), 200, [
                        'Content-Type' => $mime,
                        'Content-Disposition' => 'attachment; filename="' . addslashes($file->original_filename) . '"',
                    ]);
                }
            }

            // 4. File is > 20MB on Telegram (Telegram Bot API blocks HTTP download):
            return back()->with('info', "This file ('{$file->original_filename}', {$file->formatted_size}) exceeds Telegram's 20MB Bot API download limit. Please check and download it directly in the PARSI BOT ADMINS Telegram group.");
        }

        return back()->with('error', 'Document file is currently not accessible for preview or download.');
    }
}

