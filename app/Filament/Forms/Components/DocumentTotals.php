<?php

namespace App\Filament\Forms\Components;

use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\DocumentType;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

class DocumentTotals extends Grid
{
    protected string $view = 'filament.forms.components.document-totals';

    protected DocumentType $documentType = DocumentType::Invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schema([
            Select::make('discount_computation')
                ->label('Discount computation')
                ->hiddenLabel()
                ->options(AdjustmentComputation::class)
                ->default(AdjustmentComputation::Percentage)
                ->selectablePlaceholder(false)
                ->live(),
            TextInput::make('discount_rate')
                ->label('Discount rate')
                ->hiddenLabel()
                ->live()
                ->extraInputAttributes(['class' => 'text-right'])
                ->rate(
                    computation: static fn (Get $get) => $get('discount_computation'),
                    currency: static fn (Get $get) => $get('currency_code'),
                ),
        ]);
    }

    public function type(DocumentType | string $type): static
    {
        if (is_string($type)) {
            $type = DocumentType::from($type);
        }

        $this->documentType = $type;

        return $this;
    }

    public function getType(): DocumentType
    {
        return $this->documentType;
    }
}
