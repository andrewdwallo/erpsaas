<?php

namespace App\Filament\Company\Resources\Sales\EstimateResource\Pages;

use App\Enums\Accounting\DocumentType;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Filament\Company\Resources\Sales\EstimateResource;
use App\Filament\Infolists\Components\BannerEntry;
use App\Filament\Infolists\Components\DocumentPreview;
use App\Models\Accounting\Estimate;
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

class ViewEstimate extends ViewRecord
{
    protected static string $resource = EstimateResource::class;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit estimate')
                ->outlined(),
            ActionGroup::make([
                ActionGroup::make([
                    Estimate::getApproveDraftAction(),
                    Estimate::getMarkAsSentAction(),
                    Estimate::getMarkAsAcceptedAction(),
                    Estimate::getMarkAsDeclinedAction(),
                    Estimate::getPrintDocumentAction(),
                    Estimate::getReplicateAction(),
                    Estimate::getConvertToInvoiceAction(),
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
                //                    ->visible(fn (Estimate $record) => $record->hasInactiveAdjustments() && $record->canBeApproved())
                //                    ->columnSpanFull()
                //                    ->description(function (Estimate $record) {
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
                //                        $output = "<p class='text-sm'>This estimate contains inactive adjustments that need to be addressed before approval: {$adjustmentsList}</p>";
                //
                //                        return new HtmlString($output);
                //                    }),
                Section::make('Estimate Details')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('estimate_number')
                                    ->label('Estimate #'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('client.name')
                                    ->label('Client')
                                    ->url(static fn (Estimate $record) => $record->client_id ? ClientResource::getUrl('view', ['record' => $record->client_id]) : null)
                                    ->link(),
                                TextEntry::make('expiration_date')
                                    ->label('Expiration date')
                                    ->asRelativeDay(),
                                TextEntry::make('approved_at')
                                    ->label('Approved at')
                                    ->date(),
                                TextEntry::make('last_sent_at')
                                    ->label('Last sent')
                                    ->date(),
                                TextEntry::make('accepted_at')
                                    ->label('Accepted at')
                                    ->date(),
                            ])->columnSpan(1),
                        DocumentPreview::make()
                            ->type(DocumentType::Estimate),
                    ]),
            ]);
    }
}
