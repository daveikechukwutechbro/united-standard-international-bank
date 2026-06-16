<div class="space-y-4">
    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Type</h3>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $delivery->event_type }}</p>
    </div>

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
        <p class="mt-1">
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                @if($delivery->status === 'delivered') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                @elseif($delivery->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                @else bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100
                @endif">
                {{ ucfirst($delivery->status) }}
            </span>
        </p>
    </div>

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Payload</h3>
        <pre class="mt-1 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs overflow-x-auto">{{ json_encode($delivery->payload, JSON_PRETTY_PRINT) }}</pre>
    </div>

    @if($delivery->response_status)
    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Response Status</h3>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $delivery->response_status }}</p>
    </div>
    @endif

    @if($delivery->response_body)
    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Response Body</h3>
        <pre class="mt-1 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs overflow-x-auto">{{ $delivery->response_body }}</pre>
    </div>
    @endif

    @if($delivery->error_message)
    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Error Message</h3>
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $delivery->error_message }}</p>
    </div>
    @endif

    @if($delivery->duration_ms)
    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</h3>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $delivery->duration_ms }}ms</p>
    </div>
    @endif

    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $delivery->created_at->format('M j, Y g:i:s A') }}</p>
        </div>
        
        @if($delivery->delivered_at)
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Delivered At</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $delivery->delivered_at->format('M j, Y g:i:s A') }}</p>
        </div>
        @endif
    </div>

    @if($delivery->next_retry_at && $delivery->status === 'failed')
    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Next Retry At</h3>
        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $delivery->next_retry_at->format('M j, Y g:i:s A') }}</p>
    </div>
    @endif
</div>