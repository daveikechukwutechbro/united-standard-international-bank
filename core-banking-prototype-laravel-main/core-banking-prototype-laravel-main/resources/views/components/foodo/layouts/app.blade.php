<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>{{ $title ?? 'Foodo Insights' }} — Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/foodo.css'])
    @stack('head')
</head>
<body class="flex min-h-screen font-foodo text-foodo-text overflow-hidden foodo-bg" x-data="{ sidebarOpen: false }">
    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/50 lg:hidden" x-cloak></div>

    {{-- Sidebar --}}
    <x-foodo.sidebar :active="$activePage ?? 'dashboard'" />

    {{-- Main --}}
    <main class="lg:ml-64 flex-1 h-screen overflow-y-auto foodo-scrollbar {{ ($fullHeight ?? false) ? 'flex flex-col' : 'p-6 lg:p-8' }}">
        {{-- Mobile top bar --}}
        <div class="lg:hidden flex items-center justify-between p-4 bg-white border-b border-stone-200 shrink-0 {{ ($fullHeight ?? false) ? '' : '-mx-6 -mt-6 mb-6' }}">
            <button @click="sidebarOpen = true" class="w-10 h-10 rounded-lg bg-stone-100 flex items-center justify-center">
                <span class="material-symbols-outlined foodo-icon">menu</span>
            </button>
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 bg-foodo-primary rounded-lg flex items-center justify-center text-white">
                    <span class="font-black text-sm">F</span>
                </div>
                <span class="font-black text-sm">Foodo</span>
            </div>
            <div class="w-10"></div>
        </div>

        {{ $slot }}

        @if(!($fullHeight ?? false))
            <footer class="pt-8 pb-12 text-center text-[10px] font-black text-stone-400 tracking-[0.4em] uppercase">
                Foodo Insights &copy; 2025-2026 — Command Center
            </footer>
        @endif
    </main>

    @stack('scripts')
</body>
</html>
