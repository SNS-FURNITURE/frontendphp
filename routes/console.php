<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('commercial:reports weekly')->weeklyOn(1, '6:00');
Schedule::command('commercial:reports monthly')->monthlyOn(1, '6:30');
Schedule::command('commercial:reports yearly')->yearlyOn(1, 1, '7:00');
