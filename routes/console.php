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
