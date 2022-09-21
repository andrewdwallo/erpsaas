<x-filament-breezy::auth-card action="authenticate">

    <div class="w-full flex justify-center">
        <x-filament::brand />
    </div>


    <div>
        <h2 class="font-bold tracking-tight text-center text-2xl">
            {{ $this->usingRecoveryCode ? __('filament-breezy::default.two_factor.recovery.heading') : __('filament-breezy::default.two_factor.heading') }}
        </h2>
        <p class="mt-2 text-sm text-center">
            {{ $this->usingRecoveryCode ? __('filament-breezy::default.two_factor.recovery.description') : __('filament-breezy::default.two_factor.description') }} <a class="text-primary-600" href="{{route('filament.auth.login')}}">
                {{ __('filament-breezy::default.two_factor.back_to_login_link') }}
            </a>
        </p>
    </div>

    {{ $this->twoFactorForm }}

    <x-filament::button type="submit" class="w-full">
        {{ __('filament::login.buttons.submit.label') }}
    </x-filament::button>

    <div class="text-center">
        {{ $this->usingRecoveryCode ? '' : __('filament-breezy::default.two_factor.recovery_code_text') }}
        <a x-data @click="$wire.toggleRecoveryCode()" class="text-primary-600 hover:text-primary-700" href="#">{{$this->usingRecoveryCode ? __('filament-breezy::default.cancel') : __('filament-breezy::default.two_factor.recovery_code_link') }}</a>
    </div>

</x-filament-breezy::auth-card>
