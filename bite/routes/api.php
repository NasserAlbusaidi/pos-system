<?php

use App\Http\Controllers\Api\Guest\GuestOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest ordering JSON API (#51)
|--------------------------------------------------------------------------
|
| Public, no-auth JSON endpoints that wrap GuestOrderService for an external
| QR menu SPA/app. Throttled by IP. Reads are authorized by the unguessable
| tracking_token. The table/call-waiter endpoints from the original spec are
| intentionally absent — the table system was removed (2026_03_02).
|
*/

Route::prefix('guest')->name('api.guest.')->group(function () {
    Route::post('orders/quote', [GuestOrderController::class, 'quote'])
        ->middleware('throttle:guest-api')
        ->name('orders.quote');

    Route::post('orders', [GuestOrderController::class, 'store'])
        ->middleware('throttle:guest-orders')
        ->name('orders.store');

    Route::get('orders/{order:tracking_token}', [GuestOrderController::class, 'show'])
        ->whereUuid('order')
        ->middleware('throttle:guest-api')
        ->name('orders.show');
});
