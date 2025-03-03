<?php

namespace App\Filament\Company\Resources\Purchases\BillResource\Pages;

use App\Concerns\ManagesLineItems;
use App\Concerns\RedirectToListPage;
use App\Filament\Company\Resources\Purchases\BillResource;
use App\Models\Accounting\Bill;
use App\Models\Common\Vendor;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

class CreateBill extends CreateRecord
{
    use ManagesLineItems;
    use RedirectToListPage;

    protected static string $resource = BillResource::class;

    #[Url(as: 'vendor')]
    public ?int $vendorId = null;

    public function mount(): void
    {
        parent::mount();

        if ($this->vendorId) {
            $this->data['vendor_id'] = $this->vendorId;

            if ($currencyCode = Vendor::find($this->vendorId)?->currency_code) {
                $this->data['currency_code'] = $currencyCode;
            }
        }
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::Full;
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var Bill $record */
        $record = parent::handleRecordCreation($data);

        $this->handleLineItems($record, collect($data['lineItems'] ?? []));

        $totals = $this->updateDocumentTotals($record, $data);

        $record->updateQuietly($totals);

        if (! $record->initialTransaction) {
            $record->createInitialTransaction();
        }

        return $record;
    }
}
