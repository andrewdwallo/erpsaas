<?php

namespace App\Concerns;

use App\Enums\Accounting\JournalEntryType;
use App\Utilities\Currency\CurrencyAccessor;
use Filament\Actions\Action;

trait HasJournalEntryActions
{
    public int $debitAmount = 0;

    public int $creditAmount = 0;

    private function formatMoney(int $amount): string
    {
        return money($amount, CurrencyAccessor::getDefaultCurrency())->format();
    }

    /**
     * Expects formatted simple amount: e.g. 1,000.00 or 1.000,00
     *
     * Sets debit amount in cents as integer: e.g. 100000
     */
    public function setDebitAmount(int $amount): void
    {
        $this->debitAmount = $amount;
    }

    /**
     * Expects formatted simple amount: e.g. 1,000.00 or 1.000,00
     *
     * Sets credit amount in cents as integer: e.g. 100000
     */
    public function setCreditAmount(int $amount): void
    {
        $this->creditAmount = $amount;
    }

    /**
     * Returns debit amount in cents as integer: e.g. 100000
     */
    public function getDebitAmount(): int
    {
        return $this->debitAmount;
    }

    /**
     * Returns credit amount in cents as integer: e.g. 100000
     */
    public function getCreditAmount(): int
    {
        return $this->creditAmount;
    }

    /**
     * Expects debit amount in cents as string integer: e.g. 100000
     *
     * Returns formatted amount: e.g. $1,000.00 or €1.000,00
     */
    public function getFormattedDebitAmount(): string
    {
        return $this->formatMoney($this->getDebitAmount());
    }

    /**
     * Expects credit amount in cents as integer: e.g. 100000
     *
     * Returns formatted amount: e.g. $1,000.00 or €1.000,00
     */
    public function getFormattedCreditAmount(): string
    {
        return $this->formatMoney($this->getCreditAmount());
    }

    /**
     * Returns balance difference in cents as integer: e.g. 100000
     */
    public function getBalanceDifference(): int
    {
        return $this->getDebitAmount() - $this->getCreditAmount();
    }

    /**
     * Returns formatted balance difference: e.g. $1,000.00 or €1.000,00
     */
    public function getFormattedBalanceDifference(): string
    {
        $absoluteDifference = abs($this->getBalanceDifference());

        return $this->formatMoney($absoluteDifference);
    }

    /**
     * Returns boolean indicating whether the journal entry is balanced
     * using the debit and credit integer amounts
     */
    public function isJournalEntryBalanced(): bool
    {
        return $this->getDebitAmount() === $this->getCreditAmount();
    }

    /**
     * Resets debit and credit amounts to '0.00'
     */
    public function resetJournalEntryAmounts(): void
    {
        $this->debitAmount = 0;
        $this->creditAmount = 0;
    }

    public function adjustJournalEntryAmountsForTypeChange(JournalEntryType $newType, JournalEntryType $oldType, ?string $amount): void
    {
        if ($newType === $oldType) {
            return;
        }

        $entries = $this instanceof Action
            ? ($this->getLivewire()->mountedTableActionsData[0]['journalEntries'] ?? [])
            : ($this->getLivewire()->mountedActionsData[0]['journalEntries'] ?? []);

        // Reset the totals
        $this->debitAmount = 0;
        $this->creditAmount = 0;

        // Recalculate totals from all entries
        foreach ($entries as $entry) {
            if (empty($entry['type']) || empty($entry['amount'])) {
                continue;
            }

            $entryType = JournalEntryType::parse($entry['type']);
            $entryAmount = $this->ensureCompleteDecimal($entry['amount']);
            $formattedAmount = $this->convertAmountToCents($entryAmount);

            if ($entryType->isDebit()) {
                $this->debitAmount += $formattedAmount;
            } else {
                $this->creditAmount += $formattedAmount;
            }
        }
    }

    /**
     * Expects the journal entry type,
     * the new amount and the old amount as formatted simple amounts: e.g. 1,000.00 or 1.000,00
     * It can expect the amounts as partial amounts: e.g. 1,000. or 1.000, (this needs to be handled by this method)
     */
    public function updateJournalEntryAmount(JournalEntryType $journalEntryType, ?string $newAmount, ?string $oldAmount): void
    {
        if ($newAmount === $oldAmount) {
            return;
        }

        $entries = $this instanceof Action
            ? ($this->getLivewire()->mountedTableActionsData[0]['journalEntries'] ?? [])
            : ($this->getLivewire()->mountedActionsData[0]['journalEntries'] ?? []);

        // Reset the totals
        $this->debitAmount = 0;
        $this->creditAmount = 0;

        // Recalculate totals from all entries
        foreach ($entries as $entry) {
            if (empty($entry['type']) || empty($entry['amount'])) {
                continue;
            }

            $entryType = JournalEntryType::parse($entry['type']);
            $entryAmount = $this->ensureCompleteDecimal($entry['amount']);
            $formattedAmount = $this->convertAmountToCents($entryAmount);

            if ($entryType->isDebit()) {
                $this->debitAmount += $formattedAmount;
            } else {
                $this->creditAmount += $formattedAmount;
            }
        }
    }

    private function ensureCompleteDecimal(?string $amount): string
    {
        if ($amount === null) {
            return '0';
        }

        $currency = currency(CurrencyAccessor::getDefaultCurrency());
        $decimal = $currency->getDecimalMark();

        if (substr($amount, -1) === $decimal) {
            return '0';
        }

        return $amount;
    }

    /**
     * Expects formatted simple amount: e.g. 1,000.00 or 1.000,00
     *
     * Returns sanitized amount in cents as integer: e.g. 100000
     */
    protected function convertAmountToCents(string $amount): int
    {
        return money($amount, CurrencyAccessor::getDefaultCurrency(), true)->getAmount();
    }
}
