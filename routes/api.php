<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payment\StripeController;
use App\Http\Controllers\WebhookController;

// Payment routes
Route::post('/payment/stripe/create-intent', [StripeController::class, 'createPaymentIntent'])->name('api.stripe.create-intent');

// Webhook routes (no CSRF protection)
Route::post('/webhooks/stripe', [WebhookController::class, 'handle'])->name('webhooks.stripe');
Route::post('/webhooks/paymob', [\App\Http\Controllers\Payment\PaymobController::class, 'callback'])->name('webhooks.paymob');
