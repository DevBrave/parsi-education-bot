<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramUpdate;
use App\Models\User;
use App\Telegram\Handlers\CallbackQueryHandler;
use App\Telegram\Handlers\MessageHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected MessageHandler $messageHandler,
        protected CallbackQueryHandler $callbackQueryHandler
    ) {}

    /**
     * Handle incoming Telegram webhook updates.
     */
    public function handle(Request $request): Response
    {
        $payload = $request->all();
        Log::info('Telegram webhook received', ['payload' => $payload]);

        if (app()->isLocal()) {
            Cache::put(
                'debug.telegram.latest',
                $payload,
                now()->addMinutes(10),
            );
        }

        // Dispatch the background job so Telegram gets an instant 204 response
        ProcessTelegramUpdate::dispatch($payload);

        return response()->noContent();
    }

    /**
     * Forward to MessageHandler for test reflection.
     */
    protected function handleMessage(array $message, User $user): void
    {
        $this->messageHandler->handle($message, $user);
    }

    /**
     * Forward to CallbackQueryHandler for test reflection.
     */
    protected function handleCallbackQuery(array $callbackQuery, User $user): void
    {
        $this->callbackQueryHandler->handle($callbackQuery, $user);
    }
}
