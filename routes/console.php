<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic transaction checking every 5 minutes
Schedule::command('transactions:check')->everyFiveMinutes()->withoutOverlapping();

// Schedule automatic disbursement checking every 5 minutes
Schedule::command('disbursements:check')->everyFiveMinutes()->withoutOverlapping();

// ============================================
// AUTOMATIC REPAYMENT SYSTEM
// ============================================

// Daily loans: Initiate payment at 3:00 PM every day
Schedule::command('repayments:automate --type=daily')->dailyAt('15:00');

// Weekly loans: Initiate payment at 3:00 PM every Friday
Schedule::command('repayments:automate --type=weekly')->fridays()->at('15:00');

// Monthly loans: Initiate payment at 3:00 PM on payment date
Schedule::command('repayments:automate --type=monthly')->dailyAt('15:00');

// Process retries: Every hour (for clients who didn't provide PIN)
Schedule::command('repayments:automate --type=retry')->hourly();

// Generate late fees: At midnight for failed payments
Schedule::command('repayments:automate --type=late-fees')->dailyAt('00:00');

