<?php

declare(strict_types=1);

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

// Outside the web group: no session, no CSRF. Providers retry on 429.
Route::post('webhooks/{driver}', PaymentWebhookController::class)
    ->middleware('throttle:300,1')
    ->name('webhooks.payment');
