<?php

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

if (! function_exists('bot_log')) {
    /**
     * Get a logger instance for a dedicated channel.
     * Defaults to the 'master' channel.
     *
     * Example:
     *   bot_log()->info('User started bot', ['user_id' => $user->id]);
     *   bot_log('drive')->error('Upload failed', ['file' => $filename]);
     *   bot_log('rbac')->warning('Unauthorized action', ['action' => $action]);
    /**
     * @return \Psr\Log\LoggerInterface|\Illuminate\Log\LogManager
     */
    function bot_log(string $channel = 'master')
    {
        return Log::channel($channel);
    }
}

if (! function_exists('log_rbac')) {
    /**
     * Log an RBAC / administrative audit action with actor details and context.
     *
     * Example:
     *   log_rbac("Course #{$course->id} ('{$course->name}') created");
     *   log_rbac("File #{$file->id} ('{$file->original_filename}') deleted from Resource #{$resource->id}");
     */
    function log_rbac(string $message, ?\App\Models\User $user = null, array $context = []): void
    {
        $user = $user ?? (auth()->check() ? auth()->user() : (request()?->user() ?? null));
        $actor = $user
            ? "user #{$user->id} (" . ($user->username ? '@'.$user->username : ($user->first_name ?: 'ID:'.$user->id)) . ")"
            : 'system';

        bot_log('rbac')->info("{$message} by {$actor}", array_merge([
            'actor_id' => $user?->id,
            'actor_role' => $user?->role,
            'ip' => request()?->ip(),
        ], $context));
    }
}
