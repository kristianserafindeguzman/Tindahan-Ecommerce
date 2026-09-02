<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:auto-cancel')->everyFiveMinutes();

// Run ML Demand Forecast daily at midnight
Schedule::command('ml:run-demand-forecast --train')->daily();

// Run ML Personalization daily at 1 AM (Full Retrain)
Schedule::command('ml:run-personalization --train')->dailyAt('01:00');
