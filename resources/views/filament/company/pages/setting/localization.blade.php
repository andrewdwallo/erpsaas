<x-filament-panels::page style="margin-bottom: 500px">
    {{ $this->content }}
</x-filament-panels::page>
<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('localizationUpdated', function () {
            window.location.reload();
        });
    });
</script>
