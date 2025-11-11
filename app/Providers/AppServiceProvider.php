<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Force HTTPS in production (for Digital Ocean deployment)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        
        // Fix for MySQL key length issue with utf8mb4
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);
        
        // Fix route model binding for loan parameter
        // \Illuminate\Support\Facades\Route::model('loan', \App\Models\Loan::class);
    }
}
