<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Section 10.5: Register bills:process-daily to run daily at 06:00
Schedule::command('bills:process-daily')->dailyAt('06:00');
