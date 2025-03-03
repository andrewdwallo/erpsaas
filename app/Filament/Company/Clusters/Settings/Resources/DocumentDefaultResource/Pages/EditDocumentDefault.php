<?php

namespace App\Filament\Company\Clusters\Settings\Resources\DocumentDefaultResource\Pages;

use App\Filament\Company\Clusters\Settings\Resources\DocumentDefaultResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditDocumentDefault extends EditRecord
{
    protected static string $resource = DocumentDefaultResource::class;

    public function getRecordTitle(): string | Htmlable
    {
        return $this->record->type->getLabel();
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = parent::getBreadcrumbs();

        array_pop($breadcrumbs);

        return $breadcrumbs;
    }
}
