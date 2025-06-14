<?php

namespace App\Filament\Company\Clusters\Settings\Pages;

use App\Events\CompanyDefaultUpdated;
use App\Filament\Company\Clusters\Settings;
use App\Models\Banking\BankAccount;
use App\Models\Setting\CompanyDefault as CompanyDefaultModel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\Locked;

use function Filament\authorize;

/**
 * @property \Filament\Schemas\Schema $form
 */
class CompanyDefault extends Page
{
    use InteractsWithFormActions;

    protected static ?string $title = 'Default';

    protected string $view = 'filament.company.pages.setting.company-default';

    protected static ?string $cluster = Settings::class;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    #[Locked]
    public ?CompanyDefaultModel $record = null;

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
        $this->record = CompanyDefaultModel::firstOrNew([
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
                $this->getGeneralSection(),
            ])
            ->model($this->record)
            ->statePath('data')
            ->operation('edit');
    }

    protected function getGeneralSection(): Component
    {
        return Section::make('General')
            ->schema([
                Select::make('bank_account_id')
                    ->localizeLabel()
                    ->relationship('bankAccount', 'name')
                    ->getOptionLabelFromRecordUsing(function (BankAccount $record) {
                        $name = $record->account->name;
                        $currency = $this->renderBadgeOptionLabel($record->account->currency_code);

                        return "{$name} ⁓ {$currency}";
                    })
                    ->allowHtml()
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->searchable()
                    ->preload(),
                Placeholder::make('currency_code')
                    ->label(translate('Currency'))
                    ->hintIcon('heroicon-o-question-mark-circle', 'You cannot change this after your company has been created. You can still use other currencies for transactions.')
                    ->content(static fn (CompanyDefaultModel $record) => "{$record->currency->code} {$record->currency->symbol} - {$record->currency->name}"),
            ])->columns();
    }

    public function renderBadgeOptionLabel(string $label): string
    {
        return Blade::render('filament::components.badge', [
            'color' => 'primary',
            'size' => 'sm',
            'slot' => $label,
        ]);
    }

    protected function handleRecordUpdate(CompanyDefaultModel $record, array $data): CompanyDefaultModel
    {
        CompanyDefaultUpdated::dispatch($record, $data);

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
            ->livewireSubmitHandler('register')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->sticky($this->areFormActionsSticky()),
            ]);
    }
}
