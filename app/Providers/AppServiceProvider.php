<?php

namespace App\Providers;

use App\Auth\TelegramGuard;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');

        $this->app->singleton(\App\Services\TelegramService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Use custom styling for pagination
        Paginator::defaultView('admin.partials.pagination');
        Paginator::defaultSimpleView('admin.partials.pagination');

        // Register the custom 'telegram' guard driver.
        Auth::extend('telegram', function ($app, $name, array $config) {
            return new TelegramGuard(
                Auth::createUserProvider($config['provider']),
                $app['request'],
            );
        });




        // // In local dev, auto-login as the first admin user for convenience.
        // if (app()->isLocal()) {
        //     $admin = User::where('role', 'admin')->first();
        //     if ($admin) {
        //         Auth::login($admin);
        //     }
        // }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn(): ?Password => app()->isProduction()
                ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
                : null,
        );
    }
}
