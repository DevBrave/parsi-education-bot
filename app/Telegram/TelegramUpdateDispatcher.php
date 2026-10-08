<?php

namespace App\Telegram;

use App\Models\User;
use App\Telegram\Handlers\CallbackQueryHandler;
use App\Telegram\Handlers\MessageHandler;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class TelegramUpdateDispatcher
{
    public function __construct(
        protected MessageHandler $messageHandler,
        protected CallbackQueryHandler $callbackQueryHandler
    ) {}

    /**
     * Dispatch an incoming Telegram webhook update to the appropriate handler.
     */
    public function dispatch(array $payload): void
    {
        
        $message = $payload['message'] ?? null;
        $callbackQuery = $payload['callback_query'] ?? null;

        $from = $message['from'] ?? $callbackQuery['from'] ?? null;

        if (! $from || ! isset($from['id'])) {
            return;
        }

        // 2. Resolve User safely (Read-first)
        $user = $this->resolveUser($from);
        


        if (! $user) {
            return;
        }
        // 3. Early exit for blocked users (Zero DB write overhead)
        if ($user->is_blocked) {
            Log::info("Blocked user attempted access: {$user->telegram_user_id }" . $user->is_blocked);

            return;
        }


        // Handle text messages and commands
        if ($message && isset($message['text'])) {
            $this->messageHandler->handle($message, $user);

            return;
        }

        // Handle inline keyboard callback queries
        if ($callbackQuery) {
            $this->callbackQueryHandler->handle($callbackQuery, $user);

            return;
        }
    }

    protected function resolveUser(array $from): ?User
    {
        $telegramId = $from['id'];
        $firstName = $from['first_name'] ?? 'Student';
        $lastName = $from['last_name'] ?? null;
        $username = $from['username'] ?? null;
        $user = User::where('telegram_user_id', $telegramId)->first();
        // Safe creation handling race conditions
        if (! $user) {
            try {
                $user = User::create(
                    [
                        'telegram_user_id' => $telegramId,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'username' => $username,
                    ]
                );
            } catch (QueryException $e) {
                // If a concurrent queue worker already inserted it, fetch it
                $user = User::where('telegram_user_id', $telegramId)->first();
            }
        }
        // Only update if profile fields actually changed
        if ($user && (
            $user->first_name !== $firstName ||
            $user->last_name !== $lastName ||
            $user->username !== $username
        )) {
            $user->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $username,
            ]);
        }

        return $user;
    }
}
