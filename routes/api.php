<?php

use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\EnsureTelegramTokenIsValid;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and are assigned
| the "api" middleware group (CSRF verification is not applied).
|
*/

Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->middleware(EnsureTelegramTokenIsValid::class)
    ->name('telegram.webhook');
