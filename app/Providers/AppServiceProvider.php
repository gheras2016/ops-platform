<?php

namespace App\Providers;

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
        // Behind Railway's HTTPS proxy, force all generated links (asset/url/route)
        // to https so the browser doesn't block them as mixed content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        \App\Models\Company::observe(\App\Observers\CompanyObserver::class);
    }
}
