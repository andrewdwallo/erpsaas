<?php

namespace App\Filament\Infolists\Components;

use App\Enums\Accounting\DocumentType;
use App\Enums\Setting\Template;
use App\Models\Setting\DocumentDefault;
use Closure;
use Filament\Schemas\Components\Grid;

class DocumentPreview extends Grid
{
    protected string $view = 'filament.infolists.components.document-preview';

    protected DocumentType $documentType = DocumentType::Invoice;

    protected bool | Closure $isPreview = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpan(3);
    }

    public function type(DocumentType | string $type): static
    {
        if (is_string($type)) {
            $type = DocumentType::from($type);
        }

        $this->documentType = $type;

        return $this;
    }

    public function preview(bool | Closure $condition = true): static
    {
        $this->isPreview = $condition;

        return $this;
    }

    public function getType(): DocumentType
    {
        return $this->documentType;
    }

    public function isPreview(): bool
    {
        return (bool) $this->evaluate($this->isPreview);
    }

    public function getTemplate(): Template
    {
        if ($this->documentType === DocumentType::RecurringInvoice) {
            $lookupType = DocumentType::Invoice;
        } else {
            $lookupType = $this->documentType;
        }

        $defaults = DocumentDefault::query()
            ->type($lookupType)
            ->first();

        return $defaults?->template ?? Template::Default;
    }
}
