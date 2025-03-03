<?php

namespace App\Filament\Company\Clusters\Settings\Pages;

use App\Enums\Setting\DateFormat;
use App\Enums\Setting\NumberFormat;
use App\Enums\Setting\TimeFormat;
use App\Enums\Setting\WeekStart;
use App\Filament\Company\Clusters\Settings;
use App\Models\Setting\CompanyProfile as CompanyProfileModel;
use App\Models\Setting\Localization as LocalizationModel;
use App\Services\CompanySettingsService;
use App\Utilities\Localization\Timezone;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Exceptions\Halt;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

use function Filament\authorize;

/**
 * @property Form $form
 */
class Localization extends Page
{
    use InteractsWithFormActions;

    protected static ?string $title = 'Localization';

    protected static string $view = 'filament.company.pages.setting.localization';

    protected static ?string $cluster = Settings::class;

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

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::ScreenTwoExtraLarge;
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
                    ->softRequired()
                    ->localizeLabel()
                    ->options(Timezone::getTimezoneOptions(CompanyProfileModel::first()->address->country_code))
                    ->searchable(),
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
        $beforeNumber = translate('Before number');
        $afterNumber = translate('After number');
        $selectPosition = translate('Select position');

        return Section::make('Financial & Fiscal')
            ->schema([
                Select::make('number_format')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(NumberFormat::class),
                Select::make('percent_first')
                    ->softRequired()
                    ->localizeLabel('Percent position')
                    ->boolean($beforeNumber, $afterNumber, $selectPosition),
                Group::make()
                    ->schema([
                        Cluster::make([
                            Select::make('fiscal_year_end_month')
                                ->softRequired()
                                ->options(array_combine(range(1, 12), array_map(static fn ($month) => now()->month($month)->monthName, range(1, 12))))
                                ->afterStateUpdated(static fn (Set $set) => $set('fiscal_year_end_day', null))
                                ->columnSpan(2)
                                ->live(),
                            Select::make('fiscal_year_end_day')
                                ->placeholder('Day')
                                ->softRequired()
                                ->columnSpan(1)
                                ->options(function (Get $get) {
                                    $month = (int) $get('fiscal_year_end_month');

                                    $daysInMonth = now()->month($month)->daysInMonth;

                                    return array_combine(range(1, $daysInMonth), range(1, $daysInMonth));
                                })
                                ->live(),
                        ])
                            ->columns(3)
                            ->columnSpan(2)
                            ->required()
                            ->markAsRequired(false)
                            ->label('Fiscal year end'),
                    ])->columns(3),
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
            CompanySettingsService::invalidateSettings($record->company_id);
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
