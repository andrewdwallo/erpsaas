<x-filament-breezy::auth-card action="register">
    <div class="w-full flex justify-center">
        <x-filament::brand />
    </div>
    <div>
        <h2 class="font-bold tracking-tight text-center text-2xl">
            {{ __('filament-breezy::default.registration.heading') }}
        </h2>
        <p class="mt-2 text-sm text-center">
            {{ __('filament-breezy::default.or') }}
            <a class href="{{route('filament.auth.login')}}">
                {{ strtolower(__('filament::login.heading')) }}
            </a>
        </p>
    </div>
    <form wire:submit.prevent="register" class="space-y-4 md:space-y-6 bg-primary-600">
        {{ $this->form }}
    </form>
</x-filament-breezy::auth-card>
