<?php

namespace App\Traits;

use App\Scopes\CurrentCompanyScope;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

trait CompanyOwned
{
    public static function bootCompanyOwned(): void
    {
        static::creating(static function ($model) {
            if (empty($model->company_id)) {
                if (Auth::check() && Auth::user()->currentCompany) {
                    $model->company_id = Auth::user()->currentCompany->id;
                } else {
                    Notification::make()
                        ->danger()
                        ->title('Oops! Unable to Assign Company')
                        ->body('We encountered an issue while creating the record. Please ensure you are logged in and have a valid company associated with your account.')
                        ->persistent()
                        ->send();
                }
            }
        });

        static::addGlobalScope(new CurrentCompanyScope);
    }
}
