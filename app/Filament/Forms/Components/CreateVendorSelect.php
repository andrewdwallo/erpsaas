<?php

namespace App\Filament\Forms\Components;

use App\Filament\Company\Resources\Purchases\VendorResource;
use App\Models\Common\Vendor;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;

class CreateVendorSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->searchable()
            ->preload()
            ->createOptionForm(fn (Schema $schema) => $this->createVendorForm($schema))
            ->createOptionAction(fn (Action $action) => $this->createVendorAction($action));

        $this->relationship('vendor', 'name');

        $this->createOptionUsing(static function (array $data) {
            return DB::transaction(static function () use ($data) {
                $vendor = Vendor::createWithRelations($data);

                return $vendor->getKey();
            });
        });
    }

    protected function createVendorForm(Schema $schema): Schema
    {
        return VendorResource::form($schema);
    }

    protected function createVendorAction(Action $action): Action
    {
        return $action
            ->label('Create vendor')
            ->slideOver()
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalHeading('Create a new vendor');
    }
}
