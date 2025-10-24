<?php

use App\Http\Controllers\DocumentPrintController;
use App\Http\Middleware\AllowSameOriginFrame;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(Filament::getDefaultPanel()->getUrl());
});

Route::middleware(['auth'])->group(function () {
    Route::get('documents/{documentType}/{id}/print', [DocumentPrintController::class, 'show'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('documents.print');
    
    Route::get('documents/invoice/{id}/qr-payment-slip', [DocumentPrintController::class, 'qrPaymentSlip'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('documents.qr-payment-slip');
    
    Route::get('documents/recurring_invoice/{id}/qr-payment-slip', [DocumentPrintController::class, 'qrPaymentSlipRecurring'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('documents.recurring-qr-payment-slip');
});
