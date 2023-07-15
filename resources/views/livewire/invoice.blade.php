<div>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6">
            <div class="flex flex-wrap items-center gap-4 justify-start">
                <x-filament::button type="submit">
                    {{ __('Save Changes') }}
                </x-filament::button>
            </div>
        </div>
    </form>
</div>
