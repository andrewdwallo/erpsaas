<?php

namespace App\Filament\Company\Pages\Accounting;

use App\Enums\Accounting\AccountCategory;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Utilities\Accounting\AccountCode;
use App\Utilities\Currency\CurrencyAccessor;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class AccountChart extends Page
{
    protected static ?string $title = 'Chart of Accounts';

    protected static ?string $slug = 'accounting/chart';

    protected static string $view = 'filament.company.pages.accounting.chart';

    #[Url]
    public ?string $activeTab = AccountCategory::Asset->value;

    protected function configureAction(Action $action): void
    {
        $action
            ->modal()
            ->modalWidth(MaxWidth::TwoExtraLarge);
    }

    #[Computed]
    public function categories(): Collection
    {
        return AccountSubtype::withCount('accounts')
            ->with('accounts')
            ->with('accounts.adjustment')
            ->get()
            ->groupBy('category');
    }

    public function editChartAction(): Action
    {
        return EditAction::make()
            ->iconButton()
            ->name('editChart')
            ->label('Edit account')
            ->modalHeading('Edit Account')
            ->icon('heroicon-m-pencil-square')
            ->record(fn (array $arguments) => Account::find($arguments['chart']))
            ->form(fn (Form $form) => $this->getChartForm($form)->operation('edit'));
    }

    public function createChartAction(): Action
    {
        return CreateAction::make()
            ->link()
            ->name('createChart')
            ->model(Account::class)
            ->label('Add a new account')
            ->icon('heroicon-o-plus-circle')
            ->form(fn (Form $form) => $this->getChartForm($form)->operation('create'))
            ->fillForm(fn (array $arguments): array => $this->getChartFormDefaults($arguments['subtype']));
    }

    private function getChartFormDefaults(int $subtypeId): array
    {
        $accountSubtype = AccountSubtype::find($subtypeId);
        $generatedCode = AccountCode::generate($accountSubtype);

        return [
            'subtype_id' => $subtypeId,
            'code' => $generatedCode,
        ];
    }

    private function getChartForm(Form $form, bool $useActiveTab = true): Form
    {
        return $form
            ->schema([
                $this->getTypeFormComponent($useActiveTab),
                $this->getCodeFormComponent(),
                $this->getNameFormComponent(),
                $this->getCurrencyFormComponent(),
                $this->getDescriptionFormComponent(),
                $this->getArchiveFormComponent(),
            ]);
    }

    protected function getTypeFormComponent(bool $useActiveTab = true): Component
    {
        return Select::make('subtype_id')
            ->label('Type')
            ->required()
            ->live()
            ->disabled(static function (string $operation): bool {
                return $operation === 'edit';
            })
            ->options($this->getChartSubtypeOptions($useActiveTab))
            ->afterStateUpdated(static function (?string $state, Set $set): void {
                if ($state) {
                    $accountSubtype = AccountSubtype::find($state);
                    $generatedCode = AccountCode::generate($accountSubtype);
                    $set('code', $generatedCode);
                }
            });
    }

    protected function getCodeFormComponent(): Component
    {
        return TextInput::make('code')
            ->label('Code')
            ->required()
            ->validationAttribute('account code')
            ->unique(table: Account::class, column: 'code', ignoreRecord: true)
            ->validateAccountCode(static fn (Get $get) => $get('subtype_id'));
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Name')
            ->required();
    }

    protected function getCurrencyFormComponent()
    {
        return Select::make('currency_code')
            ->localizeLabel('Currency')
            ->relationship('currency', 'name')
            ->default(CurrencyAccessor::getDefaultCurrency())
            ->preload()
            ->searchable()
            ->disabled(static function (string $operation): bool {
                return $operation === 'edit';
            })
            ->visible(function (Get $get): bool {
                return filled($get('subtype_id')) && AccountSubtype::find($get('subtype_id'))->multi_currency;
            })
            ->live();
    }

    protected function getDescriptionFormComponent(): Component
    {
        return Textarea::make('description')
            ->label('Description')
            ->autosize();
    }

    protected function getArchiveFormComponent(): Component
    {
        return Checkbox::make('archived')
            ->label('Archive account')
            ->helperText('Archived accounts will not be available for selection in transactions.')
            ->hidden(static function (string $operation): bool {
                return $operation === 'create';
            });
    }

    private function getChartSubtypeOptions($useActiveTab = true): array
    {
        $subtypes = $useActiveTab ?
            AccountSubtype::where('category', $this->activeTab)->get() :
            AccountSubtype::all();

        return $subtypes->groupBy(fn (AccountSubtype $subtype) => $subtype->type->getLabel())
            ->map(fn (Collection $subtypes, string $type) => $subtypes->mapWithKeys(static fn (AccountSubtype $subtype) => [$subtype->id => $subtype->name]))
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->button()
                ->label('Add new account')
                ->model(Account::class)
                ->form(fn (Form $form) => $this->getChartForm($form, false)->operation('create')),
        ];
    }

    public function getCategoryLabel($categoryValue): string
    {
        return AccountCategory::from($categoryValue)->getPluralLabel();
    }
}
