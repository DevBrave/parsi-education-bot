<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});


if (app()->isLocal()) {
    Route::get('/debug/telegram', function () {
        // Keep this debug page accessible only through localhost.
        abort_unless(
            in_array(request()->getHost(), ['localhost', '127.0.0.1'], true),
            404,
        );

        return view('debug.telegram', [
            'payload' => \Illuminate\Support\Facades\Cache::get(
                'debug.telegram.latest',
            ),
        ]);
    });
}


require __DIR__.'/settings.php';
