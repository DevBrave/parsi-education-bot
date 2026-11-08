<?php

namespace App\Telegram\Middleware;

use App\Models\User;
use App\Services\BotAccessSettings;
use App\Services\TelegramService;
use Carbon\CarbonImmutable;

class BotAccessMiddleware
{
    public function __construct(
        protected BotAccessSettings $settings,
        protected TelegramService $telegram,
    ) {}

    /** Return true when access is denied and dispatch must stop. */
    public function handle(array $payload, User $user): bool
    {
        if ($user->isStaff()) {
            return false;
        }

        $settings = $this->settings->get();
        if (! $this->settings->isRestricted($settings)) {
            return false;
        }

        $until = CarbonImmutable::parse($settings['restricted_until'], 'UTC')
            ->setTimezone($settings['timezone'])->format('d M Y H:i T');
        $text = __('access.restricted', ['until' => $until], $user->preferred_language ?? 'en');

        if (isset($payload['inline_query'])) {
            $this->telegram->answerInlineQuery($payload['inline_query']['id'], [], cacheTime: 0, isPersonal: true);
        } elseif (isset($payload['callback_query'])) {
            $this->telegram->answerCallbackQuery($payload['callback_query']['id'], $text);
        } elseif (isset($payload['message']['chat']['id'])) {
            $message = $payload['message'];
            $isGroup = in_array($message['chat']['type'] ?? 'private', ['group', 'supergroup'], true);
            $body = mb_strtolower($message['text'] ?? '');
            $mention = '@'.mb_strtolower(ltrim(config('telegram.bot_username', 'parsi_politobot'), '@'));
            // Ignore unrelated group chatter while still stopping dispatch.
            if (! $isGroup || str_contains($body, $mention) || str_starts_with($body, '/search')) {
                $this->telegram->sendMessage(
                    $message['chat']['id'],
                    $text,
                    $isGroup ? null : ['remove_keyboard' => true],
                );
            }
        }

        return true;
    }
}
