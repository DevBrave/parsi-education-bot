<?php

namespace App\Telegram\Handlers;

use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\TelegramService;
use App\Telegram\Keyboards\ProgramMenuKeyboard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class CallbackQueryHandler
{
    public function __construct(
        protected TelegramService $telegram,
        protected MessageHandler $messageHandler
    ) {}

    /**
     * Handle inline button presses (callback queries).
     */
    public function handle(array $callbackQuery, User $user): void
    {
        $callbackId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';

        // Acknowledge the button press to remove the loading spinner
        $this->telegram->answerCallbackQuery($callbackId);

        // Only message-backed inline menus can be edited by this bot.
        if (! isset($callbackQuery['message']['chat']['id'], $callbackQuery['message']['message_id'])) {
            return;
        }

        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];

        if ($data === 'back_main') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📚 <b>Educational Files</b>\n\nMenu closed.",
                ['inline_keyboard' => []]
            );
            $this->messageHandler->sendMainMenu($chatId, $user);
        } elseif ($data === 'edu_files') {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📚 <b>Educational Files</b>\n\nChoose your degree programme:",
                ProgramMenuKeyboard::programmes()
            );
        } elseif (str_starts_with($data, 'prog_')) {
            $this->handleSelectProgram($chatId, $messageId, (int) substr($data, 5));
        } elseif (str_starts_with($data, 'course_')) {
            $this->handleSelectCourse($chatId, $messageId, $data);
        } elseif (str_starts_with($data, 'resource_type_')) {
            $this->handleSelectResourceType($chatId, $messageId, $data);
        } elseif (str_starts_with($data, 'send_resource_')) {
            $this->handleSendResource($chatId, $data);
        } elseif (str_starts_with($data, 'submit_res_')) {
            $this->handleSubmitResourcePrompt($chatId, $messageId, (int) substr($data, 11));
        }
    }

    protected function handleSubmitResourcePrompt(int $chatId, int $messageId, int $courseId): void
    {
        $course = Course::find($courseId);
        $courseName = $course ? e($course->name) : 'this course';

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            "📤 <b>Submit Material for {$courseName}</b>\n\n"
                . "Thank you for contributing to PARSI PoliTo!\n\n"
                . "📄 Send your document (PDF, ZIP, DOCX) directly into this chat.\n"
                . "💡 Include the title or exam year in your file caption.\n\n"
                . "Our admins will review your submission and publish it with full credit to you! 🎓",
            [
                'inline_keyboard' => [
                    [['text' => '⬅️ Back to Educational Files', 'callback_data' => 'edu_files']],
                ],
            ]
        );
    }

    protected function handleSelectProgram(int $chatId, int $messageId, int $programId): void
    {
        $program = Program::with(['courses' => function ($query) {
            $query->where('is_active', true)->orderBy('name');
        }])->where('is_active', true)->find($programId);

        if ($program) {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                '🎓 <b>' . e($program->name) . "</b>\n\nChoose a course to view materials:",
                ProgramMenuKeyboard::courses($program)
            );
        }
    }

    protected function handleSelectCourse(int $chatId, int $messageId, string $data): void
    {
        $parts = explode('_', $data);
        $courseId = (int) ($parts[1] ?? 0);
        $programId = isset($parts[2]) ? (int) $parts[2] : null;

        $course = Course::with('programs')->where('is_active', true)->find($courseId);

        if (! $course) {
            return;
        }

        $parentProgram = $programId === null
            ? $course->programs->first()
            : $course->programs->firstWhere('id', $programId);

        if (! $parentProgram || ! $parentProgram->is_active) {
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📚 <b>Educational Files</b>\n\nChoose your degree programme:",
                ProgramMenuKeyboard::programmes()
            );

            return;
        }

        $programId = $parentProgram->id;

        $resourceTypes = ResourceType::where('is_active', true)
            ->whereHas('resources', function ($query) use ($course) {
                $query->where('course_id', $course->id)
                    ->where('status', 'approved');
            })
            ->orderBy('sort_order')
            ->get();

        Log::debug("Resource Types for {$course->name}", ['content' => $resourceTypes]);

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            '🎓 <b>' . e($course->name) . "</b>\n\nChoose a type to view materials:",
            ProgramMenuKeyboard::resourceTypes($resourceTypes, $course->id, $programId)
        );
    }

    protected function handleSelectResourceType(int $chatId, int $messageId, string $data): void
    {
        $parts = explode('_', $data);
        $resourceTypeId = (int) ($parts[2] ?? 0);
        $courseId = (int) ($parts[3] ?? 0);
        $programId = isset($parts[4]) ? (int) $parts[4] : null;

        $resources = Resource::with('files')
            ->where('course_id', $courseId)
            ->where('resource_type_id', $resourceTypeId)
            ->where('status', 'approved')
            ->whereHas('course', fn($query) => $query->where('is_active', true))
            ->get();

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            "<b>Available resources</b>\n\nChoose a resource to download:",
            ProgramMenuKeyboard::resources($resources, $courseId, $programId ?? 0)
        );
    }

    protected function handleSendResource(int $chatId, string $data): void
    {
        $resourceId = substr($data, strlen('send_resource_'));
        $resource = ctype_digit($resourceId) ? Resource::with(['files', 'course', 'submitter'])
            ->where('status', 'approved')
            ->whereHas('resourceType', fn($query) => $query->where('is_active', true))
            ->whereHas('course', fn($query) => $query->where('is_active', true)
                ->whereHas('programs', fn($programQuery) => $programQuery->where('is_active', true)))
            ->find((int) $resourceId) : null;

        if (! $resource) {
            $this->telegram->sendMessage($chatId, 'This resource is no longer available.');

            return;
        }

        $files = $resource->files->filter(fn($file) => filled($file->telegram_file_id));
        if ($files->isEmpty()) {
            $this->telegram->sendMessage($chatId, 'No downloadable files are available for this resource yet.');

            return;
        }

        $courseName = $resource->course?->name ? e($resource->course->name) : 'Course Material';
        $title = e($resource->title);
        $contributor = $resource->submitter?->username
            ? '@' . e($resource->submitter->username)
            : ($resource->submitter?->first_name ? e($resource->submitter->first_name) : null);

        $caption = "📚 <b>{$courseName}</b>\n"
            . "📄 <i>{$title}</i>\n"
            . "🎓 Politecnico di Torino\n\n"
            . "✅ <b>Verified by PARSI</b>";

        if ($contributor) {
            $caption .= "\n⭐ Contributed by: {$contributor}";
        }

        foreach ($files as $file) {
            try {
                $response = $this->telegram->sendDocument($chatId, $file->telegram_file_id, $caption);
                if (! $response->json('ok')) {
                    $this->telegram->sendMessage($chatId, 'A file could not be sent. Please try again later.');

                    return;
                }
            } catch (ConnectionException $exception) {
                Log::warning('Telegram document connection failed', ['file_id' => $file->id]);
                $this->telegram->sendMessage($chatId, 'A file could not be sent. Please try again later.');

                return;
            }
        }

        if ($files->count() < $resource->files->count()) {
            $this->telegram->sendMessage($chatId, 'Some files are not available through Telegram yet.');
        }
    }
}
