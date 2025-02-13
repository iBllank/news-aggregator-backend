<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');


// Making sure data is regularly fetched and updated from live sources
// in a live environment we can use cron jobs to run this every minute
Schedule::command('news:fetch')->everyMinute(); // maybe use everyFiveSeconds for fast testing
