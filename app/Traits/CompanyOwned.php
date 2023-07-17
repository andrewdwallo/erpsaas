<?php

namespace App\Traits;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

trait CompanyOwned
{
    public static function bootCompanyOwned(): void
    {
        static::created(static function ($model) {
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
        });
    }
}
