<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use  App\Http\Controllers\PaymentController;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::post('/mark', [ScanController::class, 'mark']);

// ✅ FASPAY CALLBACK (server-to-server, NO AUTH, NO CSRF)
Route::post('/payment/faspay/callback', [PaymentController::class, 'callback'])
    ->name('payment.faspay.callback');

// ✅ Check payment status (for testing)
Route::post('/payment/check-status', [PaymentController::class, 'checkStatus'])
    ->name('payment.check-status');