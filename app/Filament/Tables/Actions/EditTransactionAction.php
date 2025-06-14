<?php

namespace App\Filament\Tables\Actions;

use App\Concerns\HasTransactionAction;
use App\Enums\Accounting\TransactionType;
use App\Models\Accounting\Transaction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditTransactionAction extends EditAction
{
    use HasTransactionAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type(static function (Transaction $record) {
            return $record->type;
        });

        $this->label(function () {
            return match ($this->getTransactionType()) {
                TransactionType::Journal => 'Edit journal entry',
                default => 'Edit transaction',
            };
        });

        $this->slideOver();

        $this->modalWidth(function (): Width {
            return match ($this->getTransactionType()) {
                TransactionType::Journal => Width::Screen,
                default => Width::ThreeExtraLarge,
            };
        });

        $this->extraModalWindowAttributes(function (): array {
            if ($this->getTransactionType() === TransactionType::Journal) {
                return ['class' => 'journal-transaction-modal'];
            }

            return [];
        });

        $this->form(function (Schema $schema) {
            return match ($this->getTransactionType()) {
                TransactionType::Transfer => $this->transferForm($schema),
                TransactionType::Journal => $this->journalTransactionForm($schema),
                default => $this->transactionForm($schema),
            };
        });

        $this->afterFormFilled(function (Transaction $record) {
            if ($this->getTransactionType() === TransactionType::Journal) {
                $debitAmounts = $record->journalEntries->sumDebits()->getAmount();
                $creditAmounts = $record->journalEntries->sumCredits()->getAmount();

                $this->setDebitAmount($debitAmounts);
                $this->setCreditAmount($creditAmounts);
            }
        });

        $this->modalSubmitAction(function (Action $action) {
            if ($this->getTransactionType() === TransactionType::Journal) {
                $action->disabled(! $this->isJournalEntryBalanced());
            }

            return $action;
        });

        $this->after(function (Transaction $transaction) {
            if ($this->getTransactionType() === TransactionType::Journal) {
                $transaction->updateAmountIfBalanced();
            }
        });
    }
}
