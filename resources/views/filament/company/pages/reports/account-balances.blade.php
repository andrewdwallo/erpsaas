<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <x-filament-tables::container>
            <div class="p-6 divide-y divide-gray-200 dark:divide-white/5">
                <form wire:submit.prevent="loadAccountBalances" class="w-full">
                    <div class="flex flex-col md:flex-row items-end justify-center gap-4 md:gap-6">
                        <div class="flex-grow">
                            {{ $this->form }}
                        </div>
                        <x-filament::button type="submit" class="mt-4 md:mt-0">
                            Update Report
                        </x-filament::button>
                    </div>
                </form>
            </div>
            <div class="divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 dark:border-t-white/10">
                <table class="w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <x-filament-tables::header-cell>Account</x-filament-tables::header-cell>
                            <x-filament-tables::header-cell alignment="end">Starting Balance</x-filament-tables::header-cell>
                            <x-filament-tables::header-cell alignment="end">Debit</x-filament-tables::header-cell>
                            <x-filament-tables::header-cell alignment="end">Credit</x-filament-tables::header-cell>
                            <x-filament-tables::header-cell alignment="end">Net Movement</x-filament-tables::header-cell>
                            <x-filament-tables::header-cell alignment="end">Ending Balance</x-filament-tables::header-cell>
                        </tr>
                    </thead>
                    @foreach($accountBalanceReport->categories as $accountCategoryName => $accountCategory)
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <x-filament-tables::cell colspan="6">
                                <div class="px-3 py-2 text-sm font-medium text-gray-950 dark:text-white">{{ $accountCategoryName }}</div>
                            </x-filament-tables::cell>
                        </tr>
                        @foreach($accountCategory->accounts as $account)
                            <x-filament-tables::row>
                                <x-filament-tables::cell><div class="px-3 py-4 text-sm leading-6 text-gray-950 dark:text-white">{{ $account->accountName }}</div></x-filament-tables::cell>
                                <x-filament-tables::cell class="text-right"><div class="px-3 py-4 text-sm leading-6 text-gray-950 dark:text-white">{{ $account->balance->startingBalance ?? '' }}</div></x-filament-tables::cell>
                                <x-filament-tables::cell class="text-right"><div class="px-3 py-4 text-sm leading-6 text-gray-950 dark:text-white">{{ $account->balance->debitBalance }}</div></x-filament-tables::cell>
                                <x-filament-tables::cell class="text-right"><div class="px-3 py-4 text-sm leading-6 text-gray-950 dark:text-white">{{ $account->balance->creditBalance }}</div></x-filament-tables::cell>
                                <x-filament-tables::cell class="text-right"><div class="px-3 py-4 text-sm leading-6 text-gray-950 dark:text-white">{{ $account->balance->netMovement }}</div></x-filament-tables::cell>
                                <x-filament-tables::cell class="text-right"><div class="px-3 py-4 text-sm leading-6 text-gray-950 dark:text-white">{{ $account->balance->endingBalance ?? '' }}</div></x-filament-tables::cell>
                            </x-filament-tables::row>
                        @endforeach
                        <x-filament-tables::row>
                            <x-filament-tables::cell><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white">Total {{ $accountCategoryName }}</div></x-filament-tables::cell>
                            <x-filament-tables::cell class="text-right"><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white">{{ $accountCategory->summary->startingBalance ?? '' }}</div></x-filament-tables::cell>
                            <x-filament-tables::cell class="text-right"><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white">{{ $accountCategory->summary->debitBalance }}</div></x-filament-tables::cell>
                            <x-filament-tables::cell class="text-right"><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white">{{ $accountCategory->summary->creditBalance }}</div></x-filament-tables::cell>
                            <x-filament-tables::cell class="text-right"><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white">{{ $accountCategory->summary->netMovement }}</div></x-filament-tables::cell>
                            <x-filament-tables::cell class="text-right"><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white">{{ $accountCategory->summary->endingBalance ?? '' }}</div></x-filament-tables::cell>
                        </x-filament-tables::row>
                        <x-filament-tables::row>
                            <x-filament-tables::cell colspan="6">
                                <div class="px-3 py-2 invisible">Hidden Text</div>
                            </x-filament-tables::cell>
                        </x-filament-tables::row>
                        </tbody>
                    @endforeach
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <x-filament-tables::cell><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white">Total for all accounts</div></x-filament-tables::cell>
                            <x-filament-tables::cell><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white"></div></x-filament-tables::cell>
                            <x-filament-tables::cell class="text-right"><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white">{{ $accountBalanceReport->overallTotal->debitBalance }}</div></x-filament-tables::cell>
                            <x-filament-tables::cell class="text-right"><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white">{{ $accountBalanceReport->overallTotal->creditBalance }}</div></x-filament-tables::cell>
                            <x-filament-tables::cell><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white"></div></x-filament-tables::cell>
                            <x-filament-tables::cell><div class="px-3 py-2 text-sm leading-6 font-semibold text-gray-950 dark:text-white"></div></x-filament-tables::cell>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="es-table__footer-ctn border-t border-gray-200"></div>
        </x-filament-tables::container>
    </div>
</x-filament-panels::page>
