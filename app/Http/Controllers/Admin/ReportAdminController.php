<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResourceReport;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ReportAdminController extends Controller
{
    /**
     * Display a listing of reported materials.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user && ! $user->canViewReports()) {
            abort(403, 'Unauthorized.');
        }

        $query = ResourceReport::with([
            'resource.course.programs',
            'resource.files',
            'resource.submitter',
            'user',
            'resolver',
        ])
        ->forUser($user)
        ->latest();

        // 1. Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // 2. Reason Filter
        if ($request->filled('reason')) {
            $query->where('reason', $request->input('reason'));
        }

        // 3. Search Filter
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $cleanTg = ltrim($search, '@');

            $query->where(function ($q) use ($search, $cleanTg) {
                $q->where('details', 'like', "%{$search}%")
                    ->orWhere('admin_notes', 'like', "%{$search}%")
                    ->orWhereHas('resource', function ($resQ) use ($search) {
                        $resQ->where('title', 'like', "%{$search}%")
                            ->orWhereHas('course', fn ($cQ) => $cQ->where('name', 'like', "%{$search}%"));
                    })
                    ->orWhereHas('user', function ($uQ) use ($search, $cleanTg) {
                        $uQ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$cleanTg}%")
                            ->orWhere('telegram_user_id', 'like', "%{$search}%");
                    });
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        // Statistics (scoped to user's jurisdiction if professor)
        $baseStatQuery = ResourceReport::forUser($user);
        $stats = [
            'total' => (clone $baseStatQuery)->count(),
            'pending' => (clone $baseStatQuery)->pending()->count(),
            'resolved' => (clone $baseStatQuery)->resolved()->count(),
            'dismissed' => (clone $baseStatQuery)->dismissed()->count(),
        ];

        return view('admin.reports.index', [
            'reports' => $reports,
            'stats' => $stats,
        ]);
    }

    /**
     * Display a specific report.
     */
    public function show(Request $request, ResourceReport $report): View
    {
        $user = $request->user();
        if ($user && ! $user->canViewReports()) {
            abort(403, 'Unauthorized.');
        }

        if ($user && $user->isProfessor() && ! $user->canManageReport($report)) {
            abort(403, 'Unauthorized. This report belongs to another major.');
        }

        $report->load([
            'resource.course.programs',
            'resource.files',
            'resource.submitter',
            'user',
            'resolver',
        ]);

        return view('admin.reports.show', [
            'report' => $report,
        ]);
    }

    /**
     * Mark report as resolved.
     */
    public function resolve(Request $request, ResourceReport $report): RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $user->canManageReport($report)) {
            abort(403, 'Unauthorized. You do not have permission to manage this report.');
        }

        $report->update([
            'status' => ResourceReport::STATUS_RESOLVED,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'admin_notes' => $request->input('admin_notes', $report->admin_notes),
        ]);

        return redirect()
            ->route('admin.reports.index')
            ->with('success', "Report #{$report->id} marked as Resolved!");
    }

    /**
     * Mark report as dismissed.
     */
    public function dismiss(Request $request, ResourceReport $report): RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $user->canManageReport($report)) {
            abort(403, 'Unauthorized. You do not have permission to manage this report.');
        }

        $report->update([
            'status' => ResourceReport::STATUS_DISMISSED,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'admin_notes' => $request->input('admin_notes', $report->admin_notes),
        ]);

        return redirect()
            ->route('admin.reports.index')
            ->with('success', "Report #{$report->id} was dismissed.");
    }

    /**
     * Delete the reported faulty resource directly from the report review.
     */
    public function destroyResource(
        Request $request,
        ResourceReport $report,
        GoogleDriveService $driveService,
        TelegramService $telegram
    ): RedirectResponse {
        $user = $request->user();
        $resource = $report->resource;

        if (! $resource) {
            return back()->with('error', 'The reported resource no longer exists.');
        }

        if ($user && ! $user->canDeleteResource($resource)) {
            abort(403, 'Unauthorized. You do not have permission to delete this resource.');
        }

        $title = $resource->title;
        $storageChannelId = config('telegram.storage_channel_id');

        // Helper closure to delete a single ResourceFile from disk, Drive, Telegram, and DB
        $deleteFileRecord = function ($file) use ($driveService, $telegram, $storageChannelId) {
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
                    Log::warning("Failed to delete message #{$file->telegram_message_id} from Telegram channel: " . $e->getMessage());
                }
            }
            $file->delete();
        };

        // If a specific file was reported, delete only that file
        if ($report->resource_file_id) {
            $reportedFile = $resource->files->firstWhere('id', $report->resource_file_id);

            if ($reportedFile) {
                $fileName = $reportedFile->original_filename;
                $deleteFileRecord($reportedFile);

                // Check remaining files count on the resource
                $remainingFilesCount = $resource->files()->count();
                if ($remainingFilesCount === 0) {
                    $resource->delete();
                    $actionSummary = "Reported file '{$fileName}' was deleted. As no other files remained, resource '{$title}' was also deleted.";
                } else {
                    $actionSummary = "Reported file '{$fileName}' was deleted from resource '{$title}'.";
                }

                // Mark report as resolved
                $report->update([
                    'status' => ResourceReport::STATUS_RESOLVED,
                    'resolved_by' => $user->id,
                    'resolved_at' => now(),
                    'admin_notes' => ($report->admin_notes ? $report->admin_notes . " | " : "") . "Faulty file '{$fileName}' deleted by {$user->first_name}",
                ]);

                return redirect()
                    ->route('admin.reports.index')
                    ->with('success', "{$actionSummary} Report #{$report->id} marked as Resolved!");
            }
        }

        // Fallback: Delete all associated files and the whole resource
        foreach ($resource->files as $file) {
            $deleteFileRecord($file);
        }

        $resource->delete();

        // Mark report as resolved
        $report->update([
            'status' => ResourceReport::STATUS_RESOLVED,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'admin_notes' => ($report->admin_notes ? $report->admin_notes . " | " : "") . "Faulty resource deleted by {$user->first_name}",
        ]);

        return redirect()
            ->route('admin.reports.index')
            ->with('success', "Reported resource '{$title}' was deleted and report #{$report->id} was marked as Resolved!");
    }
}
