<?php

namespace App\Filament\Company\Clusters\Settings\Resources\Adjustments\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Clusters\Settings\Resources\Adjustments\AdjustmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdjustment extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = AdjustmentResource::class;
}
