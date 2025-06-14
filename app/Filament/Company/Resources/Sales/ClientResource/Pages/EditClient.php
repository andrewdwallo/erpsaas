<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Models\Common\Client;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditClient extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::FiveExtraLarge;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Client $record */
        $record->updateWithRelations($data);

        return $record;
    }
}
