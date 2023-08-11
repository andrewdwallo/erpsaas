<x-filament::form wire:submit.prevent="save">
    {{ $this->form }}

    <div class="flex flex-wrap items-center gap-4 justify-start">
        <x-filament::button type="submit" tag="button" wire:target="save">
            {{ __('Save Changes') }}
        </x-filament::button>
    </div>
</x-filament::form>
