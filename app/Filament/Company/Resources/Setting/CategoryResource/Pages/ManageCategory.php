<?php

namespace App\Filament\Company\Resources\Setting\CategoryResource\Pages;

use App\Enums\CategoryType;
use App\Filament\Company\Resources\Setting\CategoryResource;
use App\Models\Setting\Category;
use App\Traits\HandlesResourceRecordCreation;
use App\Traits\HandlesResourceRecordUpdate;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;

class ManageCategory extends ManageRecords
{
    use HandlesResourceRecordCreation;
    use HandlesResourceRecordUpdate;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data): Model {
                    $user = auth()->user();

                    $evaluatedTypes = [CategoryType::Income, CategoryType::Expense];

                    return $this->handleRecordCreationWithUniqueField($data, new Category(), $user, 'type', $evaluatedTypes);
                })
                ->modalWidth(MaxWidth::TwoExtraLarge),
        ];
    }

    protected function configureEditAction(Tables\Actions\EditAction $action): void
    {
        parent::configureEditAction($action);

        $action
            ->modalWidth(MaxWidth::TwoExtraLarge)
            ->using(function (Model $record, array $data): Model {
                $user = auth()->user();

                $evaluatedTypes = [CategoryType::Income, CategoryType::Expense];

                return $this->handleRecordUpdateWithUniqueField($record, $data, $user, 'type', $evaluatedTypes);
            });
    }
}
