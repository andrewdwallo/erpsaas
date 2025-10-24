<?php

namespace App\Filament\Company\Clusters\Settings\Pages;

use App\Enums\Setting\EntityType;
use App\Filament\Company\Clusters\Settings;
use App\Filament\Forms\Components\AddressFields;
use App\Filament\Forms\Components\Banner;
use App\Models\Setting\CompanyProfile as CompanyProfileModel;
use App\Utilities\Localization\Timezone;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

use function Filament\authorize;

/**
 * @property Form $form
 */
class CompanyProfile extends Page
{
    use InteractsWithFormActions;

    protected static ?string $title = 'Company Profile';

    protected static string $view = 'filament.company.pages.setting.company-profile';

    protected static ?string $cluster = Settings::class;

    public ?array $data = [];

    #[Locked]
    public ?CompanyProfileModel $record = null;

    public function getTitle(): string | Htmlable
    {
        return translate(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return translate(static::$title);
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::ScreenTwoExtraLarge;
    }

    public function mount(): void
    {
        $this->record = CompanyProfileModel::firstOrNew([
            'company_id' => auth()->user()->current_company_id,
        ]);

        abort_unless(static::canView($this->record), 404);

        $this->fillForm();
    }

    public function fillForm(): void
    {
        $data = $this->record->attributesToArray();

        $this->form->fill($data);
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $this->handleRecordUpdate($this->record, $data);
        } catch (Halt $exception) {
            return;
        }

        $this->getSavedNotification()->send();
    }

    protected function updateTimezone(string $countryCode): void
    {
        $model = \App\Models\Setting\Localization::firstOrFail();

        $timezones = Timezone::getTimezonesForCountry($countryCode);

        if (! empty($timezones)) {
            $model->update([
                'timezone' => $timezones[0],
            ]);
        }
    }

    protected function getTimezoneChangeNotification(): Notification
    {
        return Notification::make()
            ->info()
            ->title('Timezone update required')
            ->body('You have changed your country or state. Please update your timezone to ensure accurate date and time information.')
            ->actions([
                \Filament\Notifications\Actions\Action::make('updateTimezone')
                    ->label('Update timezone')
                    ->url(Localization::getUrl()),
            ])
            ->persistent()
            ->send();
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getIdentificationSection(),
                $this->getNeedsAddressCompletionAlert(),
                $this->getLocationDetailsSection(),
                $this->getSwissQrBillSection(),
                $this->getLegalAndComplianceSection(),
            ])
            ->model($this->record)
            ->statePath('data')
            ->operation('edit');
    }

    protected function getIdentificationSection(): Component
    {
        return Section::make('Identification')
            ->schema([
                Group::make()
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->localizeLabel()
                            ->maxLength(255)
                            ->softRequired(),
                        TextInput::make('phone_number')
                            ->tel()
                            ->localizeLabel(),
                    ])->columns(1),
                FileUpload::make('logo')
                    ->openable()
                    ->maxSize(2048)
                    ->localizeLabel()
                    ->visibility('public')
                    ->disk('public')
                    ->directory('logos/company')
                    ->imageResizeMode('contain')
                    ->imageCropAspectRatio('1:1')
                    ->panelAspectRatio('1:1')
                    ->panelLayout('integrated')
                    ->removeUploadedFileButtonPosition('center bottom')
                    ->uploadButtonPosition('center bottom')
                    ->uploadProgressIndicatorPosition('center bottom')
                    ->getUploadedFileNameForStorageUsing(
                        static fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                            ->prepend(Auth::user()->currentCompany->id . '_'),
                    )
                    ->extraAttributes(['class' => 'w-32 h-32'])
                    ->acceptedFileTypes(['image/png', 'image/jpeg']),
            ])->columns();
    }

    protected function getNeedsAddressCompletionAlert(): Component
    {
        return Banner::make('needsAddressCompletion')
            ->warning()
            ->title('Address information incomplete')
            ->description('Please complete the required address information for proper business operations.')
            ->visible(fn (CompanyProfileModel $record) => $record->address->isIncomplete())
            ->columnSpanFull();
    }

    protected function getLocationDetailsSection(): Component
    {
        return Section::make('Address Information')
            ->relationship('address')
            ->schema([
                Hidden::make('type')
                    ->default('general'),
                AddressFields::make()
                    ->required()
                    ->softRequired()
                    ->disabledCountry(is_demo_environment()),
            ])
            ->columns(2);
    }

    protected function getLegalAndComplianceSection(): Component
    {
        return Section::make('Legal & Compliance')
            ->schema([
                Select::make('entity_type')
                    ->localizeLabel()
                    ->options(EntityType::class)
                    ->softRequired(),
                TextInput::make('tax_id')
                    ->localizeLabel('Tax ID')
                    ->maxLength(50),
            ])->columns();
    }

    protected function getSwissQrBillSection(): Component
    {
        return Section::make('Swiss QR Bill')
            ->description('Enable Swiss QR payment slips for your invoices')
            ->schema([
                \Filament\Forms\Components\Toggle::make('qr_bill_enabled')
                    ->label('Enable Swiss QR Bill')
                    ->reactive()
                    ->columnSpanFull(),
                
                Group::make()
                    ->schema([
                        \Filament\Forms\Components\Radio::make('qr_bill_mode')
                            ->label('QR Bill Modus')
                            ->options([
                                'iban' => 'Normale IBAN ohne Referenz (Details pro Rechnung)',
                                'qr_iban' => 'QR-IBAN mit automatischer Referenz (Schema konfiguriert)',
                            ])
                            ->descriptions([
                                'iban' => 'Verwende normale IBAN. Zahlungsreferenz wird bei jeder Rechnung individuell angegeben.',
                                'qr_iban' => 'Verwende QR-IBAN. Zahlungsreferenz wird automatisch nach konfigurierbarem Schema generiert.',
                            ])
                            ->default('iban')
                            ->inline(false)
                            ->required(fn (\Filament\Forms\Get $get) => $get('qr_bill_enabled'))
                            ->reactive()
                            ->columnSpanFull(),
                            
                        TextInput::make('qr_bill_iban')
                            ->label(fn (\Filament\Forms\Get $get) => $get('qr_bill_mode') === 'qr_iban' ? 'QR-IBAN' : 'IBAN')
                            ->placeholder(fn (\Filament\Forms\Get $get) => $get('qr_bill_mode') === 'qr_iban' ? 'CH61 3078 2005 4610 0410 1 (QR-IBAN)' : 'CH93 0076 2011 6238 5295 7 (normale IBAN)')
                            ->maxLength(34)
                            ->required(fn (\Filament\Forms\Get $get) => $get('qr_bill_enabled'))
                            ->rules([
                                'nullable', 'string', 'max:34',
                                function (\Filament\Forms\Get $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        if (!$value) return;
                                        
                                        $cleanIban = preg_replace('/\s+/', '', $value);
                                        $mode = $get('qr_bill_mode');
                                        
                                        // Basic IBAN format check
                                        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $cleanIban)) {
                                            $fail('Die IBAN hat ein ungültiges Format.');
                                            return;
                                        }
                                        
                                        // Check if it's a Swiss IBAN
                                        if (!str_starts_with($cleanIban, 'CH')) {
                                            $fail('Nur Schweizer IBANs werden unterstützt.');
                                            return;
                                        }
                                        
                                        // Extract clearing number (positions 5-9)
                                        if (strlen($cleanIban) < 21) {
                                            $fail('Die IBAN ist zu kurz (mindestens 21 Zeichen erforderlich).');
                                            return;
                                        }
                                        
                                        $clearingNumber = (int) substr($cleanIban, 4, 5);
                                        $isQrIban = $clearingNumber >= 30000 && $clearingNumber <= 31999;
                                        
                                        if ($mode === 'qr_iban' && !$isQrIban) {
                                            $fail('Für QR-IBAN Modus wird eine QR-IBAN benötigt (Clearing-Nummer 30000-31999). Ihre IBAN hat Clearing-Nummer ' . $clearingNumber . '.');
                                        } elseif ($mode === 'iban' && $isQrIban) {
                                            $fail('Für IBAN Modus sollte eine normale IBAN verwendet werden. Ihre IBAN ist eine QR-IBAN (Clearing-Nummer ' . $clearingNumber . ').');
                                        }
                                    };
                                }
                            ])
                            ->live()
                            ->dehydrateStateUsing(fn ($state) => $state ? preg_replace('/\s+/', '', $state) : null)
                            ->hint('ℹ️')
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('ibanInfo')
                                    ->icon('heroicon-m-information-circle')
                                    ->modalHeading('IBAN vs QR-IBAN')
                                    ->modalContent(view('filament.info-modals.iban-info'))
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Schließen')
                            )
                            ->helperText(fn (\Filament\Forms\Get $get) => $get('qr_bill_mode') === 'qr_iban' ? 'QR-IBAN mit Clearing-Nummer 30000-31999' : 'Normale IBAN für Zahlungen ohne automatische Referenz')
                            ->columnSpanFull(),
                            
                        TextInput::make('qr_bill_reference_pattern')
                            ->label('Referenz-Schema')
                            ->placeholder('{invoice_id} oder {account_number}-{invoice_number}')
                            ->maxLength(100)
                            ->required(fn (\Filament\Forms\Get $get) => $get('qr_bill_enabled') && $get('qr_bill_mode') === 'qr_iban')
                            ->hint('ℹ️')
                            ->hintAction(
                                \Filament\Forms\Components\Actions\Action::make('patternInfo')
                                    ->icon('heroicon-m-information-circle')
                                    ->modalHeading('Referenz-Schema Platzhalter')
                                    ->modalContent(view('filament.info-modals.reference-pattern-info'))
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Schließen')
                            )
                            ->helperText('Schema für automatische Referenz-Generierung. Platzhalter: {invoice_id}, {invoice_number}, {account_number}, {date_y}, {date_m}, {date_d} (nur Zahlen werden extrahiert)')
                            ->visible(fn (\Filament\Forms\Get $get) => $get('qr_bill_mode') === 'qr_iban')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->visible(fn (\Filament\Forms\Get $get) => $get('qr_bill_enabled')),
            ]);
    }

    protected function handleRecordUpdate(CompanyProfileModel $record, array $data): CompanyProfileModel
    {
        $record->fill($data);

        $keysToWatch = [
            'logo',
        ];

        if ($record->isDirty($keysToWatch)) {
            $this->dispatch('companyProfileUpdated');
        }

        $record->save();

        return $record;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    public static function canView(Model $record): bool
    {
        try {
            return authorize('update', $record)->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }
}
