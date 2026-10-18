<?php

namespace App\Jobs;

use App\Models\ResourceFile;
use App\Services\ResourceUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadResourceFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * 10 minutes for large file uploads to Telegram and Google Drive.
     */
    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ResourceFile $resourceFile,
        public string $storedPath,
        public string $caption = ''
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ResourceUploadService $uploadService): void
    {
        $uploadService->sync($this->resourceFile, $this->storedPath, $this->caption);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("UploadResourceFileJob failed for ResourceFile #{$this->resourceFile->id}: {$exception->getMessage()}");

        // Clean up temporary local file
        Storage::disk('local')->delete($this->storedPath);

        // Delete file and clean up resource only if it has no other files
        $resource = $this->resourceFile->resource;
        $this->resourceFile->delete();

        if ($resource && $resource->files()->count() === 0) {
            $resource->delete();
        }
    }
}
