<div class="bg-primary-300/10 border border-primary-200 p-4 rounded-lg">
    <div class="grid gap-3 items-end text-center">
        <div class="text-sm">
            <div class="text-gray-600 mb-2">Total Debits</div>
            <strong class="text-lg">{{ $debitAmount }}</strong>
        </div>
        <div class="flex items-center justify-center px-2">
            <strong class="text-lg">{!! $isJournalBalanced ? '=' : '&ne;' !!}</strong>
        </div>
        <div class="text-sm">
            <div class="mb-2 text-gray-600">Total Credits</div>
            <strong class="text-lg">{{ $creditAmount }}</strong>
        </div>
        <div class="col-span-3 text-sm text-gray-600">
            <div class="flex justify-center items-center space-x-2">
                <span>Difference:</span>
                <span
                    @class([
                        'text-lg',
                        'text-success-600' => $isJournalBalanced,
                        'text-warning-600' => ! $isJournalBalanced,
                    ])
                >
                    {{ $difference }}
                </span>
            </div>
        </div>
    </div>
</div>
