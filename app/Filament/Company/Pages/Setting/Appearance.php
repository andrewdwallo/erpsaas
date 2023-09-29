<?php

namespace App\Filament\Company\Pages\Setting;

use App\Enums\{Font, MaxContentWidth, ModalWidth, PrimaryColor, RecordsPerPage, TableSortDirection};
use App\Models\Setting\Appearance as AppearanceModel;
use Filament\Actions\{Action, ActionGroup};
use Filament\Forms\Components\{Component, Section, Select};
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Wallo\FilamentSelectify\Components\{ButtonGroup, ToggleButton};

use function Filament\authorize;

/**
 * @property Form $form
 */
class Appearance extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $navigationLabel = 'Appearance';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/appearance';

    protected ?string $heading = 'Appearance';

    protected static string $view = 'filament.company.pages.setting.appearance';

    public ?array $data = [];

    #[Locked]
    public ?AppearanceModel $record = null;

    public function mount(): void
    {
        $this->record = AppearanceModel::firstOrNew([
            'company_id' => auth()->user()->currentCompany->id,
        ]);

        abort_unless(static::canView($this->record), 404);

        $this->fillForm();
    }

    public function fillForm(): void
    {
        $data = $this->record->attributesToArray();

        $data = $this->mutateFormDataBeforeFill($data);

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
                $this->getGeneralSection(),
                $this->getLayoutSection(),
                $this->getDataPresentationSection(),
            ])
            ->model($this->record)
            ->statePath('data')
            ->operation('edit');
    }

    protected function getGeneralSection(): Component
    {
        return Section::make('General')
            ->schema([
                Select::make('primary_color')
                    ->label('Primary Color')
                    ->native(false)
                    ->allowHtml()
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->options(
                        collect(PrimaryColor::cases())
                            ->mapWithKeys(static fn ($case) => [
                                $case->value => "<span class='flex items-center gap-x-4'>
                                <span class='rounded-full w-4 h-4' style='background:rgb(" . $case->getColor()[600] . ")'></span>
                                <span>" . str($case->value)->title() . '</span>
                                </span>',
                            ]),
                    ),
                Select::make('font')
                    ->label('Font')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->allowHtml()
                    ->options(
                        collect(Font::cases())
                            ->mapWithKeys(static fn ($case) => [
                                $case->value => "<span style='font-family:{$case->getLabel()}'>{$case->getLabel()}</span>",
                            ]),
                    ),
            ])->columns();
    }

    protected function getLayoutSection(): Component
    {
        return Section::make('Layout')
            ->schema([
                Select::make('max_content_width')
                    ->label('Max Content Width')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->options(MaxContentWidth::class),
                Select::make('modal_width')
                    ->label('Modal Width')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->options(ModalWidth::class),
                ButtonGroup::make('has_top_navigation')
                    ->label('Navigation Layout')
                    ->boolean('Top Navigation', 'Side Navigation')
                    ->rule('required'),
                ToggleButton::make('is_table_striped')
                    ->label('Striped Tables')
                    ->onLabel('Enabled')
                    ->offLabel('Disabled')
                    ->rule('required'),
            ])->columns();
    }

    protected function getDataPresentationSection(): Component
    {
        return Section::make('Data Presentation')
            ->schema([
                Select::make('table_sort_direction')
                    ->label('Table Sort Direction')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->options(TableSortDirection::class),
                Select::make('records_per_page')
                    ->label('Records Per Page')
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->options(RecordsPerPage::class),
            ])->columns();
    }

    protected function handleRecordUpdate(AppearanceModel $record, array $data): AppearanceModel
    {
        $record_array = array_map('strval', $record->toArray());
        $data_array = array_map('strval', $data);
        $diff = array_diff_assoc($data_array, $record_array);

        $keysToWatch = [
            'primary_color',
            'max_content_width',
            'has_top_navigation',
            'font',
        ];

        foreach ($diff as $key => $value) {
            if (in_array($key, $keysToWatch, true)) {
                $this->dispatch('appearanceUpdated');
            }
        }

        // If the primary color or font has changed, we need to update the associated models accent_color column.
        if (array_key_exists('primary_color', $diff) || array_key_exists('font', $diff)) {
            $primaryColorToHex = PrimaryColor::from($data['primary_color'])->getHexCode();
            $font = Font::from($data['font'])->value;
            $this->record->company->defaultBill()->update([
                'accent_color' => $primaryColorToHex,
                'font' => $font,
            ]);
            $this->record->company->defaultInvoice()->update([
                'accent_color' => $primaryColorToHex,
                'font' => $font,
            ]);
        }

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
