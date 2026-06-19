<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Use our branded pagination markup for length-aware paginators.
        Paginator::defaultView('pagination.hrpro');

        // Localise dates for the Thai-language UI.
        Carbon::setLocale(config('hrpro.default_locale', 'th'));
    }
}
