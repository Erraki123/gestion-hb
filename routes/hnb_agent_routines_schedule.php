<?php

use Illuminate\Support\Facades\Schedule;

// Read-only collection of existing reminders into daily per-user counts.
// The existing schedule:run is enough; this never sends external messages.
Schedule::command('hnb:routines:refresh')->dailyAt('08:20')->withoutOverlapping();
