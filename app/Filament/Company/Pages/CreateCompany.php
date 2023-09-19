<?php

namespace App\Filament\Company\Pages;

use App\Enums\EntityType;
use App\Events\CompanyGenerated;
use App\Models\Setting\CompanyProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Wallo\FilamentCompanies\Events\AddingCompany;
use Wallo\FilamentCompanies\FilamentCompanies;
use Wallo\FilamentCompanies\Pages\Company\CreateCompany as FilamentCreateCompany;

class CreateCompany extends FilamentCreateCompany
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-companies::default.labels.company_name'))
                    ->autofocus()
                    ->maxLength(255)
                    ->required(),
                TextInput::make('profile.email')
                    ->label('Company Email')
                    ->email()
                    ->required(),
                Select::make('profile.entity_type')
                    ->label('Entity Type')
                    ->native(false)
                    ->options(EntityType::class)
                    ->required(),
                Select::make('profile.country')
                    ->label('Country')
                    ->native(false)
                    ->searchable()
                    ->options(CompanyProfile::getAvailableCountryOptions())
                    ->required(),
            ])
            ->model(FilamentCompanies::companyModel())
            ->statePath('data');
    }

    protected function handleRegistration(array $data): Model
    {
        $user = Auth::user();

        Gate::forUser($user)->authorize('create', FilamentCompanies::newCompanyModel());

        AddingCompany::dispatch($user);

        $personalCompany = $user?->personalCompany() === null;

        $company = $user?->ownedCompanies()->create([
            'name' => $data['name'],
            'personal_company' => $personalCompany,
        ]);

        $company->profile()->create([
            'email' => $data['profile']['email'],
            'entity_type' => $data['profile']['entity_type'],
            'country' => $data['profile']['country'],
        ]);

        $user?->switchCompany($company);

        $name = $data['name'];

        CompanyGenerated::dispatch($user, $company, $data['profile']['country']);

        $this->companyCreated($name);

        return $company;
    }
}
