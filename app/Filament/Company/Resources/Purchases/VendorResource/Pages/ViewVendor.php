<?php

namespace App\Filament\Company\Resources\Purchases\VendorResource\Pages;

use App\Filament\Company\Resources\Purchases\BillResource\Pages\CreateBill;
use App\Filament\Company\Resources\Purchases\VendorResource;
use App\Filament\Company\Resources\Purchases\VendorResource\RelationManagers\BillsRelationManager;
use App\Filament\Company\Resources\Purchases\VendorResource\Widgets\BillOverview;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    protected function getAllRelationManagers(): array
    {
        return [
            BillsRelationManager::class,
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit vendor')
                ->outlined(),
            ActionGroup::make([
                ActionGroup::make([
                    Action::make('newBill')
                        ->label('New bill')
                        ->icon('heroicon-m-document-plus')
                        ->url(CreateBill::getUrl(['vendor' => $this->record->getKey()])),
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

    protected function getHeaderWidgets(): array
    {
        return [
            BillOverview::class,
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('General')
                    ->columns()
                    ->schema([
                        TextEntry::make('contact.full_name')
                            ->label('Contact'),
                        TextEntry::make('contact.email')
                            ->label('Email'),
                        TextEntry::make('contact.first_available_phone')
                            ->label('Primary phone'),
                        TextEntry::make('website')
                            ->label('Website')
                            ->url(static fn ($state) => $state, true)
                            ->link(),
                    ]),
                Section::make('Additional Details')
                    ->columns()
                    ->schema([
                        TextEntry::make('address.address_string')
                            ->label('Billing address')
                            ->listWithLineBreaks(),
                        TextEntry::make('notes'),
                    ]),
            ]);
    }
}
