<?php

namespace App\Jobs;

use App\Models\Resource;
use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Handlers\FileUploadHandler;
use App\Telegram\Keyboards\MainMenuKeyboard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class FinalizeSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public int $resourceId,
        public int $chatId
    ) {}

    public function handle(FileUploadHandler $uploadHandler, TelegramService $telegram): void
    {
        $stateKey = "user_upload_state:{$this->userId}";
        $state = Cache::get($stateKey);

        // If the session was already completed or cancelled, do nothing
        if (! $state || ($state['resource_id'] ?? null) !== $this->resourceId) {
            return;
        }

        Cache::forget($stateKey);

        $resource = Resource::with(['course', 'resourceType', 'files'])->find($this->resourceId);
        $user = User::find($this->userId);

        if (! $resource || ! $user || $resource->files->isEmpty()) {
            return;
        }

        // Notify admins about the submission
        $uploadHandler->notifyAdminsForResource($resource, $user);

        // Send confirmation to the student
        $lang = $user->preferred_language ?? 'en';
        $courseName = $resource->course ? e($resource->course->name) : 'Course';
        $count = $resource->files->count();

        $telegram->sendMessage(
            $this->chatId,
            __('bot.submit_material.success', ['course' => $courseName, 'count' => $count], $lang),
            MainMenuKeyboard::build($lang)
        );
    }
}
