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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

use function Filament\authorize;

/**
 * @property \Filament\Schemas\Schema $form
 */
class CompanyProfile extends Page
{
    use InteractsWithFormActions;

    protected static ?string $title = 'Company Profile';

    protected string $view = 'filament.company.pages.setting.company-profile';

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

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::ScreenTwoExtraLarge;
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
                Action::make('updateTimezone')
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getIdentificationSection(),
                // $this->getNeedsAddressCompletionAlert(),
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

    protected function handleRecordUpdate(CompanyProfileModel $record, array $data): CompanyProfileModel
    {
        $record->fill($data);

        $keysToWatch = [
            'logo',
        ];

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

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->sticky($this->areFormActionsSticky()),
            ]);
    }
}
