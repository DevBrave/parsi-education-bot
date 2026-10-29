<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureTelegramTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredSecret = config('telegram.webhook_secret');

        // Allow bypassing ONLY in local development if no secret is set
        if (empty($configuredSecret) && app()->isLocal()) {
            return $next($request);
        }

        // If secret is not configured in non-local environments, reject securely
        if (empty($configuredSecret)) {
            bot_log('telegram')->error('Telegram webhook rejected: TELEGRAM_WEBHOOK_SECRET is not configured on server.');

            return response('Server configuration error', 500);
        }

        $incomingToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (! is_string($incomingToken) || ! hash_equals($configuredSecret, $incomingToken)) {
            bot_log('telegram')->warning('Telegram webhook rejected: invalid secret token', [
                'ip' => $request->ip(),
            ]);

            return response('Invalid secret token', 403);
        }

        return $next($request);
    }
}
