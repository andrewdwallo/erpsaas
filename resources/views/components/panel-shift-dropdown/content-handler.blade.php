@props([
    'item',
])

@if(isset($item['panelId']))
    {{-- Render a submenu toggle for groups in the main panel --}}
    <x-panel-shift-dropdown.toggle :label="$item['label']" :icon="$item['icon']" :panel-id="$item['panelId']" />
@elseif(!empty($item['items']))
    {{-- For nested groups, recursively render their items --}}
    @foreach($item['items'] as $nestedItem)
        <x-panel-shift-dropdown.content-handler :item="$nestedItem" />
    @endforeach
@elseif(isset($item['url']))
    {{-- Render standalone items --}}
    <x-panel-shift-dropdown.item :url="$item['url']" :label="$item['label']" :icon="$item['icon']" />
@endif
