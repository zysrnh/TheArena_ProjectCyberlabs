<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Force HTTPS untuk ngrok, cloudflare, atau production
        if (str_contains(config('app.url'), 'ngrok') 
            || str_contains(config('app.url'), 'trycloudflare.com') 
            || $this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // ✅ Atau kalau request dari proxy (ngrok, cloudflare, dll)
        if (request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}