<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One instance, so the defaults it captures at boot are still known
        // when a settings form is saved later in the request.
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        // Values saved on the settings screen override config/ and .env.
        // Applied first so everything below already sees them.
        $this->app->make(SettingsService::class)->apply();

        // Use our branded pagination markup for length-aware paginators.
        Paginator::defaultView('pagination.hrpro');

        // Localise dates for the Thai-language UI.
        Carbon::setLocale(config('hrpro.default_locale', 'th'));

        // Force HTTPS if APP_URL starts with https
        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
