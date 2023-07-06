<?php

namespace App\Http\Livewire;

use App\Models\Banking\Account;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;
use App\Models\Setting\DefaultSetting as Defaults;
use App\Models\Setting\Tax;
use App\Traits\HandlesRecordCreation;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * @property ComponentContainer $form
 */
class DefaultSetting extends Component implements HasForms
{
    use InteractsWithForms, HandlesRecordCreation;

    public Defaults $defaultSetting;

    public $data;

    public $record;

    public function mount():void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('General')
                ->schema([
                    Select::make('account_id')
                        ->label('Account')
                        ->options(Defaults::getAccounts())
                        ->default(Defaults::getDefaultAccount())
                        ->searchable()
                        ->validationAttribute('Account')
                        ->required(),
                    Select::make('currency_code')
                        ->label('Currency')
                        ->options(Defaults::getCurrencies())
                        ->default(Defaults::getDefaultCurrency())
                        ->searchable()
                        ->validationAttribute('Currency')
                        ->required(),
                ])->columns(),
            Section::make('Taxes')
                ->schema([
                    Select::make('sales_tax_id')
                        ->label('Sales Tax')
                        ->options(Defaults::getSalesTaxes())
                        ->default(Defaults::getDefaultSalesTax())
                        ->searchable()
                        ->validationAttribute('Sales Tax')
                        ->required(),
                    Select::make('purchase_tax_id')
                        ->label('Purchase Tax')
                        ->options(Defaults::getPurchaseTaxes())
                        ->default(Defaults::getDefaultPurchaseTax())
                        ->searchable()
                        ->validationAttribute('Purchase Tax')
                        ->required(),
                ])->columns(),
            Section::make('Categories')
                ->schema([
                    Select::make('income_category_id')
                        ->label('Income Category')
                        ->options(Defaults::getIncomeCategories())
                        ->default(Defaults::getDefaultIncomeCategory())
                        ->searchable()
                        ->validationAttribute('Income Category')
                        ->required(),
                    Select::make('expense_category_id')
                        ->label('Expense Category')
                        ->options(Defaults::getExpenseCategories())
                        ->default(Defaults::getDefaultExpenseCategory())
                        ->searchable()
                        ->validationAttribute('Expense Category')
                        ->required(),
                ])->columns(),
        ];
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $data = $this->mutateFormDataBeforeCreate($data);

        $this->record = $this->handleRecordCreation($data);

        $this->form->model($this->record)->saveRelationships();

        $this->getSavedNotification()?->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->currentCompany->id;
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function getRelatedEntities(): array
    {
        return [
            'account_id' => [Account::class, 'id'],
            'currency_code' => [Currency::class, 'code'],
            'sales_tax_id' => [Tax::class, 'id', 'sales'],
            'purchase_tax_id' => [Tax::class, 'id', 'purchase'],
            'income_category_id' => [Category::class, 'id', 'income'],
            'expense_category_id' => [Category::class, 'id', 'expense'],
        ];
    }

    protected function getFormModel(): string
    {
        return Defaults::class;
    }

    protected function getSavedNotification():?Notification
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
