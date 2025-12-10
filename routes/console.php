<?php

use App\Jobs\CheckOfflineDevices;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command("inspire", function () {
    $this->comment(Inspiring::quote());
})->purpose("Display an inspiring quote");

// Schedule offline device checks every 5 minutes
Schedule::job(new CheckOfflineDevices())->everyFiveMinutes();

// Schedule daily report cleanup
Schedule::command("reports:cleanup")->daily()->at("02:00");
