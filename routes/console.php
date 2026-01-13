<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clean logs older than 30 days - runs daily
Schedule::command('logs:clean')->daily();

// Clear news cache hourly (for cache invalidation every hour)
Schedule::command('cache:clear-news')->hourly();
