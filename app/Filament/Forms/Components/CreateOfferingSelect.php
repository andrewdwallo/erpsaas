<?php

namespace App\Filament\Forms\Components;

use App\Filament\Company\Resources\Common\OfferingResource;
use App\Models\Common\Offering;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class CreateOfferingSelect extends Select
{
    protected bool $isPurchasable = true;

    protected bool $isSellable = true;

    public function purchasable(bool $condition = true): static
    {
        $this->isPurchasable = $condition;
        $this->isSellable = false;

        return $this;
    }

    public function sellable(bool $condition = true): static
    {
        $this->isSellable = $condition;
        $this->isPurchasable = false;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->searchable()
            ->preload()
            ->createOptionForm(fn (Schema $schema) => $this->createOfferingForm($schema))
            ->createOptionAction(fn (Action $action) => $this->createOfferingAction($action));

        $this->relationship(
            name: fn () => $this->isPurchasable() && ! $this->isSellable() ? 'purchasableOffering' : ($this->isSellable() && ! $this->isPurchasable() ? 'sellableOffering' : 'offering'),
            titleAttribute: 'name'
        );

        $this->createOptionUsing(function (array $data, Schema $schema) {
            if ($this->isSellableAndPurchasable()) {
                $attributes = array_flip($data['attributes'] ?? []);

                $data['sellable'] = isset($attributes['Sellable']);
                $data['purchasable'] = isset($attributes['Purchasable']);
            } else {
                $data['sellable'] = $this->isSellable;
                $data['purchasable'] = $this->isPurchasable;
            }

            unset($data['attributes']);

            $offering = Offering::create($data);

            $schema->model($offering)->saveRelationships();

            return $offering->getKey();
        });
    }

    protected function createOfferingForm(Schema $schema): Schema
    {
        return $schema->components([
            OfferingResource::getGeneralSection($this->isSellableAndPurchasable()),
            OfferingResource::getSellableSection()->visible(
                fn (Get $get) => $this->isSellableAndPurchasable()
                    ? in_array('Sellable', $get('attributes') ?? [])
                    : $this->isSellable()
            ),
            OfferingResource::getPurchasableSection()->visible(
                fn (Get $get) => $this->isSellableAndPurchasable()
                    ? in_array('Purchasable', $get('attributes') ?? [])
                    : $this->isPurchasable()
            ),
        ]);
    }

    protected function createOfferingAction(Action $action): Action
    {
        return $action
            ->label('Create offering')
            ->slideOver()
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalHeading('Create a new offering');
    }

    public function isSellable(): bool
    {
        return $this->isSellable;
    }

    public function isPurchasable(): bool
    {
        return $this->isPurchasable;
    }

    public function isSellableAndPurchasable(): bool
    {
        return $this->isSellable && $this->isPurchasable;
    }
}
