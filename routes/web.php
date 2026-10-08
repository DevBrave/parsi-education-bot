<?php

use App\Http\Controllers\Admin\ResourceAdminController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
   
});


Route::middleware(IsAdmin::class)->prefix('admin')->group(function (){
    Route::get('/resources/create',[ResourceAdminController::class,'create'])->name('admin.resources.create');
    Route::post('/resources/store',[ResourceAdminController::class,'store'])->name('admin.resources.store');
});

if (app()->isLocal()) {
    Route::get('/debug/telegram', function () {
        // Keep this debug page accessible only through localhost.
        abort_unless(
            in_array(request()->getHost(), ['localhost', '127.0.0.1'], true),
            404,
        );

        return view('debug.telegram', [
            'payload' => Cache::get(
                'debug.telegram.latest',
            ),
        ]);
    });

    Route::get('/google/auth', function () {
        $clientId = config('google.client_id');
        $clientSecret = config('google.client_secret');
        if (! $clientId || ! $clientSecret) {
            return response('Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env first!', 400);
        }

        $client = new \Google\Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri(url('/google/callback'));
        $client->addScope(\Google\Service\Drive::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return redirect()->away($client->createAuthUrl());
    });

    Route::get('/google/callback', function (\Illuminate\Http\Request $request) {
        $code = $request->input('code');
        if (! $code) {
            return response('No authorization code received from Google.', 400);
        }

        $client = new \Google\Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->setRedirectUri(url('/google/callback'));

        $token = $client->fetchAccessTokenWithAuthCode($code);
        $refreshToken = $token['refresh_token'] ?? null;

        if (! $refreshToken) {
            return response('No refresh token received. Revoke access at https://myaccount.google.com/permissions and retry.', 400);
        }

        // Save refresh token to .env file automatically
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            if (str_contains($env, 'GOOGLE_REFRESH_TOKEN=')) {
                $env = preg_replace('/GOOGLE_REFRESH_TOKEN=.*/', 'GOOGLE_REFRESH_TOKEN=' . $refreshToken, $env);
            } else {
                $env .= "\nGOOGLE_REFRESH_TOKEN=" . $refreshToken . "\n";
            }
            file_put_contents($envPath, $env);
        }

        return response('<h1>✅ Google Drive Connected Successfully!</h1><p>Your refresh token was automatically saved to your <code>.env</code> file. You can close this window now.</p>');
    });
}

require __DIR__ . '/settings.php';
