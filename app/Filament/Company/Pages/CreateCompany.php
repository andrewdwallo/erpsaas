<?php

namespace App\Filament\Company\Pages;

use App\Enums\Common\AddressType;
use App\Enums\Setting\EntityType;
use App\Models\Company;
use App\Models\Locale\Country;
use App\Models\Setting\Localization;
use App\Services\CompanyDefaultService;
use App\Utilities\Currency\CurrencyAccessor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Wallo\FilamentCompanies\Events\AddingCompany;
use Wallo\FilamentCompanies\FilamentCompanies;
use Wallo\FilamentCompanies\Pages\Company\CreateCompany as FilamentCreateCompany;

class CreateCompany extends FilamentCreateCompany
{
    protected bool $hasTopbar = false;

    protected static string $view = 'filament.company.pages.create-company';

    protected static string $layout = 'components.company.layout.custom-simple';

    public function getHeading(): string | Htmlable
    {
        return '';
    }

    public function getMaxWidth(): MaxWidth | string | null
    {
        return MaxWidth::FourExtraLarge;
    }

    public function hasLogo(): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('filament-companies::default.labels.company_name'))
                    ->autofocus()
                    ->maxLength(255)
                    ->softRequired(),
                TextInput::make('profile.email')
                    ->label('Company email')
                    ->email()
                    ->softRequired(),
                Select::make('profile.entity_type')
                    ->label('Entity type')
                    ->options(EntityType::class)
                    ->softRequired(),
                Select::make('profile.country')
                    ->label('Country')
                    ->live()
                    ->searchable()
                    ->options(Country::getAvailableCountryOptions())
                    ->getSearchResultsUsing(fn (string $search): array => Country::getSearchResultsUsing($search))
                    ->getOptionLabelUsing(fn ($value): ?string => Country::find($value)?->name . ' ' . Country::find($value)?->flag)
                    ->softRequired(),
                Select::make('locale.language')
                    ->label('Language')
                    ->searchable()
                    ->options(Localization::getAllLanguages())
                    ->softRequired(),
                Select::make('currencies.code')
                    ->label('Currency')
                    ->searchable()
                    ->options(CurrencyAccessor::getAllCurrencyOptions())
                    ->optionsLimit(5)
                    ->softRequired(),
            ])
            ->columns()
            ->model(FilamentCompanies::companyModel())
            ->statePath('data');
    }

    protected function handleRegistration(array $data): Model
    {
        $user = Auth::user();

        Gate::forUser($user)->authorize('create', FilamentCompanies::newCompanyModel());

        AddingCompany::dispatch($user);

        $personalCompany = $user?->personalCompany() === null;

        return DB::transaction(function () use ($user, $data, $personalCompany) {
            /** @var Company $company */
            $company = $user?->ownedCompanies()->create([
                'name' => $data['name'],
                'personal_company' => $personalCompany,
            ]);

            $profile = $company->profile()->create([
                'email' => $data['profile']['email'],
                'entity_type' => $data['profile']['entity_type'],
            ]);

            $profile->address()->create([
                'company_id' => $company->id,
                'type' => AddressType::General,
                'country_code' => $data['profile']['country'],
            ]);

            $user?->switchCompany($company);

            $companyDefaultService = app(CompanyDefaultService::class);
            $user = $company->owner ?? $user;
            $companyDefaultService->createCompanyDefaults($company, $user, $data['currencies']['code'], $data['profile']['country'], $data['locale']['language']);

            $this->companyCreated($data['name']);

            return $company;
        });
    }
}
