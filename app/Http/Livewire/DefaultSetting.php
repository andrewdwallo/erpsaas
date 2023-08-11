<?php

namespace App\Http\Livewire;

use App\Models\Banking\Account;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Models\Setting\DefaultSetting as Defaults;
use App\Models\Setting\Tax;
use App\Traits\HandlesDefaultSettingRecordUpdate;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * @property ComponentContainer $form
 */
class DefaultSetting extends Component implements HasForms
{
    use InteractsWithForms, HandlesDefaultSettingRecordUpdate;

    public $data;

    public Defaults $record;

    public function mount():void
    {
        $this->record = Defaults::firstOrNew();

        $this->form->fill([
            'account_id' => Defaults::getDefaultAccount(),
            'currency_code' => Defaults::getDefaultCurrency(),
            'sales_tax_id' => Defaults::getDefaultSalesTax(),
            'purchase_tax_id' => Defaults::getDefaultPurchaseTax(),
            'sales_discount_id' => Defaults::getDefaultSalesDiscount(),
            'purchase_discount_id' => Defaults::getDefaultPurchaseDiscount(),
            'income_category_id' => Defaults::getDefaultIncomeCategory(),
            'expense_category_id' => Defaults::getDefaultExpenseCategory(),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('General')
                ->schema([
                    Select::make('account_id')
                        ->label('Account')
                        ->options(Defaults::getAccounts())
                        ->searchable()
                        ->validationAttribute('Account')
                        ->nullable(),
                    Select::make('currency_code')
                        ->label('Currency')
                        ->options(Defaults::getCurrencies())
                        ->searchable()
                        ->validationAttribute('Currency')
                        ->nullable(),
                ])->columns(),
            Section::make('Taxes & Discounts')
                ->schema([
                    Select::make('sales_tax_id')
                        ->label('Sales Tax')
                        ->options(Defaults::getSalesTaxes())
                        ->searchable()
                        ->validationAttribute('Sales Tax')
                        ->nullable(),
                    Select::make('purchase_tax_id')
                        ->label('Purchase Tax')
                        ->options(Defaults::getPurchaseTaxes())
                        ->searchable()
                        ->validationAttribute('Purchase Tax')
                        ->nullable(),
                    Select::make('sales_discount_id')
                        ->label('Sales Discount')
                        ->options(Defaults::getSalesDiscounts())
                        ->searchable()
                        ->validationAttribute('Sales Discount')
                        ->nullable(),
                    Select::make('purchase_discount_id')
                        ->label('Purchase Discount')
                        ->options(Defaults::getPurchaseDiscounts())
                        ->searchable()
                        ->validationAttribute('Purchase Discount')
                        ->nullable(),
                ])->columns(),
            Section::make('Categories')
                ->schema([
                    Select::make('income_category_id')
                        ->label('Income Category')
                        ->options(Defaults::getIncomeCategories())
                        ->searchable()
                        ->validationAttribute('Income Category')
                        ->nullable(),
                    Select::make('expense_category_id')
                        ->label('Expense Category')
                        ->options(Defaults::getExpenseCategories())
                        ->searchable()
                        ->validationAttribute('Expense Category')
                        ->nullable(),
                ])->columns(),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->handleRecordUpdate($this->getFormModel(), $data);

        $this->getSavedNotification()?->send();
    }

    protected function getFormModel(): Model
    {
        return $this->record;
    }

    protected function getRelatedEntities(): array
    {
        return [
            'account_id' => [Account::class, 'id'],
            'currency_code' => [Currency::class, 'code'],
            'sales_tax_id' => [Tax::class, 'id', 'sales'],
            'purchase_tax_id' => [Tax::class, 'id', 'purchase'],
            'sales_discount_id' => [Tax::class, 'id', 'sales'],
            'purchase_discount_id' => [Tax::class, 'id', 'purchase'],
            'income_category_id' => [Category::class, 'id', 'income'],
            'expense_category_id' => [Category::class, 'id', 'expense'],
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        $title = $this->getSavedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($title);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('filament::resources/pages/edit-record.messages.saved');
    }

    public function render(): View
    {
        return view('livewire.default-setting');
    }
}
