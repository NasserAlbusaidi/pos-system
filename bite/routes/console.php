<?php

use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => \App\Models\Order::cancelExpired())
    ->name('orders.cancel-expired')
    ->everyMinute();

Schedule::call(fn () => \App\Models\GroupCart::cleanExpired())
    ->name('group-carts.clean-expired')
    ->hourly();

Schedule::command('webhook-events:prune --days=30')
    ->name('webhook-events.prune-processed')
    ->dailyAt('03:20');
