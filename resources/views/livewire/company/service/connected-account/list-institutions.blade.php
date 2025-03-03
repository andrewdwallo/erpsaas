<div>
    <div class="grid grid-cols-1 gap-4">
        @forelse($this->connectedInstitutions as $institution)
            <section class="connected-account-section overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <header class="connected-account-header bg-primary-300/10 px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
                    @if($institution->logo_url === null)
                        <div class="flex-shrink-0 bg-platinum p-2 rounded-full dark:bg-gray-500/20">
                            <x-filament::icon
                                icon="heroicon-o-building-library"
                                class="h-6 w-6 text-gray-500 dark:text-gray-400"
                            />
                        </div>
                    @else
                        <img
                            src="{{ $institution->logo_url }}"
                            alt="{{ $institution->name }}"
                            class="h-10 object-contain object-left"
                        >
                    @endif

                    <div class="flex-auto">
                        <h3 class="connected-account-section-header-heading text-lg font-semibold leading-6 text-gray-950 dark:text-white">
                            {{ $institution->name }}
                        </h3>

                        @if($institution->latestImport)
                            <p class="connected-account-section-header-description text-sm leading-6 text-gray-500 dark:text-gray-400">
                                {{ __('Last updated') }} {{ $institution->latestImport->last_imported_at->diffForHumans() }}
                            </p>
                        @endif
                    </div>

                    {{-- Refresh Transactions --}}
                    @if($institution->getEnabledConnectedBankAccounts()->isNotEmpty())
                        {{ ($this->refreshTransactions)(['institution' => $institution->id]) }}
                    @endif

                    {{-- Delete Institution --}}
                    {{ ($this->deleteBankConnection)(['institution' => $institution->id]) }}
                </header>

                @foreach($institution->connectedBankAccounts as $connectedBankAccount)
                    @php
                        $account = $connectedBankAccount->bankAccount?->account;
                    @endphp
                    <div class="border-t-2 border-gray-200 dark:border-white/10 px-6 py-4">
                        <div class="flex flex-col sm:flex-row items-start gap-y-2">
                            <div class="grid flex-auto gap-y-2">
                                <span class="account-name text-base font-medium leading-6 text-gray-900 dark:text-white">
                                    {{ $connectedBankAccount->name }}
                                </span>
                                <span class="account-type text-sm leading-6 text-gray-600 dark:text-gray-200">
                                    {{  ucwords($connectedBankAccount->subtype) }} {{ $connectedBankAccount->masked_number }}
                                </span>
                            </div>

                            @if($account?->ending_balance)
                                <div class="account-balance flex text-base leading-6 text-gray-700 dark:text-gray-200 space-x-1">
                                    <strong wire:poll.visible>{{ $account->ending_balance->format() }}</strong>
                                    <p>{{ $account->currency_code }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Add the toggle button to import transactions or not --}}
                        <div class="mt-4">
                            @if($connectedBankAccount->import_transactions)
                                {{ ($this->stopImportingTransactions)(['connectedBankAccount' => $connectedBankAccount->id]) }}
                            @else
                                {{ ($this->startImportingTransactions)(['connectedBankAccount' => $connectedBankAccount->id]) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </section>
        @empty
            <section class="connected-account-section overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="px-6 py-12 text-center">
                    <div class="connected-account-empty-state-content mx-auto grid max-w-lg justify-items-center text-center">
                        <div class="connected-account-empty-state-icon-ctn mb-4 rounded-full bg-platinum p-3 dark:bg-gray-500/20">
                            <x-filament::icon
                                icon="heroicon-o-x-mark"
                                class="connected-account-empty-state-icon h-6 w-6 text-gray-500 dark:text-gray-400"
                            />
                        </div>
                        <h4 class="connected-account-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            {{ __('No connected accounts') }}
                        </h4>
                        <p class="connected-account-empty-state-description text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('Connect your bank account to get started.') }}
                        </p>
                        <div class="connected-account-empty-state-action flex shrink-0 items-center gap-3 flex-wrap justify-center mt-6">
                            <x-filament::button
                                wire:click="$dispatch('createToken')"
                                wire:loading.attr="disabled"
                            >
                                {{ __('Connect account') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </section>
        @endforelse

        <x-filament-actions::modals/>
    </div>
    {{-- Include Plaid's JavaScript SDK CDN --}}
    @assets
    <script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
    @endassets

    {{-- Include the Plaid script --}}
    @script
    <script>
        {{-- Adjust the modal width based on the screen size --}}
        const mobileSize = window.matchMedia("(max-width: 480px)");

        let data = Alpine.reactive({windowWidth: 'max-w-2xl'});

        // Add a media query change listener
        mobileSize.addEventListener('change', (e) => {
            data.windowWidth = e.matches ? 'screen' : 'max-w-2xl';
        });

        Alpine.effect(() => {
            $wire.$set('modalWidth', data.windowWidth);
        });

        {{-- Initialize Plaid Link --}}
        $wire.on('initializeLink', token => {
            const handler = Plaid.create({
                token: token,
                onSuccess: (publicToken, metadata) => {
                    $wire.dispatchSelf('linkSuccess', {publicToken: publicToken, metadata: metadata});
                },
                onExit: (err, metadata) => {
                },
                onEvent: (eventName, metadata) => {
                },
            });

            handler.open();
        });
    </script>
    @endscript
</div>
