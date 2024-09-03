<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PromoCommandServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->commands([
            CheckExpiredPromos::class,
        ]);
    }
    /**
     * Bootstrap services.
     */
    public function boot(Schedule $schedule)
{
    // Schedule the command to run every minute for testing purposes
    $schedule->command('promos:check-expired')->everyMinute();
}
}
