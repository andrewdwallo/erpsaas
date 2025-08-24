<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class CustomTableRepeater extends Repeater
{
    protected bool | Closure $spreadsheet = false;

    protected bool | Closure $reorderAtStart = false;

    protected View | Htmlable | Closure | null $footerItem = null;

    /**
     * @var array<string> | Closure | null
     */
    protected array | Closure | null $excludedAttributesForCloning = [
        'id',
        'line_number',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    public function spreadsheet(bool | Closure $condition = true): static
    {
        $this->spreadsheet = $condition;

        return $this;
    }

    public function isSpreadsheet(): bool
    {
        return (bool) $this->evaluate($this->spreadsheet);
    }

    public function reorderAtStart(bool | Closure $condition = true): static
    {
        $this->reorderAtStart = $condition;

        return $this;
    }

    public function isReorderAtStart(): bool
    {
        return $this->evaluate($this->reorderAtStart) && $this->isReorderable();
    }

    public function footerItem(View | Htmlable | Closure | null $footer = null): static
    {
        $this->footerItem = $footer;

        return $this;
    }

    public function getFooterItem(): View | Htmlable | null
    {
        return $this->evaluate($this->footerItem);
    }

    public function hasFooterItem(): bool
    {
        return $this->footerItem !== null;
    }

    /**
     * @param  array<string> | Closure | null  $attributes
     */
    public function excludeAttributesForCloning(array | Closure | null $attributes): static
    {
        $this->excludedAttributesForCloning = $attributes;

        return $this;
    }

    /**
     * @return array<string> | null
     */
    public function getExcludedAttributesForCloning(): ?array
    {
        return $this->evaluate($this->excludedAttributesForCloning);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->minItems(1);

        $this->extraAttributes(function (): array {
            $attributes = [];

            if ($this->isSpreadsheet()) {
                $attributes['class'] = 'is-spreadsheet';
            }

            return $attributes;
        });

        $this->reorderAction(function (Action $action) {
            if ($this->isReorderAtStart()) {
                $action->icon('heroicon-m-bars-3');
            }

            return $action;
        });

        $this->cloneAction(function (Action $action) {
            return $action
                ->action(function (array $arguments, CustomTableRepeater $component): void {
                    $newUuid = $component->generateUuid();
                    $items = $component->getState();

                    $clone = $items[$arguments['item']];

                    foreach ($component->getExcludedAttributesForCloning() as $attribute) {
                        unset($clone[$attribute]);
                    }

                    if ($newUuid) {
                        $items[$newUuid] = $clone;
                    } else {
                        $items[] = $clone;
                    }

                    $component->state($items);
                    $component->collapsed(false, shouldMakeComponentCollapsible: false);
                    $component->callAfterStateUpdated();
                });
        });
    }
}
