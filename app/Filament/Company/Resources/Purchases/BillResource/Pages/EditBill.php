<?php

namespace App\Filament\Company\Resources\Purchases\BillResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Concerns\ManagesLineItems;
use App\Filament\Company\Resources\Purchases\BillResource;
use App\Models\Accounting\Bill;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditBill extends EditRecord
{
    use HandlePageRedirect;
    use ManagesLineItems;

    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Bill $record */
        $lineItems = collect($data['lineItems'] ?? []);

        $this->deleteRemovedLineItems($record, $lineItems);

        $this->handleLineItems($record, $lineItems);

        $totals = $this->updateDocumentTotals($record, $data);

        $data = array_merge($data, $totals);

        $record = parent::handleRecordUpdate($record, $data);

        $record->updateInitialTransaction();

        return $record;
    }
}
