<?php

namespace App\Filament\Tables\Actions;

use Closure;
use Filament\Actions\Concerns\CanReplicateRecords;
use Filament\Actions\Contracts\ReplicatesRecords;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ReplicateBulkAction extends BulkAction implements ReplicatesRecords
{
    use CanReplicateRecords;

    protected ?Closure $afterReplicaSaved = null;

    protected array $relationshipsToReplicate = [];

    protected array | Closure | null $excludedAttributesPerRelationship = null;

    public static function getDefaultName(): ?string
    {
        return 'replicate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Replicate Selected'));

        $this->modalHeading(fn (): string => __('Replicate selected :label', ['label' => $this->getPluralModelLabel()]));

        $this->modalSubmitActionLabel(__('Replicate'));

        $this->successNotificationTitle(__('Replicated'));

        $this->icon('heroicon-m-square-3-stack-3d');

        $this->requiresConfirmation();

        $this->modalIcon('heroicon-o-square-3-stack-3d');

        $this->action(function () {
            $result = $this->process(function (Collection $records) {
                $records->each(function (Model $record) {
                    $this->replica = $record->replicate($this->getExcludedAttributes());

                    $this->callBeforeReplicaSaved();

                    $this->replica->save();

                    $this->replicateRelationships($record, $this->replica);

                    $this->callAfterReplicaSaved($record, $this->replica);
                });
            });

            try {
                return $result;
            } finally {
                $this->success();
            }
        });
    }

    public function replicateRelationships(Model $original, Model $replica): void
    {
        foreach ($this->relationshipsToReplicate as $relationship) {
            $relation = $original->$relationship();

            $excludedAttributes = $this->excludedAttributesPerRelationship[$relationship] ?? [];

            if ($relation instanceof BelongsToMany) {
                $replica->$relationship()->sync($relation->pluck($relation->getRelated()->getKeyName()));
            } elseif ($relation instanceof HasMany) {
                $relation->each(function (Model $related) use ($excludedAttributes, $replica, $relationship) {
                    $relatedReplica = $related->replicate($excludedAttributes);
                    $relatedReplica->{$replica->$relationship()->getForeignKeyName()} = $replica->getKey();
                    $relatedReplica->save();
                });
            } elseif ($relation instanceof MorphMany) {
                $relation->each(function (Model $related) use ($excludedAttributes, $relation, $replica) {
                    $relatedReplica = $related->replicate($excludedAttributes);
                    $relatedReplica->{$relation->getForeignKeyName()} = $replica->getKey();
                    $relatedReplica->{$relation->getMorphType()} = $replica->getMorphClass();
                    $relatedReplica->save();

                    if (method_exists($related, 'adjustments')) {
                        $relatedReplica->adjustments()->sync($related->adjustments->pluck('id'));
                    }
                });
            } elseif ($relation instanceof HasOne && $relation->exists()) {
                $related = $relation->first();
                $relatedReplica = $related->replicate($excludedAttributes);
                $relatedReplica->{$replica->$relationship()->getForeignKeyName()} = $replica->getKey();
                $relatedReplica->save();
            }
        }
    }

    public function withReplicatedRelationships(array $relationships): static
    {
        $this->relationshipsToReplicate = $relationships;

        return $this;
    }

    public function withExcludedRelationshipAttributes(string $relationship, array | Closure | null $attributes): static
    {
        $this->excludedAttributesPerRelationship[$relationship] = $attributes;

        return $this;
    }

    public function getExcludedRelationshipAttributes(): ?array
    {
        return $this->evaluate($this->excludedAttributesPerRelationship);
    }

    public function afterReplicaSaved(Closure $callback): static
    {
        $this->afterReplicaSaved = $callback;

        return $this;
    }

    public function callAfterReplicaSaved(Model $original, Model $replica): void
    {
        $this->evaluate($this->afterReplicaSaved, [
            'original' => $original,
            'replica' => $replica,
        ]);
    }
}
