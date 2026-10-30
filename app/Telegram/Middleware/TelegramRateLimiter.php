<?php

namespace App\Telegram\Middleware;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class TelegramRateLimiter
{
    public function __construct(
        protected TelegramService $telegram
    ) {}

    /**
     * Determine whether the incoming Telegram update should be throttled.
     *
     * @param array $payload The raw Telegram webhook update payload
     * @param User $user The resolved Telegram user model
     * @return bool True if throttled (stop processing), false to continue
     */
    public function handle(array $payload, User $user): bool
    {
        // Staff members (Admins and Editors) bypass rate limits
        if ($user->isAdmin() || $user->isEditor()) {
            return false;
        }

        $lang = $user->preferred_language ?? 'en';
        $message = $payload['message'] ?? null;
        $callbackQuery = $payload['callback_query'] ?? null;

        // 1. File Upload Throttling (Max 20 files per 1 hour)
        if ($message && (isset($message['document']) || isset($message['photo']))) {
            $key = "tg_limit:upload:{$user->id}";
            $maxUploads = 20;
            $decaySeconds = 3600; // 1 hour

            if (RateLimiter::tooManyAttempts($key, $maxUploads)) {
                $seconds = RateLimiter::availableIn($key);
                $minutes = max(1, (int) ceil($seconds / 60));
                $chatId = $message['chat']['id'] ?? $user->telegram_user_id;

                $this->telegram->sendMessage(
                    $chatId,
                    __('bot.rate_limit.too_many_uploads', ['minutes' => $minutes], $lang),
                    null,
                    'HTML'
                );

                bot_log('rbac')->warning("User #{$user->id} ({$user->telegram_user_id}) exceeded hourly upload limit.");
                return true;
            }

            RateLimiter::hit($key, $decaySeconds);
            return false;
        }

        // 2. Inline Button Click Throttling (Max 25 clicks per 30 seconds)
        if ($callbackQuery) {
            $key = "tg_limit:cb:{$user->id}";
            $maxClicks = 25;
            $decaySeconds = 30;

            if (RateLimiter::tooManyAttempts($key, $maxClicks)) {
                $callbackId = $callbackQuery['id'] ?? '';
                if (! empty($callbackId)) {
                    $this->telegram->answerCallbackQuery(
                        $callbackId,
                        __('bot.rate_limit.too_many_clicks', [], $lang),
                    );
                }

                bot_log('rbac')->warning("User #{$user->id} ({$user->telegram_user_id}) throttled for rapid button clicks.");
                return true;
            }

            RateLimiter::hit($key, $decaySeconds);
            return false;
        }

        // 3. Message & Command Throttling (Max 20 messages per 30 seconds)
        if ($message && isset($message['text'])) {
            $key = "tg_limit:msg:{$user->id}";
            $maxMessages = 20;
            $decaySeconds = 30;

            if (RateLimiter::tooManyAttempts($key, $maxMessages)) {
                $seconds = RateLimiter::availableIn($key);
                $chatId = $message['chat']['id'] ?? $user->telegram_user_id;

                // Send warning only once per 10 seconds to prevent the bot from spamming itself
                $warnedKey = "tg_limit:warned:{$user->id}";
                if (! Cache::has($warnedKey)) {
                    Cache::put($warnedKey, true, now()->addSeconds(10));

                    $this->telegram->sendMessage(
                        $chatId,
                        __('bot.rate_limit.too_many_messages', ['seconds' => $seconds], $lang),
                        null,
                        'HTML'
                    );
                }

                bot_log('rbac')->warning("User #{$user->id} ({$user->telegram_user_id}) throttled for rapid text messages.");
                return true;
            }

            RateLimiter::hit($key, $decaySeconds);
            return false;
        }

        return false;
    }
}
