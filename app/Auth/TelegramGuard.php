<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class TelegramGuard implements Guard
{
    use GuardHelpers;  // Implements check(), guest(), id(), setUser() automatically

    /**
     * The current HTTP request (contains the Telegram webhook JSON).
     */
    protected Request $request;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * THE key method: "Who is making this request?"
     *
     * Reads telegram_user_id from the webhook JSON body,
     * then looks up the User in MySQL.
     */
    public function user(): ?User
    {

        if ($this->user !== null) {
            return $this->user;
        }

        // Telegram sends user info in different places depending on update type
        $telegramUserId = $this->request->input('message.from.id')
            ?? $this->request->input('callback_query.from.id');
        if ($telegramUserId) {
            $this->user = User::where('telegram_user_id', $telegramUserId)->first();
        }

        return $this->user;
    }

    /**
     * Required by the Guard contract.
     * "Can these credentials identify a user?"
     * Not really used for Telegram, but we must implement it.
     */
    public function validate(array $credentials = []): bool
    {
        $user = User::where('telegram_user_id', $credentials['telegram_user_id'] ?? null)->first();

        return $user !== null;
    }
}
