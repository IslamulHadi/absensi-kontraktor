<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:mark-absent-for-missing')
    ->timezone((string) config('attendance.day_close_timezone'))
    ->dailyAt((string) config('attendance.day_close_time'));
