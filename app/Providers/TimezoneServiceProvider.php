<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TimezoneServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set the application timezone
        $timezone = Config::get('app.timezone', 'Africa/Nairobi');
        date_default_timezone_set($timezone);
        
        // Set Carbon timezone globally
        Carbon::setLocale(Config::get('app.locale', 'en'));
        
        // Set MySQL timezone for database connections
        try {
            DB::statement("SET time_zone = '+03:00'");
        } catch (\Exception $e) {
            // Handle silently - timezone might already be set or connection not ready
        }
    }
}