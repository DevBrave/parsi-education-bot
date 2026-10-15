<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Credentials & Settings
    |--------------------------------------------------------------------------
    |
    | Values are loaded from your local .env file.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),

    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),

    'api_url' => 'https://api.telegram.org/bot',

    'storage_channel_id' => env('TELEGRAM_STORAGE_CHANNEL_ID', ''),

    'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID', ''),
];
