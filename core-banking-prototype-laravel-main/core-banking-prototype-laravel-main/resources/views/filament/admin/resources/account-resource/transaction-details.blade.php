<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaction ID</p>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->uuid }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</p>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ ucfirst(str_replace('_', ' ', $transaction->type)) }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Amount</p>
            <p class="text-sm text-gray-900 dark:text-gray-100">${{ number_format($transaction->amount / 100, 2) }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Balance After</p>
            <p class="text-sm text-gray-900 dark:text-gray-100">${{ number_format($transaction->balance_after / 100, 2) }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Reference</p>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->reference ?? 'N/A' }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</p>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $transaction->created_at->format('M j, Y g:i:s A') }}</p>
        </div>
    </div>
    <div>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaction Hash</p>
        <p class="text-xs text-gray-900 dark:text-gray-100 font-mono break-all">{{ $transaction->hash }}</p>
    </div>
</div>