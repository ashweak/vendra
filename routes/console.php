<?php

use Illuminate\Support\Facades\Schedule;

// Expiry checks also run during actions; this scheduled cleanup catches the rest (P9).
Schedule::command('vendra:expire')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('vendra:retention')->dailyAt('02:30');
// Day-before booking reminders (each booking gets at most one).
Schedule::command('vendra:remind')->hourly()->withoutOverlapping();
// Owners' morning summary (7 AM in each business's timezone; the command checks the hour).
Schedule::command('vendra:daily-summary')->hourlyAt(2)->withoutOverlapping();

// Public demo only (the command does nothing otherwise): fresh fictional data every 6 hours.
Schedule::command('vendra:demo-reset')->everySixHours();
