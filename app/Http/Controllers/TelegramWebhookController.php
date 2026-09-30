<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramService $telegram
    ) {}

    /**
     * Handle incoming Telegram webhook updates.
     */
    public function handle(Request $request): Response
    {
        // 1. Verify Secret Token if configured
        $configuredSecret = config('telegram.webhook_secret');
        if (! empty($configuredSecret)) {
            $incomingToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
            if (! hash_equals($configuredSecret, (string) $incomingToken)) {
                Log::warning('Telegram webhook rejected: invalid secret token', [
                    'ip' => $request->ip(),
                ]);

                return response('Invalid secret token', 403);
            }
        }

        $payload = $request->all();
        Log::info('Telegram webhook received', ['payload' => $payload]);

        // 2. Identify incoming update type (message or callback query)
        $message = $payload['message'] ?? null;
        $callbackQuery = $payload['callback_query'] ?? null;


        if (app()->isLocal()) {
            \Illuminate\Support\Facades\Cache::put(
                'debug.telegram.latest',
                $payload,
                now()->addMinutes(10),
            );
        }
        $from = $message['from'] ?? $callbackQuery['from'] ?? null;

        if (! $from || ! isset($from['id'])) {
            return response()->noContent();
        }

        // 3. Resolve or update the student in database
        $user = User::updateOrCreate(
            ['telegram_user_id' => $from['id']],
            [
                'first_name' => $from['first_name'] ?? 'Student',
                'last_name' => $from['last_name'] ?? null,
                'username' => $from['username'] ?? null,
            ]
        );

        // If the user is blocked, ignore the request
        if ($user->is_blocked) {
            Log::info("Blocked user attempted access: {$user->telegram_user_id}");

            return response()->noContent();
        }

        // 4. Handle text commands (e.g. /start)
        if ($message && isset($message['text'])) {
            $this->handleMessage($message, $user);
        }

        // 5. Handle inline button presses
        if ($callbackQuery) {
            $this->handleCallbackQuery($callbackQuery, $user);
        }

        return response()->noContent();
    }

    /**
     * Handle standard incoming text messages and commands.
     */
    protected function handleMessage(array $message, User $user): void
    {
        $chatId = $message['chat']['id'];
        $text = trim($message['text'] ?? '');

        if (str_starts_with($text, '/start')) {
            $this->sendMainMenu($chatId, $user);
        } elseif ($text === '📚 Educational Files') {
            $this->sendProgrammeSelector($chatId);
        } elseif ($text === '🔍 Edisu') {
            $this->telegram->sendMessage(
                $chatId,
                "🔍 <b>Edisu</b>\n\nThis section is coming soon!",
                ['inline_keyboard' => [
                    [['text' => '⬅️ Back to Main Menu', 'callback_data' => 'back_main']],
                ]]
            );
        } elseif ($text === '❓ Important Detail') {
            $this->telegram->sendMessage(
                $chatId,
                "❓ <b>Important Detail</b>\n\nThis section is coming soon!",
                ['inline_keyboard' => [
                    [['text' => '⬅️ Back to Main Menu', 'callback_data' => 'back_main']],
                ]]
            );
        } elseif ($text === '⚠️ Contact / Report') {
            $this->telegram->sendMessage(
                $chatId,
                "⚠️ <b>Contact / Report</b>\n\nThis section is coming soon!",
                ['inline_keyboard' => [
                    [['text' => '⬅️ Back to Main Menu', 'callback_data' => 'back_main']],
                ]]
            );
        } else {
            $this->telegram->sendMessage(
                $chatId,
                "👋 Hello {$user->first_name}!\n\nPlease choose an option from the menu below, or send /start.",
                $this->getMainMenuKeyboard()
            );
        }
    }

    /**
     * Handle inline button presses (callback queries).
     */
    protected function handleCallbackQuery(array $callbackQuery, User $user): void
    {
        $callbackId = $callbackQuery['id'];
        $data = $callbackQuery['data'] ?? '';

        // Acknowledge the button press - It mean I got it(removes loading spinner)
        $this->telegram->answerCallbackQuery($callbackId);

        // Only message-backed inline menus can be edited by this bot.
        if (! isset($callbackQuery['message']['chat']['id'], $callbackQuery['message']['message_id'])) {
            return;
        }

        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];

        if ($data === 'back_main') {
            // Close this submenu, then restore the persistent reply keyboard.
            $this->telegram->editMessageText(
                $chatId,
                $messageId,
                "📚 <b>Educational Files</b>\n\nMenu closed.",
                ['inline_keyboard' => []]
            );
            $this->sendMainMenu($chatId, $user);
        } elseif ($data === 'edu_files') {
            $this->editToProgrammeSelector($chatId, $messageId);
        } elseif (str_starts_with($data, 'prog_')) {
            // Dynamically load selected programme and its courses from DB
            $programId = (int) substr($data, 5);
            $program = Program::with(['courses' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            }])->where('is_active', true)->find($programId);

            if ($program) {
                $courseButtons = [];
                foreach ($program->courses as $course) {
                    $codeSuffix = $course->course_code ? " [{$course->course_code}]" : '';
                    $courseButtons[] = [
                        ['text' => "📖 {$course->name}{$codeSuffix}", 'callback_data' => "course_{$course->id}_{$program->id}"],
                    ];
                }

                if (empty($courseButtons)) {
                    $courseButtons[] = [
                        ['text' => 'ℹ️ No courses registered yet', 'callback_data' => 'noop'],
                    ];
                }

                $courseButtons[] = [
                    ['text' => '⬅️ Back to Programmes', 'callback_data' => 'edu_files'],
                ];

                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    '🎓 <b>' . e($program->name) . "</b>\n\nChoose a course to view materials:",
                    ['inline_keyboard' => $courseButtons]
                );
            }
        } elseif (str_starts_with($data, 'course_')) {
            // Dynamically load selected course details from DB
            $parts = explode('_', $data);
            $courseId = (int) ($parts[1] ?? 0);
            $programId = isset($parts[2]) ? (int) $parts[2] : null;
            $course = Course::with('programs')->where('is_active', true)->find($courseId);

            if ($course) {
                $parentProgram = $programId === null
                    ? $course->programs->first()
                    : $course->programs->firstWhere('id', $programId);

                if (! $parentProgram || ! $parentProgram->is_active) {
                    $this->editToProgrammeSelector($chatId, $messageId);

                    return;
                }

                $parentProgramId = $parentProgram->id;
                $backCallback = $parentProgramId ? "prog_{$parentProgramId}" : 'edu_files';

                $program = Program::with(['courses' => function ($query) {
                    $query->where('is_active', true)->orderBy('name');
                }])->where('is_active', true)->find($programId);

                $resourceTypes = ResourceType::where('is_active', true)
                    ->whereHas('resources', function ($query) use ($course) {
                        $query->where('course_id', $course->id)
                            ->where('status', 'approved');
                    })
                    ->orderBy('sort_order')
                    ->get();

                Log::debug("Reource Types for {$course->name}", ['content' => $resourceTypes]);
                if ($resourceTypes) {
                $courseButtons = [];
                foreach ($resourceTypes as $type) {
                    $courseButtons[] = [
                        ['text' => "📖 {$type->display_name}", 'callback_data' => "resource_type_{$type->id}"],
                    ];
                }

                if (empty($courseButtons)) {
                    $courseButtons[] = [
                        ['text' => 'ℹ️ There is no resource for this', 'callback_data' => 'noop'],
                    ];
                }

                $courseButtons[] = [
                    ['text' => '⬅️ Back to Courses', 'callback_data' => 'edu_files'],
                ];
            }
                $this->telegram->editMessageText(
                    $chatId,
                    $messageId,
                    '🎓 <b>' . e($course->name) . "</b>\n\nChoose a type to view materials:",
                    ['inline_keyboard' => $courseButtons]
                );
            }
        }
    }

    /**
     * Send the main menu welcome message with reply keyboard buttons.
     */
    protected function sendMainMenu(int $chatId, User $user): void
    {
        $welcome = '👋 <b>Welcome to PARSI Education Bot, ' . e($user->first_name) . "!</b>\n\n"
            . "Find course resources, past exams, and lecture notes easily.\n"
            . 'What would you like to do?';

        $this->telegram->sendMessage(
            $chatId,
            $welcome,
            $this->getMainMenuKeyboard()
        );
    }

    /**
     * Send a NEW message with inline programme buttons dynamically from database.
     */
    protected function sendProgrammeSelector(int $chatId): void
    {

        $this->telegram->sendMessage(
            $chatId,
            "📚 <b>Educational Files</b>\n\nChoose your degree programme:",
            $this->getProgrammesInlineKeyboard()
        );
    }

    /**
     * EDIT an existing message to show programme buttons dynamically from database.
     */
    protected function editToProgrammeSelector(int $chatId, int $messageId): void
    {
        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            "📚 <b>Educational Files</b>\n\nChoose your degree programme:",
            $this->getProgrammesInlineKeyboard()
        );
    }

    /**
     * Dynamically build inline keyboard of active programmes from database.
     */
    protected function getProgrammesInlineKeyboard(): array
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();

        $rows = [];
        foreach ($programs as $program) {
            $levelTag = $program->degree_level ? " ({$program->degree_level})" : '';
            $rows[] = [
                ['text' => "🎓 {$program->name}{$levelTag}", 'callback_data' => "prog_{$program->id}"],
            ];
        }

        $rows[] = [
            ['text' => '⬅️ Back to Main Menu', 'callback_data' => 'back_main'],
        ];

        return ['inline_keyboard' => $rows];
    }

    /**
     * Build the main menu reply keyboard.
     */
    protected function getMainMenuKeyboard(): array
    {
        return [
            'keyboard' => [
                [['text' => '📚 Educational Files'], ['text' => '🔍 Edisu']],
                [['text' => '❓ Important Detail'], ['text' => '⚠️ Contact / Report']],
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
            'one_time_keyboard' => false,
        ];
    }
}
