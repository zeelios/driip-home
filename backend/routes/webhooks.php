<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Shipment\WebhookController;

// Courier webhook endpoints - secured by HMAC signature middleware
Route::middleware(['throttle:1000,1', App\Http\Middleware\VerifyCourierWebhookSignature::class])
    ->group(function () {
        Route::post('ghn', [WebhookController::class, 'ghn'])->name('webhook.ghn');
        Route::post('ghtk', [WebhookController::class, 'ghtk'])->name('webhook.ghtk');
        Route::post('spx', [WebhookController::class, 'spx'])->name('webhook.spx');
        Route::post('viettel', [WebhookController::class, 'viettel'])->name('webhook.viettel');
    });
