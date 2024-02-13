<?php

namespace App\Filament\Company\Pages\Accounting;

use App\Models\Accounting\Account as ChartModel;
use App\Enums\Accounting\AccountCategory;
use App\Models\Accounting\AccountSubtype;
use App\Utilities\Accounting\AccountCode;
use App\Utilities\Currency\CurrencyAccessor;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
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
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $title = 'Chart of Accounts';

    protected static ?string $navigationGroup = 'Accounting';

    protected static ?string $slug = 'accounting/chart';

    protected static string $view = 'filament.company.pages.accounting.chart';

    public ?ChartModel $chart = null;

    #[Url]
    public ?string $activeTab = null;

    public function mount(): void
    {
        $this->activeTab = $this->activeTab ?? AccountCategory::Asset->value;
    }

    protected function configureAction(Action $action): void
    {
        $action
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->stickyModalHeader()
            ->stickyModalFooter();
    }

    #[Computed]
    public function categories(): Collection
    {
        return AccountSubtype::withCount('accounts')
            ->get()
            ->groupBy('category');
    }

    public function editChartAction(): Action
    {
        return EditAction::make()
            ->iconButton()
            ->record($this->chart)
            ->name('editChart')
            ->label('Edit account')
            ->modalHeading('Edit Account')
            ->icon('heroicon-m-pencil-square')
            ->mountUsing(function (array $arguments, Form $form) {
                $chartId = $arguments['chart'];
                $this->chart = ChartModel::find($chartId);

                $form
                    ->fill($this->chart->toArray())
                    ->operation('edit')
                    ->model($this->chart); // This is needed for form relationships to work (maybe a bug in Filament regarding passed arguments related to timing)
            })
            ->form($this->getChartForm());
    }

    public function createChartAction(): Action
    {
        return CreateAction::make()
            ->link()
            ->name('createChart')
            ->form($this->getChartForm())
            ->model(ChartModel::class)
            ->label('Add a new account')
            ->icon('heroicon-o-plus-circle')
            ->mountUsing(function (array $arguments, Form $form) {
                $subtypeId = $arguments['subtype'];
                $this->chart = new ChartModel([
                    'subtype_id' => $subtypeId,
                ]);

                if ($subtypeId) {
                    $companyId = auth()->user()->currentCompany->id;
                    $generatedCode = AccountCode::generate($companyId, $subtypeId);
                    $this->chart->code = $generatedCode;
                }

                $form->fill($this->chart->toArray())
                    ->operation('create');
            });
    }

    private function getChartForm(bool $useActiveTab = true): array
    {
        return [
            Select::make('subtype_id')
                ->label('Type')
                ->required()
                ->live()
                ->disabled(static fn (string $operation, ?ChartModel $record) => $operation === 'edit' && $record?->default === true)
                ->options($this->getChartSubtypeOptions($useActiveTab))
                ->afterStateUpdated(static function (?string $state, Set $set): void {
                   if ($state) {
                       $companyId = auth()->user()->currentCompany->id;
                       $generatedCode = AccountCode::generate($companyId, $state);
                       $set('code', $generatedCode);
                   }
                }),
            TextInput::make('code')
                ->label('Code')
                ->required()
                ->validationAttribute('account code')
                ->unique(table: ChartModel::class, column: 'code', ignoreRecord: true)
                ->validateAccountCode(static fn (Get $get) => $get('subtype_id')),
            TextInput::make('name')
                ->label('Name')
                ->required(),
            Select::make('currency_code')
                ->localizeLabel('Currency')
                ->relationship('currency', 'name')
                ->default(CurrencyAccessor::getDefaultCurrency())
                ->preload()
                ->searchable()
                ->visible(function (Get $get): bool {
                    return filled($get('subtype_id')) && AccountSubtype::find($get('subtype_id'))->multi_currency;
                })
                ->live(),
            Textarea::make('description')
                ->label('Description')
                ->autosize(),
        ];
    }

    private function getChartSubtypeOptions($useActiveTab = true): array
    {
        $subtypes = $useActiveTab ?
            AccountSubtype::where('category', $this->activeTab)->get() :
            AccountSubtype::all();

        return $subtypes->groupBy(fn(AccountSubtype $subtype) => $subtype->type->getLabel())
            ->map(fn(Collection $subtypes, string $type) => $subtypes->mapWithKeys(static fn (AccountSubtype $subtype) => [$subtype->id => $subtype->name]))
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->button()
                ->label('Add New Account')
                ->model(ChartModel::class)
                ->form($this->getChartForm(false)),
        ];
    }

    public function getCategoryLabel($categoryValue): string
    {
        return AccountCategory::from($categoryValue)->getLabel();
    }
}
