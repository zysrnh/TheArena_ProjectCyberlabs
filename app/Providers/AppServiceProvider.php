<?php
namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;

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

        // ✅ FIX: Input text color untuk light mode
        FilamentView::registerRenderHook(
            'panels::styles.after',
            fn (): string => Blade::render('<style>
                input[type="text"],
                input[type="number"],
                input[type="email"],
                input[type="tel"],
                input[type="date"],
                textarea,
                select {
                    color: rgb(17 24 39) !important;
                }
                
                .dark input[type="text"],
                .dark input[type="number"],
                .dark input[type="email"],
                .dark input[type="tel"],
                .dark input[type="date"],
                .dark textarea,
                .dark select {
                    color: rgb(255 255 255) !important;
                }
            </style>')
        );
    }
}