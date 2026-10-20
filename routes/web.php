<?php

use App\Http\Controllers\Admin\AcademicYearAdminController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CourseAdminController;
use App\Http\Controllers\Admin\ProgramAdminController;
use App\Http\Controllers\Admin\ResourceAdminController;
use App\Http\Controllers\Admin\ResourceTypeAdminController;
use App\Http\Middleware\IsAdmin;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

// Admin Authentication (Public)
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

Route::middleware(IsAdmin::class)->prefix('admin')->group(function () {
    // 0. Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // 1. Resources
    Route::get('/resources', [ResourceAdminController::class, 'index'])->name('admin.resources.index');
    Route::get('/resources/create', [ResourceAdminController::class, 'create'])->name('admin.resources.create');
    Route::post('/resources', [ResourceAdminController::class, 'store'])->name('admin.resources.store');
    Route::get('/resources/{resource}/edit', [ResourceAdminController::class, 'edit'])->name('admin.resources.edit');
    Route::put('/resources/{resource}', [ResourceAdminController::class, 'update'])->name('admin.resources.update');
    Route::get('/resources/{resource}/files/{file}/download', [ResourceAdminController::class, 'downloadFile'])->name('admin.resources.download');
    Route::delete('/resources/{resource}/files/{file}', [ResourceAdminController::class, 'destroyFile'])->name('admin.resources.files.destroy');
    Route::delete('/resources/{resource}', [ResourceAdminController::class, 'destroy'])->name('admin.resources.destroy');

    // 2. Academic Years
    Route::get('/academic-years', [AcademicYearAdminController::class, 'index'])->name('admin.academic-years.index');
    Route::post('/academic-years', [AcademicYearAdminController::class, 'store'])->name('admin.academic-years.store');
    Route::get('/academic-years/{academicYear}/edit', [AcademicYearAdminController::class, 'edit'])->name('admin.academic-years.edit');
    Route::put('/academic-years/{academicYear}', [AcademicYearAdminController::class, 'update'])->name('admin.academic-years.update');
    Route::post('/academic-years/{academicYear}/current', [AcademicYearAdminController::class, 'setCurrent'])->name('admin.academic-years.current');
    Route::delete('/academic-years/{academicYear}', [AcademicYearAdminController::class, 'destroy'])->name('admin.academic-years.destroy');

    // 3. Programmes
    Route::get('/programs', [ProgramAdminController::class, 'index'])->name('admin.programs.index');
    Route::post('/programs', [ProgramAdminController::class, 'store'])->name('admin.programs.store');
    Route::get('/programs/{program}/edit', [ProgramAdminController::class, 'edit'])->name('admin.programs.edit');
    Route::put('/programs/{program}', [ProgramAdminController::class, 'update'])->name('admin.programs.update');
    Route::delete('/programs/{program}', [ProgramAdminController::class, 'destroy'])->name('admin.programs.destroy');

    // 4. Courses
    Route::get('/courses', [CourseAdminController::class, 'index'])->name('admin.courses.index');
    Route::post('/courses', [CourseAdminController::class, 'store'])->name('admin.courses.store');
    Route::get('/courses/{course}/edit', [CourseAdminController::class, 'edit'])->name('admin.courses.edit');
    Route::put('/courses/{course}', [CourseAdminController::class, 'update'])->name('admin.courses.update');
    Route::delete('/courses/{course}', [CourseAdminController::class, 'destroy'])->name('admin.courses.destroy');

    // 5. Resource Types
    Route::get('/resource-types', [ResourceTypeAdminController::class, 'index'])->name('admin.resource-types.index');
    Route::post('/resource-types', [ResourceTypeAdminController::class, 'store'])->name('admin.resource-types.store');
    Route::get('/resource-types/{resourceType}/edit', [ResourceTypeAdminController::class, 'edit'])->name('admin.resource-types.edit');
    Route::put('/resource-types/{resourceType}', [ResourceTypeAdminController::class, 'update'])->name('admin.resource-types.update');
    Route::delete('/resource-types/{resourceType}', [ResourceTypeAdminController::class, 'destroy'])->name('admin.resource-types.destroy');

    // 6. User & Role Management
    Route::get('/users', [\App\Http\Controllers\Admin\UserAdminController::class, 'index'])->name('admin.users.index');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserAdminController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserAdminController::class, 'update'])->name('admin.users.update');

    // 7. Resource Reports
    Route::get('/reports', [\App\Http\Controllers\Admin\ReportAdminController::class, 'index'])->name('admin.reports.index');
    Route::get('/reports/{report}', [\App\Http\Controllers\Admin\ReportAdminController::class, 'show'])->name('admin.reports.show');
    Route::post('/reports/{report}/resolve', [\App\Http\Controllers\Admin\ReportAdminController::class, 'resolve'])->name('admin.reports.resolve');
    Route::post('/reports/{report}/dismiss', [\App\Http\Controllers\Admin\ReportAdminController::class, 'dismiss'])->name('admin.reports.dismiss');
    Route::delete('/reports/{report}/resource', [\App\Http\Controllers\Admin\ReportAdminController::class, 'destroyResource'])->name('admin.reports.resource.destroy');
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

        $client = new Client;
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri(url('/google/callback'));
        $client->addScope(Drive::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return redirect()->away($client->createAuthUrl());
    });

    Route::get('/google/callback', function (Request $request) {
        $code = $request->input('code');
        if (! $code) {
            return response('No authorization code received from Google.', 400);
        }

        $client = new Client;
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
                $env = preg_replace('/GOOGLE_REFRESH_TOKEN=.*/', 'GOOGLE_REFRESH_TOKEN='.$refreshToken, $env);
            } else {
                $env .= "\nGOOGLE_REFRESH_TOKEN=".$refreshToken."\n";
            }
            file_put_contents($envPath, $env);
        }

        return response('<h1>✅ Google Drive Connected Successfully!</h1><p>Your refresh token was automatically saved to your <code>.env</code> file. You can close this window now.</p>');
    });
}

require __DIR__.'/settings.php';
