<div class="rounded-lg bg-primary-300/10 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 overflow-hidden">
    <div class="flex items-center p-4 gap-x-2">
        @if($connectedBankAccount->institution->logo_url)
            <img src="{{ $connectedBankAccount->institution->logo_url }}" alt="{{ $connectedBankAccount->institution->name }}" class="h-10">
        @else
            <div class="flex-shrink-0 bg-platinum p-2 rounded-full dark:bg-gray-500/20">
                <x-filament::icon
                    icon="heroicon-o-building-library"
                    class="h-6 w-6 text-gray-500 dark:text-gray-400"
                />
            </div>
        @endif
        <div>
            <p class="text-sm font-medium leading-6 text-gray-900 dark:text-white">{{ $connectedBankAccount->institution->name }}</p>
            <p class="text-sm leading-6 text-gray-600 dark:text-gray-200">{{ ucwords($connectedBankAccount->subtype) }} {{ $connectedBankAccount->masked_number }}</p>
        </div>
    </div>
</div>
