<?php

use App\Http\Controllers\PlaidWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/plaid/webhook', [PlaidWebhookController::class, 'handleWebhook']);
