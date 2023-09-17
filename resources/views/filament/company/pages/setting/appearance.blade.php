<x-filament-panels::page style="margin-bottom: 500px">
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page>
<script>
    // Save the scroll position before the page is reloaded
    document.addEventListener('livewire:init', function () {
        Livewire.on('appearanceUpdated', function () {
            localStorage.setItem('scrollPosition', window.scrollY.toString());
            window.location.reload();
        });
    });

    // Restore the scroll position after the page is reloaded
    window.addEventListener('load', function () {
        const scrollPosition = parseInt(localStorage.getItem('scrollPosition'), 10);
        if (scrollPosition) {
            window.scrollTo(0, scrollPosition);
            localStorage.removeItem('scrollPosition');
        }
    });
</script>
