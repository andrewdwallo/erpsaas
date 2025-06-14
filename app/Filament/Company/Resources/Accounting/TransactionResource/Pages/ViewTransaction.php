<?php

namespace App\Filament\Company\Resources\Accounting\TransactionResource\Pages;

use App\Filament\Actions\EditTransactionAction;
use App\Filament\Company\Resources\Accounting\TransactionResource;
use App\Filament\Company\Resources\Accounting\TransactionResource\RelationManagers\JournalEntriesRelationManager;
use App\Filament\Company\Resources\Purchases\BillResource\Pages\ViewBill;
use App\Filament\Company\Resources\Purchases\VendorResource;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ViewInvoice;
use App\Filament\Infolists\Components\BannerEntry;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Transaction;
use App\Models\Common\Client;
use App\Models\Common\Vendor;
use App\Utilities\Currency\CurrencyAccessor;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;

use function Filament\Support\get_model_label;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    protected function getHeaderActions(): array
    {
        return [
            EditTransactionAction::make()
                ->outlined()
                ->after(fn () => $this->dispatch('refresh')),
            ViewAction::make('viewAssociatedDocument')
                ->outlined()
                ->icon('heroicon-o-document-text')
                ->hidden(static fn (Transaction $record): bool => ! $record->transactionable_id)
                ->label(static function (Transaction $record) {
                    if (! $record->transactionable_type) {
                        return 'View document';
                    }

                    return 'View ' . get_model_label($record->transactionable_type);
                })
                ->url(static function (Transaction $record) {
                    return match ($record->transactionable_type) {
                        Bill::class => ViewBill::getUrl(['record' => $record->transactionable_id]),
                        Invoice::class => ViewInvoice::getUrl(['record' => $record->transactionable_id]),
                        default => null,
                    };
                }),
            ActionGroup::make([
                ActionGroup::make([
                    Action::make('markAsReviewed')
                        ->label(static fn (Transaction $record) => $record->reviewed ? 'Mark as unreviewed' : 'Mark as reviewed')
                        ->icon(static fn (Transaction $record) => $record->reviewed ? 'heroicon-s-check-circle' : 'heroicon-o-check-circle')
                        ->hidden(fn (Transaction $record): bool => $record->isUncategorized())
                        ->action(fn (Transaction $record) => $record->update(['reviewed' => ! $record->reviewed])),
                    ReplicateAction::make()
                        ->excludeAttributes(['created_by', 'updated_by', 'created_at', 'updated_at'])
                        ->modal(false)
                        ->beforeReplicaSaved(static function (Transaction $replica) {
                            $replica->description = '(Copy of) ' . $replica->description;
                        })
                        ->hidden(static fn (Transaction $transaction) => $transaction->transactionable_id)
                        ->after(static function (Transaction $original, Transaction $replica) {
                            $original->journalEntries->each(function (JournalEntry $entry) use ($replica) {
                                $entry->replicate([
                                    'transaction_id',
                                ])->fill([
                                    'transaction_id' => $replica->id,
                                ])->save();
                            });
                        }),
                ])->dropdown(false),
                DeleteAction::make(),
            ])
                ->label('Actions')
                ->button()
                ->outlined()
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-m-chevron-down')
                ->iconPosition(IconPosition::After),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //                BannerEntry::make('transactionUncategorized')
                //                    ->warning()
                //                    ->title('Transaction uncategorized')
                //                    ->description('You must categorize this transaction before you can mark it as reviewed.')
                //                    ->visible(fn (Transaction $record) => $record->isUncategorized())
                //                    ->columnSpanFull(),
                Section::make('Transaction Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('posted_at')
                            ->label('Date')
                            ->date(),
                        TextEntry::make('type')
                            ->badge(),
                        IconEntry::make('is_payment')
                            ->label('Payment')
                            ->boolean(),
                        TextEntry::make('description')
                            ->label('Description'),
                        TextEntry::make('bankAccount.account.name')
                            ->label('Account')
                            ->hidden(static fn (Transaction $record): bool => ! $record->bankAccount),
                        TextEntry::make('payeeable.name')
                            ->label('Payee')
                            ->hidden(static fn (Transaction $record): bool => ! $record->payeeable_type)
                            ->url(static function (Transaction $record): ?string {
                                if (! $record->payeeable_type || ! $record->payeeable_id) {
                                    return null;
                                }

                                return match ($record->payeeable_type) {
                                    Vendor::class => VendorResource::getUrl('view', ['record' => $record->payeeable_id]),
                                    Client::class => ClientResource::getUrl('view', ['record' => $record->payeeable_id]),
                                    default => null,
                                };
                            })
                            ->link(),
                        TextEntry::make('account.name')
                            ->label('Category')
                            ->hidden(static fn (Transaction $record): bool => ! $record->account),
                        TextEntry::make('amount')
                            ->label('Amount')
                            ->currency(static fn (Transaction $record) => $record->bankAccount?->account->currency_code ?? CurrencyAccessor::getDefaultCurrency()),
                        TextEntry::make('reviewed')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(static fn (bool $state): string => $state ? 'Reviewed' : 'Not Reviewed')
                            ->color(static fn (bool $state): string => $state ? 'success' : 'warning'),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->columnSpan(2)
                            ->visible(static fn (Transaction $record): bool => filled($record->notes)),
                    ]),
            ]);
    }

    protected function getAllRelationManagers(): array
    {
        return [
            JournalEntriesRelationManager::class,
        ];
    }
}
