<x-filament-companies::grid-section md="2">
    <x-slot name="title">
        {{ __('filament-companies::default.grid_section_titles.update_password') }}
    </x-slot>

    <x-slot name="description">
        {{ __('filament-companies::default.grid_section_descriptions.update_password') }}
    </x-slot>

    <x-filament::section>
        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}

            <div class="text-left">
                <x-filament::button type="submit">
                    {{ __('filament-companies::default.buttons.save') }}
                </x-filament::button>
            </div>
        </x-filament-panels::form>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-companies::grid-section>
