<?php

namespace App\Filament\Company\Resources\Sales\InvoiceResource\Pages;

use App\Enums\Accounting\DocumentType;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Filament\Company\Resources\Sales\InvoiceResource;
use App\Filament\Company\Resources\Sales\InvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Infolists\Components\BannerEntry;
use App\Filament\Infolists\Components\DocumentPreview;
use App\Models\Accounting\Invoice;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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
            EditAction::make()
                ->label('Edit invoice')
                ->outlined(),
            ActionGroup::make([
                ActionGroup::make([
                    Invoice::getApproveDraftAction(),
                    Invoice::getMarkAsSentAction(),
                    Invoice::getPrintDocumentAction(),
                    Invoice::getReplicateAction(),
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
                //                BannerEntry::make('inactiveAdjustments')
                //                    ->label('Inactive adjustments')
                //                    ->warning()
                //                    ->icon('heroicon-o-exclamation-triangle')
                //                    ->visible(fn (Invoice $record) => $record->hasInactiveAdjustments() && $record->canBeApproved())
                //                    ->columnSpanFull()
                //                    ->description(function (Invoice $record) {
                //                        $inactiveAdjustments = collect();
                //
                //                        foreach ($record->lineItems as $lineItem) {
                //                            foreach ($lineItem->adjustments as $adjustment) {
                //                                if ($adjustment->isInactive() && $inactiveAdjustments->doesntContain($adjustment->name)) {
                //                                    $inactiveAdjustments->push($adjustment->name);
                //                                }
                //                            }
                //                        }
                //
                //                        $adjustmentsList = $inactiveAdjustments->map(static function ($name) {
                //                            return "<span class='font-medium'>{$name}</span>";
                //                        })->join(', ');
                //
                //                        $output = "<p class='text-sm'>This invoice contains inactive adjustments that need to be addressed before approval: {$adjustmentsList}</p>";
                //
                //                        return new HtmlString($output);
                //                    }),
                Section::make('Invoice Details')
                    ->columns(4)
                    ->columnSpanFull()
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
            PaymentsRelationManager::class,
        ];
    }
}
