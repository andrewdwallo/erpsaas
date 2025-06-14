<?php

namespace App\Filament\Forms\Components;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Utilities\Accounting\AccountCode;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateAccountSelect extends Select
{
    protected ?AccountCategory $category = null;

    protected ?AccountType $type = null;

    protected bool $includeArchived = false;

    public function category(AccountCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function type(AccountType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function includeArchived(bool $includeArchived = true): static
    {
        $this->includeArchived = $includeArchived;

        return $this;
    }

    public function getCategory(): ?AccountCategory
    {
        return $this->category;
    }

    public function getType(): ?AccountType
    {
        return $this->type;
    }

    public function includesArchived(): bool
    {
        return $this->includeArchived;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->searchable()
            ->live()
            ->createOptionForm($this->createAccountForm())
            ->createOptionAction(fn (Action $action) => $this->createAccountAction($action));

        $this->options(function () {
            $query = Account::query();

            if ($this->getCategory()) {
                $query->where('category', $this->getCategory());
            }

            if ($this->getType()) {
                $query->where('type', $this->getType());
            }

            if (! $this->includesArchived()) {
                $query->where('archived', false);
            }

            return $query->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });

        $this->createOptionUsing(static function (array $data) {
            return DB::transaction(static function () use ($data) {
                $account = Account::create([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'description' => $data['description'] ?? null,
                    'subtype_id' => $data['subtype_id'],
                ]);

                return $account->getKey();
            });
        });
    }

    protected function createAccountForm(): array
    {
        return [
            Select::make('subtype_id')
                ->label('Type')
                ->required()
                ->live()
                ->searchable()
                ->options(function () {
                    $query = AccountSubtype::query()->orderBy('name');

                    if ($this->getCategory()) {
                        $query->where('category', $this->getCategory());
                    }

                    if ($this->getType()) {
                        $query->where('type', $this->getType());

                        return $query->pluck('name', 'id')
                            ->toArray();
                    } else {
                        return $query->get()
                            ->groupBy(fn (AccountSubtype $subtype) => $subtype->type->getLabel())
                            ->map(fn (Collection $subtypes, string $type) => $subtypes->mapWithKeys(static fn (AccountSubtype $subtype) => [$subtype->id => $subtype->name]))
                            ->toArray();
                    }
                })
                ->afterStateUpdated(function (string $state, Set $set) {
                    if ($state) {
                        $accountSubtype = AccountSubtype::find($state);
                        $generatedCode = AccountCode::generate($accountSubtype);
                        $set('code', $generatedCode);
                    }
                }),

            TextInput::make('code')
                ->label('Code')
                ->required()
                ->validationAttribute('account code')
                ->unique(table: Account::class, column: 'code')
                ->validateAccountCode(static fn (Get $get) => $get('subtype_id')),

            TextInput::make('name')
                ->label('Name')
                ->required(),

            Textarea::make('description')
                ->label('Description'),
        ];
    }

    protected function createAccountAction(Action $action): Action
    {
        return $action
            ->label('Create Account')
            ->slideOver()
            ->modalWidth(Width::Large)
            ->modalHeading('Create a new account');
    }
}
