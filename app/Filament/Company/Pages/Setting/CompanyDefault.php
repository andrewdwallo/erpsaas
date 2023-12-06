<?php

namespace App\Filament\Company\Pages\Setting;

use App\Events\CompanyDefaultUpdated;
use App\Models\Banking\Account;
use App\Models\Setting\CompanyDefault as CompanyDefaultModel;
use App\Models\Setting\Currency;
use App\Models\Setting\Discount;
use App\Models\Setting\Tax;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
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
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\Locked;

use function Filament\authorize;

/**
 * @property Form $form
 */
class CompanyDefault extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'Default';

    protected static ?string $slug = 'settings/default';

    protected static string $view = 'filament.company.pages.setting.company-default';

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

    public static function getNavigationParentItem(): ?string
    {
        if (Filament::hasTopNavigation()) {
            return translate('Personalization');
        }

        return null;
    }

    public function mount(): void
    {
        $this->record = CompanyDefaultModel::firstOrNew([
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
                $this->getModifiersSection(),
                $this->getCategoriesSection(),
            ])
            ->model($this->record)
            ->statePath('data')
            ->operation('edit');
    }

    protected function getGeneralSection(): Component
    {
        return Section::make('General')
            ->schema([
                Select::make('account_id')
                    ->localizeLabel()
                    ->relationship('account', 'name')
                    ->getOptionLabelFromRecordUsing(function (Account $record) {
                        $name = $record->name;
                        $currency = $this->renderBadgeOptionLabel($record->currency_code);

                        return "{$name} ⁓ {$currency}";
                    })
                    ->allowHtml()
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->searchable()
                    ->preload(),
                Select::make('currency_code')
                    ->softRequired()
                    ->localizeLabel('Currency')
                    ->relationship('currency', 'name')
                    ->getOptionLabelFromRecordUsing(static fn (Currency $record) => "{$record->code} {$record->symbol} - {$record->name}")
                    ->saveRelationshipsUsing(null)
                    ->searchable()
                    ->preload(),
            ])->columns();
    }

    protected function getModifiersSection(): Component
    {
        return Section::make('Taxes & Discounts')
            ->schema([
                Select::make('sales_tax_id')
                    ->softRequired()
                    ->localizeLabel()
                    ->relationship('salesTax', 'name')
                    ->getOptionLabelFromRecordUsing(function (Tax $record) {
                        $currencyCode = $this->record->currency_code;

                        $rate = rateFormat($record->rate, $record->computation->value, $currencyCode);

                        $rateBadge = $this->renderBadgeOptionLabel($rate);

                        return "{$record->name} ⁓ {$rateBadge}";
                    })
                    ->allowHtml()
                    ->saveRelationshipsUsing(null)
                    ->searchable(),
                Select::make('purchase_tax_id')
                    ->softRequired()
                    ->localizeLabel()
                    ->relationship('purchaseTax', 'name')
                    ->getOptionLabelFromRecordUsing(function (Tax $record) {
                        $currencyCode = $this->record->currency_code;

                        $rate = rateFormat($record->rate, $record->computation->value, $currencyCode);

                        $rateBadge = $this->renderBadgeOptionLabel($rate);

                        return "{$record->name} ⁓ {$rateBadge}";
                    })
                    ->allowHtml()
                    ->saveRelationshipsUsing(null)
                    ->searchable(),
                Select::make('sales_discount_id')
                    ->softRequired()
                    ->localizeLabel()
                    ->relationship('salesDiscount', 'name')
                    ->getOptionLabelFromRecordUsing(function (Discount $record) {
                        $currencyCode = $this->record->currency_code;

                        $rate = rateFormat($record->rate, $record->computation->value, $currencyCode);

                        $rateBadge = $this->renderBadgeOptionLabel($rate);

                        return "{$record->name} ⁓ {$rateBadge}";
                    })
                    ->saveRelationshipsUsing(null)
                    ->allowHtml()
                    ->searchable(),
                Select::make('purchase_discount_id')
                    ->softRequired()
                    ->localizeLabel()
                    ->relationship('purchaseDiscount', 'name')
                    ->getOptionLabelFromRecordUsing(function (Discount $record) {
                        $currencyCode = $this->record->currency_code;
                        $rate = rateFormat($record->rate, $record->computation->value, $currencyCode);

                        $rateBadge = $this->renderBadgeOptionLabel($rate);

                        return "{$record->name} ⁓ {$rateBadge}";
                    })
                    ->allowHtml()
                    ->saveRelationshipsUsing(null)
                    ->searchable(),
            ])->columns();
    }

    protected function getCategoriesSection(): Component
    {
        return Section::make('Categories')
            ->schema([
                Select::make('income_category_id')
                    ->softRequired()
                    ->localizeLabel()
                    ->relationship('incomeCategory', 'name')
                    ->saveRelationshipsUsing(null)
                    ->required()
                    ->preload(),
                Select::make('expense_category_id')
                    ->softRequired()
                    ->localizeLabel()
                    ->relationship('expenseCategory', 'name')
                    ->saveRelationshipsUsing(null)
                    ->searchable()
                    ->preload(),
            ])->columns();
    }

    public function renderBadgeOptionLabel(string $label, string $color = 'primary', string $size = 'sm'): string
    {
        return Blade::render('<x-filament::badge color="' . $color . '" size="' . $size . '">' . e($label) . '</x-filament::badge>');
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
}
