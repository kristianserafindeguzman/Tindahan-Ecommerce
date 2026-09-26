<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// A safety net, not a dependency: cart expiry is handled by CartReservationService on the request path,
// so this only catches carts nobody opens. Stale order auto-cancel does still need it.
// The lock expires in 2 minutes, not the default 1440. A run takes about a second, so a longer lock
// only matters when a run is killed mid-flight — and then the default would mute the job for a day.
Schedule::command('orders:auto-cancel')->everyMinute()->withoutOverlapping(2);

// Run ML Demand Forecast daily at midnight
Schedule::command('ml:run-demand-forecast --train')->daily();

// Run ML Personalization daily at 1 AM (Full Retrain)
Schedule::command('ml:run-personalization --train')->dailyAt('01:00');
