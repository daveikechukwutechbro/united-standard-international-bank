@php
    $componentValues = $getState();
    if (is_string($componentValues)) {
        $componentValues = json_decode($componentValues, true);
    }
@endphp

@if($componentValues && is_array($componentValues))
    <div class="space-y-1">
        @foreach($componentValues as $asset => $data)
            <div class="text-xs">
                <span class="font-medium">{{ $asset }}:</span>
                <span class="text-gray-600">${{ number_format($data['value'] ?? 0, 4) }}</span>
                <span class="text-gray-500">({{ number_format($data['weight'] ?? 0, 2) }}%)</span>
            </div>
        @endforeach
    </div>
@else
    <span class="text-gray-400 text-xs">No component data</span>
@endif