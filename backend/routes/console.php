<?php

use App\Jobs\Shipment\DetectCODDiscrepanciesJob;
use App\Jobs\Shipment\FetchGHTKRemittanceJob;
use App\Jobs\Shipment\SyncAllTrackingJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule tracking sync every 12 hours
Schedule::job(new SyncAllTrackingJob('ghtk'))->everySixHours();
Schedule::job(new SyncAllTrackingJob('ghn'))->everySixHours();

// Schedule discrepancy detection daily at 8 AM
Schedule::job(new DetectCODDiscrepanciesJob())->dailyAt('08:00');

// Schedule GHTK remittance fetch daily at 6 AM
Schedule::job(new FetchGHTKRemittanceJob())->dailyAt('06:00');
