<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // HTTPS 강제 (프로덕션)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
