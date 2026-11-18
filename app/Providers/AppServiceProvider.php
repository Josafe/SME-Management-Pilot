<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentTimezone;
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
        // Timestamps are stored in UTC (config/app.php); the panel displays
        // and accepts date-times in the company's local timezone.
        FilamentTimezone::set('Europe/Madrid');
    }
}
