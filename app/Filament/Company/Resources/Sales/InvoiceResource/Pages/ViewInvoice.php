<?php

namespace App\Filament\Company\Resources\Sales\InvoiceResource\Pages;

use App\Enums\Accounting\DocumentType;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Filament\Company\Resources\Sales\InvoiceResource;
use App\Filament\Infolists\Components\BannerEntry;
use App\Filament\Infolists\Components\DocumentPreview;
use App\Models\Accounting\Invoice;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\HtmlString;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit invoice')
                ->outlined(),
            Actions\ActionGroup::make([
                Actions\ActionGroup::make([
                    Invoice::getApproveDraftAction(),
                    Invoice::getMarkAsSentAction(),
                    Invoice::getPrintDocumentAction(),
                    $this->getQrPaymentSlipAction(),
                    Invoice::getReplicateAction(),
                ])->dropdown(false),
                Actions\DeleteAction::make(),
            ])
                ->label('Actions')
                ->button()
                ->outlined()
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-m-chevron-down')
                ->iconPosition(IconPosition::After),
        ];
    }

    protected function getQrPaymentSlipAction(): Actions\Action
    {
        return Actions\Action::make('downloadQrPaymentSlip')
            ->label('QR Payment Slip')
            ->icon('heroicon-m-qr-code')
            ->visible(function (Invoice $record) {
                $profile = $record->company->profile;
                return $profile->qr_bill_enabled && 
                       !empty($profile->qr_bill_iban) && 
                       in_array(strtoupper($record->currency_code ?? ''), ['CHF', 'EUR']) &&
                       $record->amountDue() > 0;
            })
            ->url(fn (Invoice $record) => route('documents.qr-payment-slip', ['id' => $record->id]))
            ->openUrlInNewTab();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                BannerEntry::make('inactiveAdjustments')
                    ->label('Inactive adjustments')
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn (Invoice $record) => $record->hasInactiveAdjustments() && $record->canBeApproved())
                    ->columnSpanFull()
                    ->description(function (Invoice $record) {
                        $inactiveAdjustments = collect();

                        foreach ($record->lineItems as $lineItem) {
                            foreach ($lineItem->adjustments as $adjustment) {
                                if ($adjustment->isInactive() && $inactiveAdjustments->doesntContain($adjustment->name)) {
                                    $inactiveAdjustments->push($adjustment->name);
                                }
                            }
                        }

                        $adjustmentsList = $inactiveAdjustments->map(static function ($name) {
                            return "<span class='font-medium'>{$name}</span>";
                        })->join(', ');

                        $output = "<p class='text-sm'>This invoice contains inactive adjustments that need to be addressed before approval: {$adjustmentsList}</p>";

                        return new HtmlString($output);
                    }),
                Section::make('Invoice Details')
                    ->columns(4)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('invoice_number')
                                    ->label('Invoice #'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('client.name')
                                    ->label('Client')
                                    ->url(static fn (Invoice $record) => $record->client_id ? ClientResource::getUrl('view', ['record' => $record->client_id]) : null)
                                    ->link(),
                                TextEntry::make('amount_due')
                                    ->label('Amount due')
                                    ->currency(static fn (Invoice $record) => $record->currency_code),
                                TextEntry::make('due_date')
                                    ->label('Due')
                                    ->asRelativeDay(),
                                TextEntry::make('approved_at')
                                    ->label('Approved at')
                                    ->date(),
                                TextEntry::make('last_sent_at')
                                    ->label('Last sent')
                                    ->date(),
                                TextEntry::make('paid_at')
                                    ->label('Paid at')
                                    ->date(),
                            ])->columnSpan(1),
                        DocumentPreview::make()
                            ->type(DocumentType::Invoice),
                    ]),
            ]);
    }

    protected function getAllRelationManagers(): array
    {
        return [
            InvoiceResource\RelationManagers\PaymentsRelationManager::class,
        ];
    }
}
