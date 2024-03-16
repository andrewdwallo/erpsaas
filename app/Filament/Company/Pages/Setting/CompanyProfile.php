<?php

namespace App\Filament\Company\Pages\Setting;

use App\Enums\EntityType;
use App\Models\Locale\City;
use App\Models\Locale\Country;
use App\Models\Locale\State;
use App\Models\Setting\CompanyProfile as CompanyProfileModel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
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

    protected static ?string $slug = 'settings/company-profile';

    protected static string $view = 'filament.company.pages.setting.company-profile';

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

    public static function getNavigationParentItem(): ?string
    {
        if (Filament::hasTopNavigation()) {
            return translate('Company');
        }

        return null;
    }

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
                $this->getLocationDetailsSection(),
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
                            ->required(),
                        TextInput::make('phone_number')
                            ->tel()
                            ->nullable()
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
                    ->panelLayout('compact')
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

    protected function getLocationDetailsSection(): Component
    {
        return Section::make('Location Details')
            ->schema([
                Select::make('country')
                    ->searchable()
                    ->localizeLabel()
                    ->live()
                    ->options(Country::getAvailableCountryOptions())
                    ->afterStateUpdated(static function (Set $set) {
                        $set('state_id', null);
                        $set('city_id', null);
                    })
                    ->required(),
                Select::make('state_id')
                    ->localizeLabel('State / Province')
                    ->searchable()
                    ->live()
                    ->options(static fn (Get $get) => State::getStateOptions($get('country')))
                    ->nullable(),
                TextInput::make('address')
                    ->localizeLabel('Street Address')
                    ->maxLength(255)
                    ->nullable(),
                Select::make('city_id')
                    ->localizeLabel('City / Town')
                    ->searchable()
                    ->options(static fn (Get $get) => City::getCityOptions($get('country'), $get('state_id')))
                    ->nullable(),
                TextInput::make('zip_code')
                    ->localizeLabel('Zip / Postal Code')
                    ->maxLength(20)
                    ->nullable(),
            ])->columns();
    }

    protected function getLegalAndComplianceSection(): Component
    {
        return Section::make('Legal & Compliance')
            ->schema([
                Select::make('entity_type')
                    ->localizeLabel()
                    ->options(EntityType::class)
                    ->required(),
                TextInput::make('tax_id')
                    ->localizeLabel('Tax ID')
                    ->maxLength(50)
                    ->nullable(),
            ])->columns();
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
