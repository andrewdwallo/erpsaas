<?php

namespace App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages;

use App\Enums\Accounting\DocumentType;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ListInvoices;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource;
use App\Filament\Infolists\Components\BannerEntry;
use App\Filament\Infolists\Components\DocumentPreview;
use App\Models\Accounting\RecurringInvoice;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ViewRecurringInvoice extends ViewRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit recurring invoice')
                ->outlined(),
            ActionGroup::make([
                ActionGroup::make([
                    RecurringInvoice::getManageScheduleAction(),
                    RecurringInvoice::getApproveDraftAction(),
                    RecurringInvoice::getPrintDocumentAction(),
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
                //                    ->visible(fn (RecurringInvoice $record) => $record->hasInactiveAdjustments() && $record->canBeApproved())
                //                    ->columnSpanFull()
                //                    ->description(function (RecurringInvoice $record) {
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
                //                        $output = "<p class='text-sm'>This recurring invoice contains inactive adjustments that need to be addressed before approval: {$adjustmentsList}</p>";
                //
                //                        return new HtmlString($output);
                //                    }),
                //                BannerEntry::make('scheduleIsNotSet')
                //                    ->info()
                //                    ->title('Schedule not set')
                //                    ->description('The schedule for this recurring invoice has not been set. You must set a schedule before you can approve this draft and start creating invoices.')
                //                    ->visible(fn (RecurringInvoice $record) => ! $record->hasValidStartDate())
                //                    ->columnSpanFull()
                //                    ->actions([
                //                        RecurringInvoice::getManageScheduleAction(Action::class)
                //                            ->outlined(),
                //                    ]),
                //                BannerEntry::make('readyToApprove')
                //                    ->info()
                //                    ->title('Ready to approve')
                //                    ->description('This recurring invoice is ready for approval. Review the details, and approve it when you’re ready to start generating invoices.')
                //                    ->visible(fn (RecurringInvoice $record) => $record->canBeApproved() && ! $record->hasInactiveAdjustments())
                //                    ->columnSpanFull()
                //                    ->actions([
                //                        RecurringInvoice::getApproveDraftAction(Action::class)
                //                            ->outlined(),
                //                    ]),
                Section::make('Invoice Details')
                    ->columns(4)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('client.name')
                                    ->label('Client')
                                    ->color('primary')
                                    ->weight(FontWeight::SemiBold)
                                    ->url(static fn (RecurringInvoice $record) => $record->client_id ? ClientResource::getUrl('view', ['record' => $record->client_id]) : null)
                                    ->link(),
                                TextEntry::make('total')
                                    ->label('Total')
                                    ->currency(static fn (RecurringInvoice $record) => $record->currency_code),
                                TextEntry::make('last_date')
                                    ->label('Last invoice')
                                    ->date(),
                                TextEntry::make('next_date')
                                    ->label('Next invoice')
                                    ->date(),
                                TextEntry::make('schedule')
                                    ->label('Schedule')
                                    ->state(function (RecurringInvoice $record) {
                                        return $record->getScheduleDescription();
                                    })
                                    ->helperText(function (RecurringInvoice $record) {
                                        return $record->getTimelineDescription();
                                    }),
                                TextEntry::make('occurrences_count')
                                    ->label('Created to date')
                                    ->visible(static fn (RecurringInvoice $record) => $record->occurrences_count > 0)
                                    ->color('primary')
                                    ->weight(FontWeight::SemiBold)
                                    ->suffix(fn (RecurringInvoice $record) => Str::of(' invoice')->plural($record->occurrences_count))
                                    ->url(static function (RecurringInvoice $record) {
                                        return ListInvoices::getUrl(['recurringInvoice' => $record->id]);
                                    }),
                                TextEntry::make('end_date')
                                    ->label('Ends on')
                                    ->date()
                                    ->visible(fn (RecurringInvoice $record) => $record->end_type?->isOn()),
                                TextEntry::make('approved_at')
                                    ->label('Approved at')
                                    ->date(),
                                TextEntry::make('ended_at')
                                    ->label('Ended at')
                                    ->date()
                                    ->visible(static fn (RecurringInvoice $record) => $record->ended_at),
                            ])->columnSpan(1),
                        DocumentPreview::make()
                            ->type(DocumentType::RecurringInvoice),
                    ]),
            ]);
    }
}
