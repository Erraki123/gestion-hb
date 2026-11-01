<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run')->dailyAt(env('BACKUP_TIME', '02:00'));
Schedule::command('backup:monitor')->dailyAt('03:00');
Schedule::command('activity:archive')->monthlyOn(1, '03:30');
