<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <x-filament::tabs>
            @foreach($this->categories as $categoryValue => $subtypes)
                <x-filament::tabs.item
                    wire:key="tab-item-{{ $categoryValue }}"
                    :active="$activeTab === $categoryValue"
                    wire:click="$set('activeTab', '{{ $categoryValue }}')"
                    :badge="$subtypes->sum('accounts_count')"
                >
                    {{ $this->getCategoryLabel($categoryValue) }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        @foreach($this->categories as $categoryValue => $subtypes)
            @if($activeTab === $categoryValue)
                <div class="es-table__container overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
                    <div class="es-table__header-ctn"></div>
                    <div class="es-table__content overflow-x-auto">
                        <table class="es-table table-fixed w-full divide-y divide-gray-200 text-start text-sm dark:divide-white/5">
                            <colgroup>
                                <col span="1" style="width: 12.5%;">
                                <col span="1" style="width: 25%;">
                                <col span="1" style="width: 40%;">
                                <col span="1" style="width: 15%;">
                                <col span="1" style="width: 7.5%;">
                            </colgroup>
                            @foreach($subtypes as $subtype)
                                <tbody class="es-table__rowgroup divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                                <!-- Subtype Name Header Row -->
                                <tr class="es-table__row--header bg-gray-50 dark:bg-white/5">
                                    <td colspan="5" class="es-table__cell px-4 py-4">
                                        <div class="es-table__row-content flex items-center space-x-2">
                                            <span class="es-table__row-title text-gray-800 dark:text-gray-200 font-semibold tracking-wider">
                                                {{ $subtype->name }}
                                            </span>
                                            <x-tooltip
                                                text="{!! $subtype->description !!}"
                                                icon="heroicon-o-question-mark-circle"
                                                placement="right"
                                                maxWidth="300"
                                            />
                                        </div>
                                    </td>
                                </tr>

                                <!-- Chart Rows -->
                                @forelse($subtype->accounts as $account)
                                <tr class="es-table__row">
                                    <td colspan="1" class="es-table__cell px-4 py-4">{{ $account->code }}</td>
                                    <td colspan="1" class="es-table__cell px-4 py-4">{{ $account->name }}</td>
                                    <td colspan="1" class="es-table__cell px-4 py-4">{{ $account->description }}</td>
                                    <td colspan="1" class="es-table__cell px-4 py-4">@money($account->ending_balance, $account->currency_code, true)</td>
                                    <td colspan="1" class="es-table__cell px-4 py-4">
                                        <div>
                                            @if($account->default === false)
                                                {{ ($this->editChartAction)(['chart' => $account->id]) }}
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <!-- No Accounts Available Row -->
                                <tr class="es-table__row">
                                    <td colspan="5" class="es-table__cell px-4 py-4 italic">
                                        {{ __("You haven't added any {$subtype->name} accounts yet.") }}
                                    </td>
                                </tr>
                                @endforelse

                                <!-- Add New Account Row -->
                                <tr class="es-table__row">
                                    <td colspan="5" class="es-table__cell px-4 py-4">
                                        {{ ($this->createChartAction)(['subtype' => $subtype->id]) }}
                                    </td>
                                </tr>
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                    <div class="es-table__footer-ctn border-t border-gray-200"></div>
                </div>
            @endif
        @endforeach
    </div>
</x-filament-panels::page>
