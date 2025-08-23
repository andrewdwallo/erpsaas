<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <x-report-entry
        :heading="$getHeading()"
        :description="$getDescription()"
        :icon="$getIcon()"
        :iconColor="$getIconColor()"
    />
</x-dynamic-component>
