<div class="flex items-center space-x-2 text-sm leading-6">
    <span>{{ $getState() }}</span>
    @if ($getRecord()->income_account_id && $getRecord()->expense_account_id)
        <x-filament::badge>
            Sell & Buy
        </x-filament::badge>
    @endif
</div>
