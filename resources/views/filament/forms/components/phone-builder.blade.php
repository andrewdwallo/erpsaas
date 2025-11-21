@php
    use Filament\Actions\Action;
    use Filament\Support\Enums\Alignment;

    $fieldWrapperView = $getFieldWrapperView();
    $items = $getItems();
    $blockPickerBlocks = $getBlockPickerBlocks();
    $blockPickerColumns = $getBlockPickerColumns();
    $blockPickerWidth = $getBlockPickerWidth();
    $hasBlockPreviews = $hasBlockPreviews();
    $hasInteractiveBlockPreviews = $hasInteractiveBlockPreviews();

    $addAction = $getAction($getAddActionName());
    $addActionAlignment = $getAddActionAlignment();
    $addBetweenAction = $getAction($getAddBetweenActionName());
    $cloneAction = $getAction($getCloneActionName());
    $collapseAllAction = $getAction($getCollapseAllActionName());
    $editAction = $getAction($getEditActionName());
    $expandAllAction = $getAction($getExpandAllActionName());
    $deleteAction = $getAction($getDeleteActionName());
    $moveDownAction = $getAction($getMoveDownActionName());
    $moveUpAction = $getAction($getMoveUpActionName());
    $reorderAction = $getAction($getReorderActionName());
    $extraItemActions = $getExtraItemActions();

    $isAddable = $isAddable();
    $isCloneable = $isCloneable();
    $isCollapsible = $isCollapsible();
    $isDeletable = $isDeletable();
    $isReorderableWithButtons = $isReorderableWithButtons();
    $isReorderableWithDragAndDrop = $isReorderableWithDragAndDrop();

    $collapseAllActionIsVisible = $isCollapsible && $collapseAllAction->isVisible();
    $expandAllActionIsVisible = $isCollapsible && $expandAllAction->isVisible();

    $key = $getKey();
    $statePath = $getStatePath();

    $labelBetweenItems = $getLabelBetweenItems();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class([
                    'fi-fo-builder',
                    'fi-collapsible' => $isCollapsible,
                ])
        }}
    >
        @if ($collapseAllActionIsVisible || $expandAllActionIsVisible)
            <div
                @class([
                    'fi-fo-builder-actions',
                    'fi-hidden' => count($items) < 2,
                ])
            >
                @if ($collapseAllActionIsVisible)
                    <span
                        x-on:click="$dispatch('builder-collapse', '{{ $statePath }}')"
                    >
                        {{ $collapseAllAction }}
                    </span>
                @endif

                @if ($expandAllActionIsVisible)
                    <span
                        x-on:click="$dispatch('builder-expand', '{{ $statePath }}')"
                    >
                        {{ $expandAllAction }}
                    </span>
                @endif
            </div>
        @endif

        @if (count($items))
            <ul
                x-sortable
                data-sortable-animation-duration="{{ $getReorderAnimationDuration() }}"
                wire:end.stop="mountAction('reorder', { items: $event.target.sortable.toArray() }, { schemaComponent: '{{ $key }}' })"
                class="fi-fo-builder-items space-y-6"
            >
                @php
                    $hasBlockLabels = $hasBlockLabels();
                    $hasBlockIcons = $hasBlockIcons();
                    $hasBlockNumbers = $hasBlockNumbers();
                @endphp

                @foreach ($items as $itemKey => $item)
                    @php
                        $visibleExtraItemActions = array_filter(
                            $extraItemActions,
                            fn (Action $action): bool => $action(['item' => $itemKey])->isVisible(),
                        );
                        $cloneAction = $cloneAction(['item' => $itemKey]);
                        $cloneActionIsVisible = $isCloneable && $cloneAction->isVisible();
                        $deleteAction = $deleteAction(['item' => $itemKey]);
                        $deleteActionIsVisible = $isDeletable && $deleteAction->isVisible();
                        $editAction = $editAction(['item' => $itemKey]);
                        $editActionIsVisible = $hasBlockPreviews && $editAction->isVisible();
                        $moveDownAction = $moveDownAction(['item' => $itemKey])->disabled($loop->last);
                        $moveDownActionIsVisible = $isReorderableWithButtons && $moveDownAction->isVisible();
                        $moveUpAction = $moveUpAction(['item' => $itemKey])->disabled($loop->first);
                        $moveUpActionIsVisible = $isReorderableWithButtons && $moveUpAction->isVisible();
                        $reorderActionIsVisible = $isReorderableWithDragAndDrop && $reorderAction->isVisible();
                    @endphp

                    <li
                        wire:key="{{ $item->getLivewireKey() }}.item"
                        x-sortable-item="{{ $itemKey }}"
                        class="flex items-center gap-x-4"
                    >
                        <!-- Input Field -->
                        <div class="w-full">
                            {{ $item }}
                        </div>

                        <!-- Delete Action -->
                        @if ($deleteActionIsVisible)
                            <div class="mt-6">
                                {{ $deleteAction }}
                            </div>
                        @endif
                    </li>


                    @if (! $loop->last)
                        @if ($isAddable && $addBetweenAction(['afterItem' => $itemKey])->isVisible())
                            <li class="fi-fo-builder-add-between-items-ctn relative -top-2 mt-0! h-0">
                                <div
                                    class="fi-fo-builder-add-between-items flex w-full justify-center opacity-0 transition duration-75 hover:opacity-100"
                                >
                                    <div
                                        class="fi-fo-builder-block-picker-ctn rounded-lg bg-white dark:bg-gray-900"
                                    >
                                        <x-filament-forms::builder.block-picker
                                            :action="$addBetweenAction"
                                            :after-item="$itemKey"
                                            :columns="$blockPickerColumns"
                                            :blocks="$blockPickerBlocks"
                                            :key="$key"
                                            :width="$blockPickerWidth"
                                        >
                                            <x-slot name="trigger">
                                                {{ $addBetweenAction(['afterItem' => $itemKey]) }}
                                            </x-slot>
                                        </x-filament-forms::builder.block-picker>
                                    </div>
                                </div>
                            </li>
                        @elseif (filled($labelBetweenItems))
                            <li class="fi-fo-builder-label-between-items-ctn">
                                <span class="fi-fo-builder-label-between-items">
                                    {{ $labelBetweenItems }}
                                </span>
                            </li>
                        @endif
                    @endif
                @endforeach
            </ul>
        @endif

        @if ($isAddable && $addAction->isVisible())
            <x-filament-forms::builder.block-picker
                :action="$addAction"
                :blocks="$blockPickerBlocks"
                :columns="$blockPickerColumns"
                :key="$key"
                :width="$blockPickerWidth"
                :class="($addActionAlignment instanceof Alignment) ? ('fi-align-' . $addActionAlignment->value) : $addActionAlignment"
            >
                <x-slot name="trigger">
                    {{ $addAction }}
                </x-slot>
            </x-filament-forms::builder.block-picker>
        @endif
    </div>
</x-dynamic-component>
