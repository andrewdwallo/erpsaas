<?php

namespace App\Filament\Company\Pages\Setting;

use App\Enums\EntityType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use App\Models\Setting\CompanyProfile as CompanyProfileModel;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
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

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Company Profile';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/company-profile';

    protected ?string $heading = 'Company Profile';

    protected static string $view = 'filament.company.pages.setting.company-profile';

    public ?array $data = [];

    #[Locked]
    public ?CompanyProfileModel $record = null;

    public function mount(): void
    {
        $this->record = CompanyProfileModel::firstOrNew([
            'company_id' => auth()->user()->currentCompany->id,
        ]);

        abort_unless(static::canView($this->record), 404);

        $this->fillForm();
    }

    public function fillForm(): void
    {
        $data = $this->record->attributesToArray();

        $data = $this->mutateFormDataBeforeFill($data);

        $data['fiscal_year_start'] = now()->startOfYear()->toDateString();
        $data['fiscal_year_end'] = now()->endOfYear()->toDateString();

        $this->form->fill($data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            $data = $this->mutateFormDataBeforeSave($data);

            $this->handleRecordUpdate($this->record, $data);

        } catch (Halt $exception) {
            return;
        }

        $this->getSavedNotification()?->send();

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl);
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        $title = $this->getSavedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($this->getSavedNotificationTitle());
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('filament-panels::pages/tenancy/edit-tenant-profile.notifications.saved.title');
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getIdentificationSection(),
                $this->getLocationDetailsSection(),
                $this->getLegalAndComplianceSection(),
                $this->getFiscalYearSection(),
            ])
            ->model($this->record)
            ->statePath('data')
            ->operation('edit');
    }

    protected function getIdentificationSection(): Component
    {
        return Section::make('Identification')
            ->schema([
                FileUpload::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->directory('logos/company')
                    ->imageResizeMode('contain')
                    ->imagePreviewHeight('250')
                    ->imageCropAspectRatio('2:1')
                    ->getUploadedFileNameForStorageUsing(
                        static fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                            ->prepend(Auth::user()->currentCompany->id . '_'),
                    )
                    ->openable()
                    ->maxSize(2048)
                    ->image()
                    ->visibility('public')
                    ->acceptedFileTypes(['image/png', 'image/jpeg']),
                Group::make()
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->nullable(),
                    ])->columns(1)
            ])->columns();
    }

    protected function getLocationDetailsSection(): Component
    {
        return Section::make('Location Details')
            ->schema([
                Select::make('country')
                    ->label('Country')
                    ->native(false)
                    ->live()
                    ->searchable()
                    ->options(CompanyProfileModel::getAvailableCountryOptions())
                    ->required(),
                Select::make('state')
                    ->label('State / Province')
                    ->searchable()
                    ->options(static fn (Get $get) => CompanyProfileModel::getStateOptions($get('country')))
                    ->nullable(),
                Select::make('timezone')
                    ->label('Timezone')
                    ->searchable()
                    ->options(static fn (Get $get) => CompanyProfileModel::getTimezoneOptions($get('country')))
                    ->nullable(),
                TextInput::make('address')
                    ->label('Street Address')
                    ->maxLength(255)
                    ->nullable(),
                Select::make('city_id')
                    ->label('City / Town')
                    ->searchable()
                    ->options(static fn (Get $get) => CompanyProfileModel::getCityOptions($get('country'), $get('state')))
                    ->nullable(),
                TextInput::make('zip_code')
                    ->label('Zip Code')
                    ->maxLength(20)
                    ->nullable(),
            ])->columns();
    }

    protected function getLegalAndComplianceSection(): Component
    {
        return Section::make('Legal & Compliance')
            ->schema([
                Select::make('entity_type')
                    ->label('Entity Type')
                    ->native(false)
                    ->options(EntityType::class)
                    ->required(),
                TextInput::make('tax_id')
                    ->label('Tax ID')
                    ->maxLength(50)
                    ->nullable(),
            ])->columns();
    }

    protected function getFiscalYearSection(): Component
    {
        return Section::make('Fiscal Year')
            ->schema([
                DatePicker::make('fiscal_year_start')
                    ->label('Start')
                    ->native(false)
                    ->seconds(false)
                    ->rule('required'),
                DatePicker::make('fiscal_year_end')
                    ->label('End')
                    ->minDate(static fn (Get $get) => $get('fiscal_year_start'))
                    ->native(false)
                    ->seconds(false)
                    ->rule('required'),
            ])->columns();
    }

    protected function handleRecordUpdate(CompanyProfileModel $record, array $data): CompanyProfileModel
    {
        $record->update($data);

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
            ->label(__('filament-panels::pages/tenancy/edit-tenant-profile.form.actions.save.label'))
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
