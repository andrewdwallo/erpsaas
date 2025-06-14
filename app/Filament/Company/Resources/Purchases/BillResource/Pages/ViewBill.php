<?php

namespace App\Filament\Company\Resources\Purchases\BillResource\Pages;

use App\Filament\Company\Resources\Purchases\BillResource;
use App\Filament\Company\Resources\Purchases\BillResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Company\Resources\Purchases\VendorResource;
use App\Models\Accounting\Bill;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;

class ViewBill extends ViewRecord
{
    protected static string $resource = BillResource::class;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit bill')
                ->outlined(),
            ActionGroup::make([
                ActionGroup::make([
                    Bill::getReplicateAction(),
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
                Section::make('Bill Details')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('bill_number')
                            ->label('Invoice #'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('vendor.name')
                            ->label('Vendor')
                            ->url(static fn (Bill $record) => $record->vendor_id ? VendorResource::getUrl('view', ['record' => $record->vendor_id]) : null)
                            ->link(),
                        TextEntry::make('total')
                            ->label('Total')
                            ->currency(static fn (Bill $record) => $record->currency_code),
                        TextEntry::make('amount_due')
                            ->label('Amount due')
                            ->currency(static fn (Bill $record) => $record->currency_code),
                        TextEntry::make('date')
                            ->label('Date')
                            ->date(),
                        TextEntry::make('due_date')
                            ->label('Due')
                            ->asRelativeDay(),
                        TextEntry::make('paid_at')
                            ->label('Paid at')
                            ->date(),
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
