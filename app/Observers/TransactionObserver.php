<?php

namespace App\Observers;

use App\Enums\Accounting\BillStatus;
use App\Enums\Accounting\InvoiceStatus;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Transaction;
use App\Services\TransactionService;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TransactionObserver
{
    public function __construct(
        protected TransactionService $transactionService,
    ) {}

    /**
     * Handle the Transaction "saving" event.
     */
    public function saving(Transaction $transaction): void
    {
        if ($transaction->type->isTransfer() && $transaction->description === null) {
            $transaction->description = 'Account Transfer';
        }
    }

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $this->transactionService->createJournalEntries($transaction);

        if (! $transaction->transactionable) {
            return;
        }

        $document = $transaction->transactionable;

        if ($document instanceof Invoice) {
            $this->updateInvoiceTotals($document);
        } elseif ($document instanceof Bill) {
            $this->updateBillTotals($document);
        }
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        $transaction->refresh(); // DO NOT REMOVE

        $this->transactionService->updateJournalEntries($transaction);

        if (! $transaction->transactionable) {
            return;
        }

        $document = $transaction->transactionable;

        if ($document instanceof Invoice) {
            $this->updateInvoiceTotals($document);
        } elseif ($document instanceof Bill) {
            $this->updateBillTotals($document);
        }
    }

    /**
     * Handle the Transaction "deleting" event.
     */
    public function deleting(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $this->transactionService->deleteJournalEntries($transaction);

            if (! $transaction->transactionable) {
                return;
            }

            $document = $transaction->transactionable;

            if (($document instanceof Invoice || $document instanceof Bill) && ! $document->exists) {
                return;
            }

            if ($document instanceof Invoice) {
                $this->updateInvoiceTotals($document, $transaction);
            } elseif ($document instanceof Bill) {
                $this->updateBillTotals($document, $transaction);
            }
        });
    }

    public function deleted(Transaction $transaction): void
    {
        //
    }

    protected function updateInvoiceTotals(Invoice $invoice, ?Transaction $excludedTransaction = null): void
    {
        if (! $invoice->hasPayments()) {
            return;
        }

        $invoiceCurrency = $invoice->currency_code;

        $depositTotalInInvoiceCurrencyCents = (int) $invoice->deposits()
            ->when($excludedTransaction, fn (Builder $query) => $query->whereKeyNot($excludedTransaction->getKey()))
            ->get()
            ->sum(function (Transaction $transaction) use ($invoiceCurrency) {
                $bankAccountCurrency = $transaction->bankAccount->account->currency_code;
                $amountCents = (int) $transaction->getRawOriginal('amount');

                return CurrencyConverter::convertBalance($amountCents, $bankAccountCurrency, $invoiceCurrency);
            });

        $withdrawalTotalInInvoiceCurrencyCents = (int) $invoice->withdrawals()
            ->when($excludedTransaction, fn (Builder $query) => $query->whereKeyNot($excludedTransaction->getKey()))
            ->get()
            ->sum(function (Transaction $transaction) use ($invoiceCurrency) {
                $bankAccountCurrency = $transaction->bankAccount->account->currency_code;
                $amountCents = (int) $transaction->getRawOriginal('amount');

                return CurrencyConverter::convertBalance($amountCents, $bankAccountCurrency, $invoiceCurrency);
            });

        $totalPaidInInvoiceCurrencyCents = $depositTotalInInvoiceCurrencyCents - $withdrawalTotalInInvoiceCurrencyCents;

        $invoiceTotalInInvoiceCurrencyCents = (int) $invoice->getRawOriginal('total');

        $newStatus = match (true) {
            $totalPaidInInvoiceCurrencyCents > $invoiceTotalInInvoiceCurrencyCents => InvoiceStatus::Overpaid,
            $totalPaidInInvoiceCurrencyCents === $invoiceTotalInInvoiceCurrencyCents => InvoiceStatus::Paid,
            default => InvoiceStatus::Partial,
        };

        $paidAt = $invoice->paid_at;

        if (in_array($newStatus, [InvoiceStatus::Paid, InvoiceStatus::Overpaid]) && ! $paidAt) {
            $paidAt = $invoice->deposits()
                ->latest('posted_at')
                ->value('posted_at');
        }

        $invoice->update([
            'amount_paid' => CurrencyConverter::convertCentsToFormatSimple($totalPaidInInvoiceCurrencyCents, $invoiceCurrency),
            'status' => $newStatus,
            'paid_at' => $paidAt,
        ]);
    }

    protected function updateBillTotals(Bill $bill, ?Transaction $excludedTransaction = null): void
    {
        if (! $bill->hasPayments()) {
            return;
        }

        $billCurrency = $bill->currency_code;

        $withdrawalTotalInBillCurrencyCents = (int) $bill->withdrawals()
            ->when($excludedTransaction, fn (Builder $query) => $query->whereKeyNot($excludedTransaction->getKey()))
            ->get()
            ->sum(function (Transaction $transaction) use ($billCurrency) {
                $bankAccountCurrency = $transaction->bankAccount->account->currency_code;
                $amountCents = (int) $transaction->getRawOriginal('amount');

                return CurrencyConverter::convertBalance($amountCents, $bankAccountCurrency, $billCurrency);
            });

        $totalPaidInBillCurrencyCents = $withdrawalTotalInBillCurrencyCents;

        $billTotalInBillCurrencyCents = (int) $bill->getRawOriginal('total');

        $newStatus = match (true) {
            $totalPaidInBillCurrencyCents >= $billTotalInBillCurrencyCents => BillStatus::Paid,
            default => BillStatus::Partial,
        };

        $paidAt = $bill->paid_at;

        if ($newStatus === BillStatus::Paid && ! $paidAt) {
            $paidAt = $bill->withdrawals()
                ->latest('posted_at')
                ->value('posted_at');
        }

        $bill->update([
            'amount_paid' => CurrencyConverter::convertCentsToFormatSimple($totalPaidInBillCurrencyCents, $billCurrency),
            'status' => $newStatus,
            'paid_at' => $paidAt,
        ]);
    }
}
