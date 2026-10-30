<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UploadResourceFileJob;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceFile;
use App\Models\ResourceType;
use App\Services\FileSecurityService;
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

        // 2b. Degree Programme (Major) Filter
        if ($request->filled('program_id')) {
            $programId = (int) $request->input('program_id');
            $query->whereHas('course.programs', function ($q) use ($programId) {
                $q->where('programs.id', $programId);
            });
        }

        // 2c. Academic Year Filter
        if ($request->filled('year')) {
            $year = (int) $request->input('year');
            $query->whereHas('course', function ($q) use ($year) {
                $q->where('year', $year);
            });
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

        $courseQuery = Course::where('is_active', true)->orderBy('name');
        $user = $request->user();
        if ($user && $user->isProfessor() && $user->program_id) {
            $courseQuery->whereHas('programs', function ($q) use ($user) {
                $q->where('programs.id', $user->program_id);
            });
        }
        $courses = $courseQuery->get();
        $resourceTypes = ResourceType::where('is_active', true)->orderBy('sort_order')->get();
        $academicYears = AcademicYear::all();
        $programs = Program::where('is_active', true)->orderBy('degree_level', 'asc')->orderBy('name', 'asc')->get();

        return view('admin.resources.index', [
            'resources' => $resources,
            'stats' => $stats,
            'courses' => $courses,
            'programs' => $programs,
            'resourceTypes' => $resourceTypes,
            'academicYears' => $academicYears,
        ]);
    }

    /**
     * Show the resource creation form.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        if ($user && ! $user->canCreateResources()) {
            abort(403, 'Unauthorized. Read-only viewers cannot upload or create resources.');
        }

        $courseQuery = Course::where('is_active', true)->orderBy('name');
        if ($user && $user->isProfessor() && $user->program_id) {
            $programId = $user->program_id;
            $courseQuery->whereHas('programs', fn ($q) => $q->where('programs.id', $programId));
        }

        $courses = $courseQuery->get();
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
        $user = $request->user();
        if ($user && ! $user->canCreateResources()) {
            abort(403, 'Unauthorized. Read-only viewers cannot upload or create resources.');
        }

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'resource_type_id' => ['required', 'exists:resource_types,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_date' => ['nullable', 'date'],
            'language' => ['nullable', 'string', 'in:en,it,fa'],
            'status' => ['required', 'string', 'in:approved,pending,rejected'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:pdf,zip,docx,pptx', 'max:512000'], // max 500MB per file
        ]);

        if ($user && $user->isProfessor() && $user->program_id) {
            $course = Course::with('programs')->find($validated['course_id']);
            if (! $course || ! $course->programs->contains('id', $user->program_id)) {
                abort(403, 'Unauthorized. You can only create resources for courses belonging to your assigned major.');
            }
        }

        $filesToUpload = [];
        $securityService = app(FileSecurityService::class);
        foreach ($request->file('files') as $file) {
            $security = $securityService->validateUpload(
                $file->getClientOriginalName(),
                $file->getClientMimeType(),
                $file->getRealPath()
            );

            if (! $security['valid']) {
                return back()
                    ->withInput()
                    ->withErrors(['files' => "The file '{$file->getClientOriginalName()}' was rejected by security inspection ({$security['reason']})."]);
            }

            $fileHash = hash_file('sha256', $file->getRealPath());

            // Check for duplicate file
            $existingFile = ResourceFile::where('sha256', $fileHash)->first();
            if ($existingFile) {
                return back()
                    ->withInput()
                    ->withErrors(['files' => "The file '{$file->getClientOriginalName()}' was already uploaded."]);
            }

            $filesToUpload[] = [
                'file' => $file,
                'hash' => $fileHash,
            ];
        }

        // Save Resource + ResourceFiles
        $createdFiles = DB::transaction(function () use ($validated, $request, $filesToUpload) {
            $resource = Resource::create([
                'course_id' => $validated['course_id'],
                'resource_type_id' => $validated['resource_type_id'],
                'academic_year_id' => $validated['academic_year_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'exam_date' => $validated['exam_date'] ?? null,
                'language' => $validated['language'] ?? 'en',
                'has_solutions' => $request->boolean('has_solutions'),
                'status' => $validated['status'] ?? 'approved',
                'submitted_by' => auth()->id(),
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            $records = [];
            foreach ($filesToUpload as $item) {
                $file = $item['file'];
                $storedPath = $file->store('resources');

                $resourceFile = $resource->files()->create([
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'sha256' => $item['hash'],
                    'local_path' => $storedPath,
                ]);

                $records[] = [
                    'resourceFile' => $resourceFile,
                    'storedPath' => $storedPath,
                ];
            }

            return $records;
        });

        // Dispatch background sync job for each file
        foreach ($createdFiles as $item) {
            UploadResourceFileJob::dispatch($item['resourceFile'], $item['storedPath'], "📚 {$validated['title']}");
        }

        $resourceId = $createdFiles[0]['resourceFile']->resource_id ?? null;
        log_rbac("Resource #{$resourceId} ('{$validated['title']}') created with " . count($createdFiles) . " file(s)", $request->user(), [
            'resource_id' => $resourceId,
            'course_id' => $validated['course_id'],
            'files_count' => count($createdFiles),
        ]);

        return redirect()
            ->route('admin.resources.index')
            ->with('success', count($createdFiles) . ' file(s) uploaded! Syncing with Telegram and Google Drive in the background...');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Resource $resource)
    {
        $user = request()->user();
        if ($user && ! $user->canEditResource($resource)) {
            abort(403, 'Unauthorized. You do not have permission to edit this resource.');
        }

        $resource->load(['course.programs', 'resourceType', 'academicYear', 'files.reports', 'submitter', 'reviewer']);

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
        $user = $request->user();
        if ($user && ! $user->canEditResource($resource)) {
            abort(403, 'Unauthorized. You do not have permission to edit this resource.');
        }

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
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:pdf,zip,docx,pptx', 'max:512000'], // max 500MB per file
            'file' => ['nullable', 'file', 'mimes:pdf,zip,docx,pptx', 'max:512000'],
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

        // Process any newly uploaded files
        $incomingFiles = [];
        if ($request->hasFile('files')) {
            $incomingFiles = array_merge($incomingFiles, $request->file('files'));
        }
        if ($request->hasFile('file')) {
            $incomingFiles[] = $request->file('file');
        }

        $createdFiles = [];
        if (! empty($incomingFiles)) {
            $filesToUpload = [];
            $securityService = app(FileSecurityService::class);
            foreach ($incomingFiles as $file) {
                $security = $securityService->validateUpload(
                    $file->getClientOriginalName(),
                    $file->getClientMimeType(),
                    $file->getRealPath()
                );

                if (! $security['valid']) {
                    return back()
                        ->withInput()
                        ->withErrors(['files' => "The file '{$file->getClientOriginalName()}' was rejected by security inspection ({$security['reason']})."]);
                }

                $fileHash = hash_file('sha256', $file->getRealPath());

                $existingFile = ResourceFile::where('sha256', $fileHash)->first();
                if ($existingFile) {
                    return back()
                        ->withInput()
                        ->withErrors(['files' => "The file '{$file->getClientOriginalName()}' was already uploaded."]);
                }

                $filesToUpload[] = [
                    'file' => $file,
                    'hash' => $fileHash,
                ];
            }

            $createdFiles = DB::transaction(function () use ($resource, $filesToUpload) {
                $records = [];
                foreach ($filesToUpload as $item) {
                    $file = $item['file'];
                    $storedPath = $file->store('resources');

                    $resourceFile = $resource->files()->create([
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getClientMimeType(),
                        'size_bytes' => $file->getSize(),
                        'sha256' => $item['hash'],
                        'local_path' => $storedPath,
                    ]);

                    $records[] = [
                        'resourceFile' => $resourceFile,
                        'storedPath' => $storedPath,
                    ];
                }

                return $records;
            });

            foreach ($createdFiles as $item) {
                UploadResourceFileJob::dispatch($item['resourceFile'], $item['storedPath'], "📚 {$resource->title}");
            }
        }

        // Collect IDs of any files newly uploaded by the admin in this request to avoid duplicate processing
        $newFileIds = array_map(fn ($item) => $item['resourceFile']->id, $createdFiles);

        // If status changed and reviewer is available, trigger student notification
        $newStatus = $validated['status'];
        if ($oldStatus !== $newStatus && $request->user()) {
            if ($newStatus === 'approved') {
                $moderation->approve($resource, $request->user(), excludeFileIds: $newFileIds);
            } elseif ($newStatus === 'rejected') {
                $moderation->reject($resource, $request->user());
            }
        }

        log_rbac("Resource #{$resource->id} ('{$resource->title}') updated", $request->user(), [
            'resource_id' => $resource->id,
            'status' => $validated['status'],
            'new_files_count' => count($createdFiles),
        ]);

        $extraMsg = ! empty($createdFiles)
            ? ' and ' . count($createdFiles) . ' new file(s) attached for background syncing'
            : '';

        return redirect()
            ->route('admin.resources.index')
            ->with('success', "Resource '{$resource->title}' updated successfully{$extraMsg}!");
    }

    /**
     * Delete an individual file associated with a resource.
     */
    public function destroyFile(Resource $resource, ResourceFile $file, GoogleDriveService $driveService, TelegramService $telegram)
    {
        $user = request()->user();
        if ($user && ! $user->canDeleteResource($resource)) {
            abort(403, 'Unauthorized. You do not have permission to delete this file.');
        }

        if ($file->resource_id !== $resource->id) {
            abort(404);
        }

        // Clean up from local storage
        if ($file->local_path && Storage::disk('local')->exists($file->local_path)) {
            Storage::disk('local')->delete($file->local_path);
        }

        // Clean up from Google Drive
        $driveDeleted = true;
        if ($file->drive_file_id) {
            $driveDeleted = $driveService->deleteFile($file->drive_file_id);
        }

        // Clean up from Telegram storage channel if message ID is known
        $storageChannelId = config('telegram.storage_channel_id');
        if ($file->telegram_message_id && $storageChannelId) {
            try {
                $telegram->deleteMessage($storageChannelId, $file->telegram_message_id);
            } catch (\Throwable $e) {
                bot_log('telegram')->warning("Failed to delete message #{$file->telegram_message_id} from Telegram channel: " . $e->getMessage());
            }
        }

        $filename = $file->original_filename;
        $fileId = $file->id;
        $file->delete();

        log_rbac("File #{$fileId} ('{$filename}') deleted from Resource #{$resource->id}", $user, [
            'resource_id' => $resource->id,
            'file_id' => $fileId,
            'filename' => $filename,
        ]);

        $driveNote = (! $driveDeleted && $file->drive_file_id) ? ' (Note: Google Drive file removal failed)' : '';

        // If no files remain on this resource, remove the empty resource as well
        if ($resource->files()->count() === 0) {
            $resource->delete();

            log_rbac("Resource #{$resource->id} ('{$resource->title}') deleted (no remaining files)", $user, [
                'resource_id' => $resource->id,
            ]);

            return redirect()
                ->route('admin.resources.index')
                ->with('success', "File '{$filename}' was deleted. The resource had no remaining files and was also removed.{$driveNote}");
        }

        return back()->with('success', "File '{$filename}' was deleted successfully from local storage, Google Drive, and Telegram.{$driveNote}");
    }

    /**
     * Remove the specified resource from storage along with all its attached files.
     */
    public function destroy(Resource $resource, GoogleDriveService $driveService, TelegramService $telegram)
    {
        $user = request()->user();
        if ($user && ! $user->canDeleteResource($resource)) {
            abort(403, 'Unauthorized. You do not have permission to delete this resource.');
        }

        $title = $resource->title;
        $storageChannelId = config('telegram.storage_channel_id');

        // Delete associated files from local disk, Google Drive, and Telegram
        foreach ($resource->files as $file) {
            if ($file->local_path && Storage::disk('local')->exists($file->local_path)) {
                Storage::disk('local')->delete($file->local_path);
            }
            if ($file->drive_file_id) {
                $driveService->deleteFile($file->drive_file_id);
            }
            if ($file->telegram_message_id && $storageChannelId) {
                try {
                    $telegram->deleteMessage($storageChannelId, $file->telegram_message_id);
                } catch (\Throwable $e) {
                    bot_log('telegram')->warning("Failed to delete message #{$file->telegram_message_id} from Telegram channel: " . $e->getMessage());
                }
            }
            $file->delete();
        }

        $resourceId = $resource->id;
        $resource->delete();

        log_rbac("Resource #{$resourceId} ('{$title}') and all its files deleted", $user, [
            'resource_id' => $resourceId,
        ]);

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

    /**
     * Manually sync all unsynced files of a resource to Google Drive with one click.
     */
    public function syncDrive(Resource $resource, GoogleDriveService $driveService, TelegramService $telegram)
    {
        $user = request()->user();
        if ($user && ! $user->canEditResource($resource)) {
            abort(403, 'Unauthorized. You do not have permission to sync this resource.');
        }

        @set_time_limit(0);
        @ini_set('max_execution_time', '600');

        if (! $driveService->isConfigured()) {
            return back()->with('error', 'Google Drive service is not configured on this server.');
        }

        $courseName = $resource->course?->name ?? 'General';
        $typeName = $resource->resourceType?->display_name ?? 'Notes';

        $courseFolderId = $driveService->getOrCreateFolder($courseName);
        $targetFolderId = $driveService->getOrCreateFolder($typeName, $courseFolderId);

        $syncedCount = 0;
        $failedCount = 0;

        foreach ($resource->files as $file) {
            if ($file->drive_file_id) {
                continue;
            }

            $tempPath = null;
            $sourcePath = null;

            if ($file->local_path && Storage::disk('local')->exists($file->local_path)) {
                $sourcePath = Storage::disk('local')->path($file->local_path);
            } elseif ($file->telegram_file_id) {
                $tgFile = $telegram->getFile($file->telegram_file_id);
                if ($tgFile && isset($tgFile['file_path'])) {
                    $downloadUrl = $telegram->getFileDownloadUrl($tgFile['file_path']);
                    $response = Http::timeout(60)->get($downloadUrl);
                    if ($response->successful()) {
                        $tempPath = tempnam(sys_get_temp_dir(), 'parsi_sync_');
                        file_put_contents($tempPath, $response->body());
                        $sourcePath = $tempPath;
                    }
                }
            }

            if ($sourcePath && file_exists($sourcePath)) {
                $mime = $file->mime_type ?? 'application/pdf';
                $driveId = $driveService->uploadFile($sourcePath, $file->original_filename, $mime, $targetFolderId);
                if ($driveId) {
                    $file->update(['drive_file_id' => $driveId]);
                    $syncedCount++;
                } else {
                    $failedCount++;
                }
            } else {
                $failedCount++;
            }

            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        log_rbac("Resource #{$resource->id} ('{$resource->title}') manual Drive sync: {$syncedCount} synced, {$failedCount} failed", $user, [
            'resource_id' => $resource->id,
            'synced' => $syncedCount,
            'failed' => $failedCount,
        ]);

        if ($syncedCount > 0) {
            return back()->with('success', "{$syncedCount} file(s) successfully synced to Google Drive!");
        } elseif ($failedCount > 0) {
            return back()->with('error', "Could not sync {$failedCount} file(s) to Google Drive. Check drive logs for details.");
        }

        return back()->with('info', 'All files for this resource are already synced to Google Drive.');
    }
}

