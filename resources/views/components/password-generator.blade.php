<x-forms::field-wrapper :id="$getId()"
    :label="$getLabel()"
    :label-sr-only="$isLabelHidden()"
    :helper-text="$getHelperText()"
    :hint="$getHint()"
    :hint-icon="$getHintIcon()"
    :required="$isRequired()"
    :state-path="$getStatePath()"
    class="filament-addons-slug-input-wrapper">
    <div x-data="{
        state: $wire.entangle('{{ $getStatePath() }}'),
        generatePassword: function() {
            let chars = '{{ $getChars() }}';
            let password = '';
            for (let i = 0; i < {{ $getPasswordLength() }}; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.state = password;
        }
    }">
        <div
            {{ $attributes->merge($getExtraAttributes())->class(['flex items-center space-x-2 group filament-forms-text-input-component']) }}>
            <div class="flex-1">
                <input type="text"
                    id="{{ $getId() }}"
                    x-model="state"
                    {!! ($placeholder = $getPlaceholder()) ? "placeholder=\"{$placeholder}\"" : null !!}
                    {!! $isRequired() ? 'required' : null !!}
                    {{ $getExtraInputAttributeBag()->class(['block w-full transition duration-75 rounded-lg shadow-sm focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 disabled:opacity-70', 'dark:bg-gray-700 dark:text-white' => config('forms.dark_mode'), 'border-gray-300' => !$errors->has($getStatePath()), 'dark:border-gray-600' => !$errors->has($getStatePath()) && config('forms.dark_mode'), 'border-danger-600 ring-danger-600' => $errors->has($getStatePath())]) }} />
                <input type="hidden"
                    {{ $applyStateBindingModifiers('wire:model') }}="{{ $getStatePath() }}" />
            </div>
            <div>
                <x-filament::button color="primary"
                    x-on:click.prevent="generatePassword()">
                    {{ __('Generate Password') }}
                </x-filament::button>
            </div>
        </div>
    </div>
</x-forms::field-wrapper>
