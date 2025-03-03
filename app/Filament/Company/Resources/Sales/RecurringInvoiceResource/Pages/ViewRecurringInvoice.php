<?php

namespace App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages;

use App\Enums\Accounting\DocumentType;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ListInvoices;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource;
use App\Filament\Infolists\Components\BannerEntry;
use App\Filament\Infolists\Components\DocumentPreview;
use App\Models\Accounting\RecurringInvoice;
use Filament\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Str;

class ViewRecurringInvoice extends ViewRecord
{
    protected static string $resource = RecurringInvoiceResource::class;

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::SixExtraLarge;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit recurring invoice')
                ->outlined(),
            Actions\ActionGroup::make([
                Actions\ActionGroup::make([
                    RecurringInvoice::getManageScheduleAction(),
                    RecurringInvoice::getApproveDraftAction(),
                ])->dropdown(false),
                Actions\DeleteAction::make(),
            ])
                ->label('Actions')
                ->button()
                ->outlined()
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-c-chevron-down')
                ->iconSize(IconSize::Small)
                ->iconPosition(IconPosition::After),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                BannerEntry::make('scheduleIsNotSet')
                    ->info()
                    ->title('Schedule not set')
                    ->description('The schedule for this recurring invoice has not been set. You must set a schedule before you can approve this draft and start creating invoices.')
                    ->visible(fn (RecurringInvoice $record) => ! $record->hasValidStartDate())
                    ->columnSpanFull()
                    ->actions([
                        RecurringInvoice::getManageScheduleAction(Action::class)
                            ->outlined(),
                    ]),
                BannerEntry::make('readyToApprove')
                    ->info()
                    ->title('Ready to Approve')
                    ->description('This recurring invoice is ready for approval. Review the details, and approve it when you’re ready to start generating invoices.')
                    ->visible(fn (RecurringInvoice $record) => $record->canBeApproved())
                    ->columnSpanFull()
                    ->actions([
                        RecurringInvoice::getApproveDraftAction(Action::class)
                            ->outlined(),
                    ]),
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
                                    ->url(static fn (RecurringInvoice $record) => ClientResource::getUrl('edit', ['record' => $record->client_id])),
                                TextEntry::make('last_date')
                                    ->label('Last invoice')
                                    ->date()
                                    ->placeholder('Not Created'),
                                TextEntry::make('next_date')
                                    ->label('Next invoice')
                                    ->placeholder('Not Scheduled')
                                    ->date(),
                                TextEntry::make('schedule')
                                    ->label('Schedule')
                                    ->getStateUsing(function (RecurringInvoice $record) {
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
                                    ->placeholder('Not Approved')
                                    ->date(),
                                TextEntry::make('ended_at')
                                    ->label('Ended at')
                                    ->date()
                                    ->visible(fn (RecurringInvoice $record) => $record->ended_at),
                                TextEntry::make('total')
                                    ->label('Invoice amount')
                                    ->currency(static fn (RecurringInvoice $record) => $record->currency_code),
                            ])->columnSpan(1),
                        DocumentPreview::make()
                            ->type(DocumentType::RecurringInvoice),
                    ]),
            ]);
    }
}
