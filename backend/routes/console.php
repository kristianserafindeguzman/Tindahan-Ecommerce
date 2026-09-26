<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Needs `php artisan schedule:work` (dev) or a cron entry to run at all; the buy paths release holds themselves, so this is only a sweep.
Schedule::command('orders:auto-cancel')->everyMinute()->withoutOverlapping();

// Run ML Demand Forecast daily at midnight
Schedule::command('ml:run-demand-forecast --train')->daily();

// Run ML Personalization daily at 1 AM (Full Retrain)
Schedule::command('ml:run-personalization --train')->dailyAt('01:00');
