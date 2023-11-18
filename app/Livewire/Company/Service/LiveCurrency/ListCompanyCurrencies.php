<?php

namespace App\Livewire\Company\Service\LiveCurrency;

use App\Models\Setting\Currency;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class ListCompanyCurrencies extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $tableModelLabel = 'Currency';

    public function getTableModelLabel(): ?string
    {
        return static::$tableModelLabel;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Currency::query())
            ->modelLabel($this->getTableModelLabel())
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->localizeLabel()
                    ->weight(FontWeight::Medium)
                    ->icon(static fn (Currency $record) => $record->isEnabled() ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(function (Currency $record) {
                        $tooltipMessage = translate('Default :Record', [
                            'Record' => $this->getTableModelLabel(),
                        ]);

                        return $record->isEnabled() ? $tooltipMessage : null;
                    })
                    ->iconPosition(IconPosition::After)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->localizeLabel()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('rate')
                    ->localizeLabel()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('live_rate')
                    ->localizeLabel()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('update_rate')
                    ->label('Update Rate')
                    ->icon('heroicon-o-arrow-path')
                    ->hidden(static fn (Currency $record): bool => $record->isEnabled() || ($record->rate === $record->live_rate))
                    ->requiresConfirmation()
                    ->action(static function (Currency $record): void {
                        if (($record->rate !== $record->live_rate) && $record->isDisabled()) {
                            $record->update([
                                'rate' => $record->live_rate,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Exchange Rate Updated')
                                ->body(__('The exchange rate for :currency has been updated to reflect the current market rate.', [
                                    'currency' => $record->name,
                                ]))
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('update_rate')
                    ->label('Update Rate')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $updatedCurrencies = [];

                        $records->each(function (Currency $record) use (&$updatedCurrencies): void {
                            if (($record->rate !== $record->live_rate) && $record->isDisabled()) {
                                $record->update([
                                    'rate' => $record->live_rate,
                                ]);

                                $updatedCurrencies[] = $record->name;
                            }
                        });

                        if (filled($updatedCurrencies)) {
                            $currencyList = implode('<br>', array_map(static function ($currency) {
                                return '&bull; ' . $currency;
                            }, $updatedCurrencies));

                            $message = __('The exchange rate for the following currencies has been updated to reflect the current market rate:') . '<br><br>';

                            $message .= $currencyList;

                            Notification::make()
                                ->success()
                                ->title('Exchange Rates Updated')
                                ->body($message)
                                ->send();
                        }
                    }),
            ])
            ->checkIfRecordIsSelectableUsing(static function (Currency $record): bool {
                return ($record->rate !== $record->live_rate) && $record->isDisabled();
            });
    }

    public function render(): View
    {
        return view('livewire.company.service.live-currency.list-company-currencies');
    }
}
