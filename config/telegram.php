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

    'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org/bot'),

    'max_upload_size_mb' => (int) env('TELEGRAM_MAX_UPLOAD_SIZE_MB', 50),

    'channel_username' => env('TELEGRAM_CHANNEL_USERNAME', 'PARSI_POLITO'),

    'instagram_link' => env('INSTAGRAM_PAGE_LINK','https://www.instagram.com/parsi___polito/'),

    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'parsi_politobot'),

    'storage_channel_id' => env('TELEGRAM_STORAGE_CHANNEL_ID', ''),

    'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID', ''),

    'proxy' => env('TELEGRAM_PROXY', null),
];
