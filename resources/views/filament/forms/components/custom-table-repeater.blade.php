@php
    use Filament\Forms\Components\Actions\Action;
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Enums\MaxWidth;

    $containers = $getChildComponentContainers();

    $addAction = $getAction($getAddActionName());
    $cloneAction = $getAction($getCloneActionName());
    $deleteAction = $getAction($getDeleteActionName());
    $moveDownAction = $getAction($getMoveDownActionName());
    $moveUpAction = $getAction($getMoveUpActionName());
    $reorderAction = $getAction($getReorderActionName());
    $isReorderableWithButtons = $isReorderableWithButtons();
    $extraItemActions = $getExtraItemActions();
    $extraActions = $getExtraActions();
    $visibleExtraItemActions = [];
    $visibleExtraActions = [];

    $headers = $getHeaders();
    $renderHeader = $shouldRenderHeader();
    $stackAt = $getStackAt();
    $hasContainers = count($containers) > 0;
    $emptyLabel = $getEmptyLabel();
    $streamlined = $isStreamlined();

    $reorderAtStart = $isReorderAtStart();

    $statePath = $getStatePath();

    foreach ($extraActions as $extraAction) {
        $visibleExtraActions = array_filter(
            $extraActions,
            fn (Action $action): bool => $action->isVisible(),
        );
    }

    foreach ($extraItemActions as $extraItemAction) {
        $visibleExtraItemActions = array_filter(
            $extraItemActions,
            fn (Action $action): bool => $action->isVisible(),
        );
    }

    $hasActions = $reorderAction->isVisible()
        || $cloneAction->isVisible()
        || $deleteAction->isVisible()
        || $moveUpAction->isVisible()
        || $moveDownAction->isVisible()
        || filled($visibleExtraItemActions);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{}"
        {{ $attributes->merge($getExtraAttributes())->class([
            'table-repeater-component space-y-6 relative',
            'streamlined' => $streamlined,
            match ($stackAt) {
                'sm', MaxWidth::Small => 'break-point-sm',
                'lg', MaxWidth::Large => 'break-point-lg',
                'xl', MaxWidth::ExtraLarge => 'break-point-xl',
                '2xl', MaxWidth::TwoExtraLarge => 'break-point-2xl',
                default => 'break-point-md',
            }
        ]) }}
    >
        @if (count($containers) || $emptyLabel !== false)
            <div class="table-repeater-container rounded-xs relative ring-1 ring-gray-950/5 dark:ring-white/20">
                <table class="w-full">
                    <thead @class([
                        'table-repeater-header-hidden sr-only' => ! $renderHeader,
                        'table-repeater-header overflow-hidden border-b border-gray-950/5 dark:border-white/20' => $renderHeader,
                    ])>
                    <tr class="text-xs md:divide-x rtl:divide-x-reverse md:divide-gray-950/5 dark:md:divide-white/20">
                        {{-- Move actions column to start if reorderAtStart is true --}}
                        @if ($hasActions && count($containers) && $reorderAtStart)
                            <th class="table-repeater-header-column w-px first:rounded-tl-sm first:rtl:rounded-tr-sm first:rtl:rounded-tl-none p-2 bg-gray-100 dark:bg-gray-900/60">
                                <span class="sr-only">
                                    {{ trans('table-repeater::components.repeater.row_actions.label') }}
                                </span>
                            </th>
                        @endif

                        @foreach ($headers as $key => $header)
                            <th
                                @class([
                                    'table-repeater-header-column p-2 font-medium first:rounded-tl-sm first:rtl:rounded-tr-sm first:rtl:rounded-tl-none last:rounded-tr-sm bg-gray-100 dark:text-gray-300 dark:bg-gray-900/60',
                                    match($header->getAlignment()) {
                                      'center', Alignment::Center => 'text-center',
                                      'right', 'end', Alignment::Right, Alignment::End => 'text-end',
                                      default => 'text-start'
                                    }
                                ])
                                style="width: {{ $header->getWidth() }}"
                            >
                                {{ $header->getLabel() }}
                                @if ($header->isRequired())
                                    <span class="whitespace-nowrap">
                                        <sup class="font-medium text-danger-700 dark:text-danger-400">*</sup>
                                    </span>
                                @endif
                            </th>
                        @endforeach

                        @if ($hasActions && count($containers))
                            <th class="table-repeater-header-column w-px last:rounded-tr-sm last:rtl:rounded-tr-none last:rtl:rounded-tl-sm p-2 bg-gray-100 dark:bg-gray-900/60">
                                <span class="sr-only">
                                    {{ trans('table-repeater::components.repeater.row_actions.label') }}
                                </span>
                            </th>
                        @endif
                    </tr>
                    </thead>
                    <tbody
                        x-sortable
                        wire:end.stop="{{ 'mountFormComponentAction(\'' . $statePath . '\', \'reorder\', { items: $event.target.sortable.toArray() })' }}"
                        class="table-repeater-rows-wrapper divide-y divide-gray-950/5 dark:divide-white/20"
                    >
                    @if (count($containers))
                        @foreach ($containers as $uuid => $row)
                            @php
                                $visibleExtraItemActions = array_filter(
                                    $extraItemActions,
                                    fn (Action $action): bool => $action(['item' => $uuid])->isVisible(),
                                );
                            @endphp
                            <tr
                                wire:key="{{ $this->getId() }}.{{ $row->getStatePath() }}.{{ $field::class }}.item"
                                x-sortable-item="{{ $uuid }}"
                                class="table-repeater-row"
                            >
                                {{-- Add reorder action column at start if reorderAtStart is true --}}
                                @if ($hasActions && $reorderAtStart && $reorderAction->isVisible())
                                    <td class="table-repeater-column p-2 w-px align-top">
                                        <ul class="flex items-center table-repeater-row-actions gap-x-3 px-2">
                                            <li x-sortable-handle class="shrink-0">
                                                {{ $reorderAction }}
                                            </li>
                                        </ul>
                                    </td>
                                @endif

                                @php($counter = 0)
                                @foreach($row->getComponents() as $cell)
                                    @if($cell instanceof \Filament\Forms\Components\Hidden || $cell->isHidden())
                                        {{ $cell }}
                                    @else
                                        <td
                                            @class([
                                                'table-repeater-column align-top',
                                                'p-2' => ! $streamlined,
                                                'has-hidden-label' => $cell->isLabelHidden(),
                                                match($headers[$counter++]->getAlignment()) {
                                                  'center', Alignment::Center => 'text-center',
                                                  'right', 'end', Alignment::Right, Alignment::End => 'text-end',
                                                  default => 'text-start'
                                                }
                                            ])
                                            style="width: {{ $cell->getMaxWidth() ?? 'auto' }}"
                                        >
                                            {{ $cell }}
                                        </td>
                                    @endif
                                @endforeach

                                @if ($hasActions)
                                    <td class="table-repeater-column p-2 w-px align-top">
                                        <ul class="flex items-center table-repeater-row-actions gap-x-3 px-2">
                                            @foreach ($visibleExtraItemActions as $extraItemAction)
                                                <li>
                                                    {{ $extraItemAction(['item' => $uuid]) }}
                                                </li>
                                            @endforeach

                                            @if ($reorderAction->isVisible() && ! $reorderAtStart)
                                                <li x-sortable-handle class="shrink-0">
                                                    {{ $reorderAction }}
                                                </li>
                                            @endif

                                            @if ($isReorderableWithButtons)
                                                @if (! $loop->first)
                                                    <li>
                                                        {{ $moveUpAction(['item' => $uuid]) }}
                                                    </li>
                                                @endif

                                                @if (! $loop->last)
                                                    <li>
                                                        {{ $moveDownAction(['item' => $uuid]) }}
                                                    </li>
                                                @endif
                                            @endif

                                            @if ($cloneAction->isVisible())
                                                <li>
                                                    {{ $cloneAction(['item' => $uuid]) }}
                                                </li>
                                            @endif

                                            @if ($deleteAction->isVisible())
                                                <li>
                                                    {{ $deleteAction(['item' => $uuid]) }}
                                                </li>
                                            @endif
                                        </ul>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @else
                        <tr class="table-repeater-row table-repeater-empty-row">
                            <td colspan="{{ count($headers) + intval($hasActions) }}"
                                class="table-repeater-column table-repeater-empty-column p-4 w-px text-center italic">
                                {{ $emptyLabel ?: trans('table-repeater::components.repeater.empty.label') }}
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        @endif

        @if ($addAction->isVisible() || filled($visibleExtraActions) || $hasFooterItem())
            <div class="flex justify-between items-start">
                <!-- Actions grouped to the left -->
                @if ($addAction->isVisible() || filled($visibleExtraActions))
                    <ul class="flex gap-4">
                        @if ($addAction->isVisible())
                            <li>
                                {{ $addAction }}
                            </li>
                        @endif
                        @if (filled($visibleExtraActions))
                            @foreach ($visibleExtraActions as $extraAction)
                                <li>
                                    {{ $extraAction }}
                                </li>
                            @endforeach
                        @endif
                    </ul>
                @endif

                <!-- Container for Footer Item to the right -->
                @if($hasFooterItem())
                    <div>
                        {{ $getFooterItem() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-dynamic-component>
