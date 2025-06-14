<?php

namespace App\Livewire\Company\Service\LiveCurrency;

use App\Models\Service\CurrencyList;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListCurrencies extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(CurrencyList::query())
            ->columns([
                TextColumn::make('code')
                    ->localizeLabel()
                    ->weight(FontWeight::Medium)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->localizeLabel()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('entity')
                    ->localizeLabel()
                    ->sortable()
                    ->searchable(),
                IconColumn::make('available')
                    ->localizeLabel()
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.company.service.live-currency.list-currencies');
    }
}
