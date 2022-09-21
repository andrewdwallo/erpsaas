<x-filament::page>

    <x-filament-breezy::grid-section class="mt-8">

        <x-slot name="title">
            {{ __('filament-breezy::default.profile.personal_info.heading') }}
        </x-slot>

        <x-slot name="description">
            {{ __('filament-breezy::default.profile.personal_info.subheading') }}
        </x-slot>

        <form wire:submit.prevent="updateProfile" class="col-span-2 sm:col-span-1 mt-5 md:mt-0">
            <x-filament::card>

                {{ $this->updateProfileForm }}

                <x-slot name="footer">
                    <div class="text-right">
                        <x-filament::button type="submit">
                            {{ __('filament-breezy::default.profile.personal_info.submit.label') }}
                        </x-filament::button>
                    </div>
                </x-slot>
            </x-filament::card>
        </form>

    </x-filament-breezy::grid-section>

    <x-filament::hr />

    <x-filament-breezy::grid-section>

        <x-slot name="title">
            {{ __('filament-breezy::default.profile.password.heading') }}
        </x-slot>

        <x-slot name="description">
            {{ __('filament-breezy::default.profile.password.subheading') }}
        </x-slot>

        <form wire:submit.prevent="updatePassword" class="col-span-2 sm:col-span-1 mt-5 md:mt-0">
            <x-filament::card>

                {{ $this->updatePasswordForm }}

                <x-slot name="footer">
                    <div class="text-right">
                        <x-filament::button type="submit">
                            {{ __('filament-breezy::default.profile.password.submit.label') }}
                        </x-filament::button>
                    </div>
                </x-slot>
            </x-filament::card>
        </form>

    </x-filament-breezy::grid-section>

    @if(config('filament-breezy.enable_2fa'))
    <x-filament::hr />

    <x-filament-breezy::grid-section class="mt-8">

        <x-slot name="title">
            {{ __('filament-breezy::default.profile.2fa.title') }}
        </x-slot>

        <x-slot name="description">
            {{ __('filament-breezy::default.profile.2fa.description') }}
        </x-slot>


        <x-filament::card class="col-span-2 sm:col-span-1 mt-5 md:mt-0">
            @if($this->user->has_enabled_two_factor)

                @if ($this->user->has_confirmed_two_factor)
                    <p class="text-lg font-medium text-gray-900 dark:text-white">{{ __('filament-breezy::default.profile.2fa.enabled.title') }}</p>
                    {{ __('filament-breezy::default.profile.2fa.enabled.description') ?? __('filament-breezy::default.profile.2fa.enabled.store_codes') }}
                @else
                    <p class="text-lg font-medium text-gray-900 dark:text-white">{{ __('filament-breezy::default.profile.2fa.finish_enabling.title') }}</p>
                    {{ __('filament-breezy::default.profile.2fa.finish_enabling.description') }}
                @endif

                <div class="mt-2">
                    {!! $this->twoFactorQrCode() !!}
                    <p>{{ __('filament-breezy::default.profile.2fa.setup_key') }} {{ decrypt($this->user->two_factor_secret) }}</p>
                </div>

                @if ($this->showing_two_factor_recovery_codes)
                    <hr class="my-3"/>
                    <p>{{ __('filament-breezy::default.profile.2fa.enabled.store_codes') }}</p>
                    <div class="space-y-2">
                    @foreach (json_decode(decrypt($this->user->two_factor_recovery_codes), true) as $code)
                        <span class="inline-flex items-center p-2 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $code }}</span>
                    @endforeach
                    </div>
                    {{$this->getCachedAction('regenerate2fa')}}

                @endif

            @else

                <p class="text-lg font-medium text-gray-900 dark:text-white">{{ __('filament-breezy::default.profile.2fa.not_enabled.title') }}</p>
                {{ __('filament-breezy::default.profile.2fa.not_enabled.description') }}

            @endif
            <x-slot name="footer">
                @if($this->user->has_enabled_two_factor && $this->user->has_confirmed_two_factor)
                    <div class="flex items-center justify-between">
                        <x-filament::button color="secondary" wire:click="toggleRecoveryCodes">
                            {{$this->showing_two_factor_recovery_codes ? __('filament-breezy::default.profile.2fa.enabled.hide_codes') :__('filament-breezy::default.profile.2fa.enabled.show_codes')}}
                        </x-filament::button>
                        {{$this->getCachedAction('disable2fa')}}
                    </div>
                @elseif($this->user->has_enabled_two_factor)
                    <form wire:submit.prevent="confirmTwoFactor">
                        <div class="flex items-center justify-between">
                            <div>{{$this->confirmTwoFactorForm}}</div>
                            <div class="mt-5">
                                <x-filament::button type="submit">
                                    {{ __('filament-breezy::default.profile.2fa.actions.confirm_finish') }}
                                </x-filament::button>

                                <x-filament::button color="secondary" wire:click="disableTwoFactor">
                                    {{ __('filament-breezy::default.profile.2fa.actions.cancel_setup') }}
                                </x-filament::button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="text-right">
                        {{$this->getCachedAction('enable2fa')}}
                    </div>
                @endif
            </x-slot>
        </x-filament::card>

    </x-filament-breezy::grid-section>
    @endif

    @if(config('filament-breezy.enable_sanctum'))
    <x-filament::hr />

    <x-filament-breezy::grid-section class="mt-8">

        <x-slot name="title">
            {{ __('filament-breezy::default.profile.sanctum.title') }}
        </x-slot>

        <x-slot name="description">
            {{ __('filament-breezy::default.profile.sanctum.description') }}
        </x-slot>

        <div class="space-y-3">

            <form wire:submit.prevent="createApiToken" class="col-span-2 sm:col-span-1 mt-5 md:mt-0">

                <x-filament::card>
                    @if($plain_text_token)
                    <input type="text" disabled @class(['w-full py-1 px-3 rounded-lg bg-gray-100 border-gray-200',' dark:bg-gray-900 dark:border-gray-700'=>config('filament.dark_mode')]) name="plain_text_token" value="{{$plain_text_token}}" />
                    @endif

                    {{$this->createApiTokenForm}}

                    <div class="text-right">
                        <x-filament::button type="submit">
                            {{ __('filament-breezy::default.profile.sanctum.create.submit.label') }}
                        </x-filament::button>
                    </div>
                </x-filament::card>
            </form>

            <x-filament::hr />

            @livewire(\JeffGreco13\FilamentBreezy\Http\Livewire\BreezySanctumTokens::class)

        </div>
    </x-filament-breezy::grid-section>
    @endif

</x-filament::page>
