<?php

use App\Http\Controllers\DocumentPrintController;
use App\Http\Middleware\AllowSameOriginFrame;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check() && $company = auth()->user()->primaryCompany()) {
        return redirect(Filament::getDefaultPanel()->getUrl(tenant: $company));
    }

    return redirect(Filament::getDefaultPanel()->getLoginUrl());
});

Route::middleware(['auth'])->group(function () {
    Route::get('documents/{documentType}/{id}/print', [DocumentPrintController::class, 'show'])
        ->middleware(AllowSameOriginFrame::class)
        ->name('documents.print');
});
