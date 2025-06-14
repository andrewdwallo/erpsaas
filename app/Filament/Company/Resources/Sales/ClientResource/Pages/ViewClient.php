<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\Pages;

use App\Filament\Company\Resources\Sales\ClientResource;
use App\Filament\Company\Resources\Sales\ClientResource\RelationManagers\EstimatesRelationManager;
use App\Filament\Company\Resources\Sales\ClientResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Company\Resources\Sales\ClientResource\RelationManagers\RecurringInvoicesRelationManager;
use App\Filament\Company\Resources\Sales\ClientResource\Widgets\InvoiceOverview;
use App\Filament\Company\Resources\Sales\EstimateResource\Pages\CreateEstimate;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages\CreateRecurringInvoice;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Support\Htmlable;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getAllRelationManagers(): array
    {
        return [
            InvoicesRelationManager::class,
            RecurringInvoicesRelationManager::class,
            EstimatesRelationManager::class,
        ];
    }

    public function getTitle(): string | Htmlable
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit client')
                ->outlined(),
            ActionGroup::make([
                ActionGroup::make([
                    Action::make('newInvoice')
                        ->label('New invoice')
                        ->icon('heroicon-m-document-plus')
                        ->url(CreateInvoice::getUrl(['client' => $this->record->getKey()])),
                    Action::make('newEstimate')
                        ->label('New estimate')
                        ->icon('heroicon-m-document-duplicate')
                        ->url(CreateEstimate::getUrl(['client' => $this->record->getKey()])),
                    Action::make('newRecurringInvoice')
                        ->label('New recurring invoice')
                        ->icon('heroicon-m-arrow-path')
                        ->url(CreateRecurringInvoice::getUrl(['client' => $this->record->getKey()])),
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
            InvoiceOverview::class,
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('General')
                    ->columns()
                    ->schema([
                        TextEntry::make('primaryContact.full_name')
                            ->label('Primary contact'),
                        TextEntry::make('primaryContact.email')
                            ->label('Primary email'),
                        TextEntry::make('primaryContact.first_available_phone')
                            ->label('Primary phone'),
                        TextEntry::make('website')
                            ->label('Website')
                            ->url(static fn ($state) => $state, true)
                            ->link(),
                    ]),
                Section::make('Additional Details')
                    ->columns()
                    ->schema([
                        TextEntry::make('billingAddress.address_string')
                            ->label('Billing address')
                            ->listWithLineBreaks(),
                        TextEntry::make('shippingAddress.address_string')
                            ->label('Shipping address')
                            ->listWithLineBreaks(),
                        TextEntry::make('notes')
                            ->label('Delivery instructions'),
                    ]),
            ]);
    }
}
