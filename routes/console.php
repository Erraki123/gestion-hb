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

// 🔔 Résumé quotidien des chèques (e-mail / WhatsApp) : J-3, J-2, J-1, aujourd'hui, en retard
Schedule::command('cheques:digest')->dailyAt(env('CHEQUE_DIGEST_TIME', '08:00'));

// Envois en arrière-plan : un seul cron Hostinger (schedule:run) suffit, pas besoin de worker permanent
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()->withoutOverlapping();

require __DIR__ . '/direction_alerts_phase3c.php';
require __DIR__ . '/hnb_agent_routines_schedule.php';

\Illuminate\Support\Facades\Schedule::command('prospects:discover')->dailyAt('09:15')->withoutOverlapping();

\Illuminate\Support\Facades\Schedule::command('prospects:qualify')->dailyAt('09:35')->withoutOverlapping();

// HNB_PDF_CLI_QUEUE_V1: Original gold Chromium PDFs processed outside restricted web PHP.
if (PHP_OS_FAMILY === 'Linux') {
    \Illuminate\Support\Facades\Schedule::command('hnb:pdf-process --limit=1')->everyMinute()->withoutOverlapping();
}
