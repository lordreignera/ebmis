<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
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

        // Record only completed web logins. The schema check keeps the app
        // safe during deployments where code may arrive before the migration.
        Event::listen(Login::class, function (Login $event): void {
            if ($event->guard !== 'web') {
                return;
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                return;
            }

            DB::table('users')
                ->where('id', $event->user->getAuthIdentifier())
                ->update(['last_login_at' => now()]);
        });
    }
}
