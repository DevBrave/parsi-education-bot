<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Drive Service Settings
    |--------------------------------------------------------------------------
    |
    | Path to the Google Service Account JSON credentials file, and the target
    | folder ID on Google Drive where resources should be stored.
    |
    */

    'client_id' => env('GOOGLE_CLIENT_ID', ''),
    'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
    'refresh_token' => env('GOOGLE_REFRESH_TOKEN', ''),

    'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON')
        ? (str_starts_with(env('GOOGLE_SERVICE_ACCOUNT_JSON'), '/') || preg_match('/^[a-zA-Z]:\\\\/', env('GOOGLE_SERVICE_ACCOUNT_JSON'))
            ? env('GOOGLE_SERVICE_ACCOUNT_JSON')
            : base_path(env('GOOGLE_SERVICE_ACCOUNT_JSON')))
        : storage_path('app/google/service-account.json'),

    'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),
];
