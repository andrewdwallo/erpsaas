<?php

namespace App\Filament\Company\Pages\Setting;

use App\Enums\DateFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Enums\WeekStart;
use App\Models\Setting\Localization as LocalizationModel;
use App\Utilities\Localization\Timezone;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

use function Filament\authorize;

/**
 * @property Form $form
 */
class Localization extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?string $title = 'Localization';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/localization';

    protected static string $view = 'filament.company.pages.setting.localization';

    public ?array $data = [];

    #[Locked]
    public ?LocalizationModel $record = null;

    public function getTitle(): string | Htmlable
    {
        return translate(static::$title);
    }

    public static function getNavigationLabel(): string
    {
        return translate(static::$title);
    }

    public function mount(): void
    {
        $this->record = LocalizationModel::firstOrNew([
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
                $this->getGeneralSection(),
                $this->getDateAndTimeSection(),
                $this->getFinancialAndFiscalSection(),
            ])
            ->model($this->record)
            ->statePath('data')
            ->operation('edit');
    }

    protected function getGeneralSection(): Component
    {
        return Section::make('General')
            ->schema([
                Select::make('language')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(LocalizationModel::getAllLanguages())
                    ->searchable(),
                Select::make('timezone')
                    ->localizeLabel()
                    ->options(Timezone::getTimezoneOptions(\App\Models\Setting\CompanyProfile::find(auth()->user()->currentCompany->id)->country))
                    ->searchable()
                    ->nullable(),
            ])->columns();
    }

    protected function getDateAndTimeSection(): Component
    {
        return Section::make('Date & Time')
            ->schema([
                Select::make('date_format')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(DateFormat::class)
                    ->live(),
                Select::make('time_format')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(TimeFormat::class),
                Select::make('week_start')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(WeekStart::class),
            ])->columns();
    }

    protected function getFinancialAndFiscalSection(): Component
    {
        $beforeNumber = translate('Before Number');
        $afterNumber = translate('After Number');
        $selectPosition = translate('Select Position');

        return Section::make('Financial & Fiscal')
            ->schema([
                DatePicker::make('fiscal_year_start')
                    ->localizeLabel()
                    ->live()
                    ->extraAttributes(['wire:key' => Str::random()]) // Required to reinitialize the datepicker when the date_format state changes
                    ->maxDate(static fn (Get $get) => $get('fiscal_year_end'))
                    ->displayFormat(static function (LocalizationModel $record, Get $get) {
                        return $get('date_format') ?? DateFormat::DEFAULT;
                    })
                    ->seconds(false)
                    ->softRequired(),
                DatePicker::make('fiscal_year_end')
                    ->softRequired()
                    ->localizeLabel()
                    ->live()
                    ->extraAttributes(['wire:key' => Str::random()]) // Required to reinitialize the datepicker when the date_format state changes
                    ->minDate(static fn (Get $get) => $get('fiscal_year_start'))
                    ->disabled(static fn (Get $get): bool => ! filled($get('fiscal_year_start')))
                    ->displayFormat(static function (LocalizationModel $record, Get $get) {
                        return $get('date_format') ?? DateFormat::DEFAULT;
                    })
                    ->seconds(false),
                Select::make('number_format')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(NumberFormat::class),
                Select::make('percent_first')
                    ->softRequired()
                    ->localizeLabel('Percent Position')
                    ->boolean($beforeNumber, $afterNumber, $selectPosition),
            ])->columns();
    }

    protected function handleRecordUpdate(LocalizationModel $record, array $data): LocalizationModel
    {
        $record->fill($data);

        $keysToWatch = [
            'language',
            'timezone',
            'date_format',
            'week_start',
            'time_format',
        ];

        if ($record->isDirty($keysToWatch)) {
            $this->dispatch('localizationUpdated');
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
