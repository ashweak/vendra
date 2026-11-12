<?php

use Illuminate\Support\Facades\Schedule;

// Expiry checks also run during actions; this scheduled cleanup catches the rest (P9).
Schedule::command('vendra:expire')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('vendra:retention')->dailyAt('02:30');
