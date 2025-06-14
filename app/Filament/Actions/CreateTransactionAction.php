<?php

namespace App\Filament\Actions;

use App\Concerns\HasTransactionAction;
use App\Enums\Accounting\TransactionType;
use App\Models\Accounting\Transaction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class CreateTransactionAction extends CreateAction
{
    use HasTransactionAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(null);

        $this->groupedIcon(null);

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

        $this->modalHeading(function (): string {
            return match ($this->getTransactionType()) {
                TransactionType::Journal => 'Create journal entry',
                default => 'Create transaction',
            };
        });

        $this->fillForm(fn (): array => $this->getFormDefaultsForType($this->getTransactionType()));

        $this->schema(function (Schema $schema) {
            return match ($this->getTransactionType()) {
                TransactionType::Transfer => $this->transferForm($schema),
                TransactionType::Journal => $this->journalTransactionForm($schema),
                default => $this->transactionForm($schema),
            };
        });

        $this->afterFormFilled(function () {
            if ($this->getTransactionType() === TransactionType::Journal) {
                $this->resetJournalEntryAmounts();
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

        $this->mutateDataUsing(function (array $data) {
            if ($this->getTransactionType() === TransactionType::Journal) {
                $data['type'] = TransactionType::Journal;
            }

            return $data;
        });

        $this->outlined(fn () => ! $this->getGroup());
    }
}
