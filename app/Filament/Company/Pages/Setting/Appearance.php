<?php

namespace App\Filament\Company\Pages\Setting;

use App\Enums\Font;
use App\Enums\MaxContentWidth;
use App\Enums\ModalWidth;
use App\Enums\PrimaryColor;
use App\Enums\RecordsPerPage;
use App\Enums\TableSortDirection;
use App\Models\Setting\Appearance as AppearanceModel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Wallo\FilamentSelectify\Components\ToggleButton;

use function Filament\authorize;

/**
 * @property Form $form
 */
class Appearance extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';

    protected static ?string $title = 'Appearance';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/appearance';

    protected static string $view = 'filament.company.pages.setting.appearance';

    public ?array $data = [];

    #[Locked]
    public ?AppearanceModel $record = null;

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
        $this->record = AppearanceModel::firstOrNew([
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
                    ->allowHtml()
                    ->softRequired()
                    ->localizeLabel()
                    ->options(
                        collect(PrimaryColor::cases())
                            ->mapWithKeys(static fn ($case) => [
                                $case->value => "<span class='flex items-center gap-x-4'>
                                <span class='rounded-full w-4 h-4' style='background:rgb(" . $case->getColor()[600] . ")'></span>
                                <span>" . $case->getLabel() . '</span>
                                </span>',
                            ]),
                    ),
                Select::make('font')
                    ->allowHtml()
                    ->softRequired()
                    ->localizeLabel()
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
                    ->softRequired()
                    ->localizeLabel()
                    ->options(MaxContentWidth::class),
                Select::make('modal_width')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(ModalWidth::class),
                Select::make('has_top_navigation')
                    ->localizeLabel('Navigation Layout')
                    ->selectablePlaceholder(false)
                    ->boolean(translate('Top Navigation'), translate('Side Navigation')),
                ToggleButton::make('is_table_striped')
                    ->localizeLabel('Striped Tables')
                    ->onLabel(translate('Enabled'))
                    ->offLabel(translate('Disabled')),
            ])->columns();
    }

    protected function getDataPresentationSection(): Component
    {
        return Section::make('Data Presentation')
            ->schema([
                Select::make('table_sort_direction')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(TableSortDirection::class),
                Select::make('records_per_page')
                    ->softRequired()
                    ->localizeLabel()
                    ->options(RecordsPerPage::class),
            ])->columns();
    }

    protected function handleRecordUpdate(AppearanceModel $record, array $data): AppearanceModel
    {
        $record->fill($data);

        $keysToWatch = [
            'primary_color',
            'max_content_width',
            'has_top_navigation',
            'font',
        ];

        if ($record->isDirty($keysToWatch)) {
            $this->dispatch('appearanceUpdated');
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
