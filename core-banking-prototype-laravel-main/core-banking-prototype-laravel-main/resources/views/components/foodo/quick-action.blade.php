@props([
    'icon',
    'title',
    'description',
    'color' => 'red',
])

@php
    $colorClasses = [
        'red'    => ['bg' => 'bg-red-50',    'text' => 'text-red-500',    'hoverBorder' => 'hover:border-red-300',    'hoverChevron' => 'group-hover:text-red-400'],
        'blue'   => ['bg' => 'bg-blue-50',   'text' => 'text-blue-500',   'hoverBorder' => 'hover:border-blue-300',   'hoverChevron' => 'group-hover:text-blue-400'],
        'orange' => ['bg' => 'bg-orange-50',  'text' => 'text-orange-500', 'hoverBorder' => 'hover:border-orange-300', 'hoverChevron' => 'group-hover:text-orange-400'],
        'green'  => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-500','hoverBorder' => 'hover:border-emerald-300','hoverChevron' => 'group-hover:text-emerald-400'],
    ];
    $c = $colorClasses[$color] ?? $colorClasses['red'];
@endphp

<div class="bg-white p-3 rounded-xl border border-stone-200 flex gap-3 items-center group cursor-pointer {{ $c['hoverBorder'] }} transition-colors">
    <div class="w-10 h-10 rounded-lg {{ $c['bg'] }} {{ $c['text'] }} flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined foodo-icon">{{ $icon }}</span>
    </div>
    <div class="flex-1">
        <h4 class="text-xs font-bold text-stone-800">{{ $title }}</h4>
        <p class="text-[10px] text-stone-500 leading-tight mt-0.5">{{ $description }}</p>
    </div>
    <span class="material-symbols-outlined text-stone-300 {{ $c['hoverChevron'] }} text-sm foodo-icon-thin">chevron_right</span>
</div>
