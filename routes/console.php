<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Filament\Admin\Resources\BookingResource;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('telescope:prune')->weekly();

Schedule::call(fn() => BookingResource::autoCompleteExpiredBookings())->dailyAt('00:05');