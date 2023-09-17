<?php

namespace App\Filament\Company\Pages\Setting;

use App\Events\CompanyDefaultUpdated;
use App\Models\Setting\CompanyDefault as CompanyDefaultModel;
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
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use function Filament\authorize;

/**
 * @property Form $form
 */
class CompanyDefault extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static ?string $navigationLabel = 'Default';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/default';

    protected ?string $heading = 'Default';

    protected static string $view = 'filament.company.pages.setting.company-default';

    public ?array $data = [];

    #[Locked]
    public ?CompanyDefaultModel $record = null;

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
                    ->label('Account')
                    ->relationship('account', 'name')
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->searchable()
                    ->preload(),
                Select::make('currency_code')
                    ->label('Currency')
                    ->relationship('currency', 'code')
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->searchable()
                    ->preload(),
            ])->columns();
    }

    protected function getModifiersSection(): Component
    {
        return Section::make('Taxes & Discounts')
            ->schema([
                Select::make('sales_tax_id')
                    ->label('Sales Tax')
                    ->relationship('salesTax', 'name')
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->searchable()
                    ->preload(),
                Select::make('purchase_tax_id')
                    ->label('Purchase Tax')
                    ->relationship('purchaseTax', 'name')
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->searchable()
                    ->preload(),
                Select::make('sales_discount_id')
                    ->label('Sales Discount')
                    ->relationship('salesDiscount', 'name')
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->searchable()
                    ->preload(),
                Select::make('purchase_discount_id')
                    ->label('Purchase Discount')
                    ->relationship('purchaseDiscount', 'name')
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->searchable()
                    ->preload(),
            ])->columns();
    }

    protected function getCategoriesSection(): Component
    {
        return Section::make('Categories')
            ->schema([
                Select::make('income_category_id')
                    ->label('Income Category')
                    ->relationship('incomeCategory', 'name')
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->searchable()
                    ->preload(),
                Select::make('expense_category_id')
                    ->label('Expense Category')
                    ->relationship('expenseCategory', 'name')
                    ->saveRelationshipsUsing(null)
                    ->selectablePlaceholder(false)
                    ->rule('required')
                    ->searchable()
                    ->preload(),
            ])->columns();
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
