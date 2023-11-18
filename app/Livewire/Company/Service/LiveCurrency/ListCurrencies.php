<?php

namespace App\Livewire\Company\Service\LiveCurrency;

use App\Models\Service\CurrencyList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
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
                Tables\Columns\TextColumn::make('code')
                    ->localizeLabel()
                    ->weight(FontWeight::Medium)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->localizeLabel()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('entity')
                    ->localizeLabel()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('available')
                    ->localizeLabel()
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.company.service.live-currency.list-currencies');
    }
}
