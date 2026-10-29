<?php

namespace App\Telegram;

use App\Models\Resource;
use App\Models\User;
use App\Telegram\Handlers\CallbackQueryHandler;
use App\Telegram\Handlers\FileUploadHandler;
use App\Telegram\Handlers\InlineQueryHandler;
use App\Telegram\Handlers\MessageHandler;
use App\Telegram\Middleware\CheckUserOnboarding;
use App\Telegram\Middleware\TelegramRateLimiter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramUpdateDispatcher
{
    public function __construct(
        protected MessageHandler $messageHandler,
        protected CallbackQueryHandler $callbackQueryHandler,
        protected CheckUserOnboarding $onboardingMiddleware,
        protected FileUploadHandler $fileUploadHandler,
        protected InlineQueryHandler $inlineQueryHandler,
        protected TelegramRateLimiter $rateLimiter
    ) {}

    /**
     * Dispatch an incoming Telegram webhook update to the appropriate handler.
     */
    public function dispatch(array $payload): void
    {
        $message = $payload['message'] ?? null;
        $callbackQuery = $payload['callback_query'] ?? null;
        $inlineQuery = $payload['inline_query'] ?? null;
        $chosenInlineResult = $payload['chosen_inline_result'] ?? null;

        // 1. Handle Inline Queries (@bot_username <query> typed anywhere)
        if ($inlineQuery) {
            $from = $inlineQuery['from'] ?? null;
            if ($from && isset($from['id'])) {
                $user = $this->resolveUser($from);
                if ($user && ! $user->is_blocked) {
                    $this->inlineQueryHandler->handle($inlineQuery, $user);
                }
            }

            return;
        }

        // 2. Handle Chosen Inline Result (User selected/clicked an item from the inline list)
        if ($chosenInlineResult) {
            $this->handleChosenInlineResult($chosenInlineResult);

            return;
        }

        $chatType = $message['chat']['type'] ?? ($callbackQuery['message']['chat']['type'] ?? 'private');
        $isGroup = in_array($chatType, ['group', 'supergroup'], true);
        $callbackData = $callbackQuery['data'] ?? '';
        $isAdminModerationCallback = str_starts_with($callbackData, 'admin_approve_') || str_starts_with($callbackData, 'admin_reject_');
        $isGroupAllowedCallback = str_starts_with($callbackData, 'send_resource_') || str_starts_with($callbackData, 'course_') || str_starts_with($callbackData, 'resource_type_') || str_starts_with($callbackData, 'res_page_');

        if ($chatType !== 'private' && ! $isAdminModerationCallback && ! $isGroup) {
            return;
        }

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
            bot_log('rbac')->warning("Blocked user attempted access: {$user->telegram_user_id}");

            return;
        }

        // Direct dispatch for admin moderation callback queries (bypass student onboarding)
        if ($isAdminModerationCallback && $callbackQuery) {
            $this->callbackQueryHandler->handle($callbackQuery, $user);

            return;
        }

        // 4. Handle Group / Supergroup interactions (Mentions & Callbacks)
        if ($isGroup) {
            if ($callbackQuery && $isGroupAllowedCallback) {
                $this->callbackQueryHandler->handle($callbackQuery, $user);

                return;
            }

            if ($message && isset($message['text'])) {
                $text = $message['text'];
                $botUsername = strtolower(config('telegram.bot_username', 'parsi_politobot'));
                $isMentioned = str_contains(strtolower($text), '@' . $botUsername) || str_starts_with(strtolower($text), '/search');

                if (! $isMentioned) {
                    return;
                }

                $cleanText = preg_replace('/@' . preg_quote($botUsername, '/') . '\b/i', '', $text);
                $cleanText = trim(preg_replace('/^\/search\b/i', '', trim($cleanText)));

                if ($cleanText !== '') {
                    $this->messageHandler->executeSearch($message['chat']['id'], $user, $cleanText, showNavigation: false);
                }
            }

            return;
        }

        // 5. Intercept first-time users or users with incomplete onboarding (Private chats only)
        if ($this->onboardingMiddleware->handle($payload, $user)) {
            return;
        }

        // 6. Anti-Spam Rate Limiter (Throttles rapid messages, clicks, and hourly uploads)
        if ($this->rateLimiter->handle($payload, $user)) {
            return;
        }

        // Handle text messages and commands
        if ($message && isset($message['text'])) {
            $this->messageHandler->handle($message, $user);

            return;
        }

        // Handle incoming files and photos
        if ($message && (isset($message['document']) || isset($message['photo']))) {
            $this->fileUploadHandler->handle($message, $user);

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

        $userIdCacheKey = "user_id:telegram_{$telegramId}";
        $userId = Cache::get($userIdCacheKey);

        $user = null;
        if ($userId && is_numeric($userId)) {
            $user = User::with('program')->find((int) $userId);
        }

        if (! $user) {
            $user = User::with('program')->where('telegram_user_id', $telegramId)->first();
            if ($user) {
                Cache::put($userIdCacheKey, $user->id, now()->addMinutes(30));
            }
        }

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
                Cache::put($userIdCacheKey, $user->id, now()->addMinutes(30));
            } catch (QueryException $e) {
                $user = User::where('telegram_user_id', $telegramId)->first();
                if ($user) {
                    Cache::put($userIdCacheKey, $user->id, now()->addMinutes(30));
                }
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
            Cache::put($userIdCacheKey, $user->id, now()->addMinutes(30));
        }

        return $user;
    }

    /**
     * Handle Telegram chosen_inline_result update when a student selects an inline search item.
     */
    protected function handleChosenInlineResult(array $chosenInlineResult): void
    {
        $resultId = $chosenInlineResult['result_id'] ?? '';
        if (str_starts_with($resultId, 'res_')) {
            $resourceId = (int) substr($resultId, 4);
            if ($resourceId > 0) {
                $resource = Resource::find($resourceId);
                if ($resource) {
                    $resource->increment('downloads_count');
                    $resource->increment('inline_shares_count');

                    bot_log('telegram')->info("Resource #{$resourceId} selected via inline query", [
                        'resource_id' => $resourceId,
                        'from_user' => $chosenInlineResult['from']['id'] ?? null,
                        'query' => $chosenInlineResult['query'] ?? '',
                    ]);
                }
            }
        }
    }
}
