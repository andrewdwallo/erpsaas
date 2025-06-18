<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $url = $getUrl();
        $shouldOpenInNewTab = $shouldOpenUrlInNewTab();
    @endphp

    @if(filled($url))
        <a href="{{ $url }}"@if($shouldOpenInNewTab) target="_blank" rel="noopener noreferrer"@endif>
    @endif

    <x-report-entry
        :heading="$getHeading()"
        :description="$getDescription()"
        :icon="$getIcon()"
        :iconColor="$getIconColor()"
    />

    @if(filled($url))
        </a>
    @endif
</x-dynamic-component>
