<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LoanScheduleService;
use App\Services\FeeManagementService;
use App\Services\LoanApprovalService;
use App\Services\MobileMoneyService;
use App\Services\RepaymentService;
use App\Services\DisbursementService;

class LoanServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register core services
        $this->app->singleton(LoanScheduleService::class, function ($app) {
            return new LoanScheduleService();
        });
        
        $this->app->singleton(FeeManagementService::class, function ($app) {
            return new FeeManagementService();
        });
        
        $this->app->singleton(MobileMoneyService::class, function ($app) {
            return new MobileMoneyService();
        });
        
        $this->app->singleton(RepaymentService::class, function ($app) {
            return new RepaymentService(
                $app->make(MobileMoneyService::class)
            );
        });
        
        $this->app->singleton(LoanApprovalService::class, function ($app) {
            return new LoanApprovalService(
                $app->make(FeeManagementService::class),
                $app->make(LoanScheduleService::class)
            );
        });
        
        $this->app->singleton(DisbursementService::class, function ($app) {
            return new DisbursementService(
                $app->make(FeeManagementService::class),
                $app->make(LoanScheduleService::class),
                $app->make(MobileMoneyService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}