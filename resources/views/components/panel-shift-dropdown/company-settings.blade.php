@props([
    'icon' => null,
    'currentTenant' => null,
])

@php
    $currentTenantName = filament()->getTenantName($currentTenant);
    $currentCompany = auth()->user()->currentCompany;
    $currentCompanyOwner = $currentCompany->owner;
    $items = filament()->getTenantMenuItems();
    $profileItem = $items['profile'] ?? null;
    $profileItemUrl = $profileItem?->getUrl();
@endphp

<li class="grid grid-flow-col auto-cols-max gap-x-2 items-start p-2">
    <div class="icon h-9 w-9 flex items-center justify-center rounded-full bg-gray-200 dark:bg-white/10">
        <x-filament::icon
            :icon="$icon"
            class="h-6 w-6 text-gray-600 dark:text-gray-200"
        />
    </div>
    <div>
        <div class="px-2 pb-2">
            <h2 class="text-gray-800 dark:text-gray-200 text-base font-semibold">
                {{ $currentTenantName }}
            </h2>
            <p class="text-sm font-normal text-gray-500 dark:text-gray-400">
                {{ $currentCompanyOwner->email }}
            </p>
        </div>
    </div>
</li>

<x-panel-shift-dropdown.item
    :url="\App\Filament\Company\Clusters\Settings::getUrl()"
    label="All Settings"
    icon="heroicon-m-cog-6-tooth"
/>

<x-panel-shift-dropdown.item
    :url="$profileItemUrl ?? filament()->getTenantProfileUrl()"
    :label="$profileItem?->getLabel() ?? filament()->getTenantProfilePage()::getLabel()"
    icon="heroicon-m-briefcase"
/>

<x-panel-shift-dropdown.toggle
    label="Switch Company"
    icon="heroicon-m-arrows-right-left"
    panel-id="company-switcher"
/>
