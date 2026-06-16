@extends('layouts.public')

@section('title', 'Global Currency Unit (GCU) | ' . config('brand.name', 'Zelta'))

@section('seo')
    @include('partials.seo', [
        'title' => 'Global Currency Unit (GCU)',
        'description' => 'The world\'s first democratically governed basket currency. Real bank backing, government insurance, community control. Join ' . config('brand.name', 'Zelta') . '\'s GCU today.',
        'keywords' => 'GCU, global currency unit, democratic banking, basket currency, ' . config('brand.name', 'Zelta') . ', stable currency, digital currency, community governance',
    ])

    {{-- Schema.org Markup --}}
    <x-schema type="gcu" />
    <x-schema type="breadcrumb" :data="[
        ['name' => 'Home', 'url' => url('/')],
        ['name' => 'Global Currency Unit', 'url' => url('/gcu')]
    ]" />
@endsection

@push('styles')
<style>
    .composition-bar {
        transition: width 1.5s ease-out;
    }
    .currency-card {
        transition: all 0.3s ease;
    }
    .currency-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }
</style>
@endpush

@section('content')

    <!-- Hero Section -->
    <section class="bg-fa-navy relative overflow-hidden">
        <div class="absolute inset-0 bg-grid-pattern"></div>
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/8 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-teal-500/6 rounded-full blur-[100px]"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-sm text-amber-400 mb-8">
                        <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>
                        Flagship Product
                    </div>
                    <h1 class="font-display text-5xl lg:text-6xl font-extrabold text-white tracking-tight mb-6">
                        Global Currency <span class="text-gradient">Unit (GCU)</span>
                    </h1>
                    <p class="text-lg text-slate-400 mb-8 max-w-xl">
                        A democratically governed basket currency backed by six reserve assets — USD, EUR, GBP, CHF, JPY, and gold — with stake-weighted governance and real bank backing.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 mb-12">
                        <a href="{{ route('register') }}" class="btn-primary px-8 py-4 text-base font-semibold">
                            Open GCU Account
                        </a>
                        <a href="#how-it-works" class="btn-outline px-8 py-4 text-base font-semibold">
                            How It Works
                        </a>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-3 gap-6">
                        <div class="bg-white/5 border border-white/[0.06] rounded-xl p-4">
                            <div class="text-3xl font-bold text-white">{{ count($compositionData['composition'] ?? config('platform.gcu.composition')) }}</div>
                            <div class="text-slate-400 text-sm mt-1">Currencies</div>
                        </div>
                        <div class="bg-white/5 border border-white/[0.06] rounded-xl p-4">
                            <div class="text-3xl font-bold text-white">€100k</div>
                            <div class="text-slate-400 text-sm mt-1">Insured/Bank</div>
                        </div>
                        <div class="bg-white/5 border border-white/[0.06] rounded-xl p-4">
                            <div class="text-3xl font-bold text-white">0.1%</div>
                            <div class="text-slate-400 text-sm mt-1">Transfer Fee</div>
                        </div>
                    </div>
                </div>

                <!-- GCU Symbol Card -->
                <div class="flex justify-center lg:justify-end">
                    <div class="bg-white rounded-2xl p-8 shadow-2xl w-full max-w-sm">
                        <div class="text-center mb-6">
                            <div class="text-7xl font-bold bg-clip-text text-transparent bg-gradient-to-br from-indigo-500 to-purple-600 mb-4">Ǥ</div>
                            <h3 class="text-2xl font-bold text-slate-900 mb-1">Global Currency Unit</h3>
                            <p class="text-slate-500 text-sm">Democratic finance infrastructure</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs text-slate-500 mb-1 text-center">Current Exchange Rate</p>
                            @php
                                $gcuValueUSD = 1.0975;
                                $usdToEur = 0.92;
                                $gcuValueEUR = $gcuValueUSD * $usdToEur;
                            @endphp
                            <p class="text-2xl font-bold text-indigo-600 text-center">1 Ǥ = €{{ number_format($gcuValueEUR, 4) }}</p>
                            <p class="text-xs text-slate-400 mt-1 text-center">Weighted basket value</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-blue-500/20 to-transparent"></div>
    </section>

    <!-- Basket Composition Section -->
    <section id="composition" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-slate-900 mb-4">Current Basket Composition</h2>
                <p class="text-lg text-slate-500 max-w-2xl mx-auto">Optimized for stability through community governance</p>
            </div>

            <!-- Performance Metrics -->
            @if(isset($compositionData['performance']))
            <div class="max-w-4xl mx-auto mb-12">
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-center">
                        <div>
                            <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider">Current Value</div>
                            <div class="text-2xl font-bold text-slate-900">Ǥ{{ number_format($compositionData['performance']['value'], 4) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider">24h Change</div>
                            <div class="text-2xl font-bold {{ $compositionData['performance']['change_24h'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $compositionData['performance']['change_24h'] >= 0 ? '+' : '' }}{{ number_format($compositionData['performance']['change_24h'], 2) }}%
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider">7d Change</div>
                            <div class="text-2xl font-bold {{ $compositionData['performance']['change_7d'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $compositionData['performance']['change_7d'] >= 0 ? '+' : '' }}{{ number_format($compositionData['performance']['change_7d'], 2) }}%
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider">30d Change</div>
                            <div class="text-2xl font-bold {{ $compositionData['performance']['change_30d'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $compositionData['performance']['change_30d'] >= 0 ? '+' : '' }}{{ number_format($compositionData['performance']['change_30d'], 2) }}%
                            </div>
                        </div>
                    </div>
                    @if(isset($compositionData['last_updated']))
                    <div class="text-center mt-4 text-xs text-slate-400">
                        Last updated: {{ \Carbon\Carbon::parse($compositionData['last_updated'])->diffForHumans() }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Composition Visual -->
            <div class="max-w-5xl mx-auto">
                <div class="grid lg:grid-cols-2 gap-12 items-center mb-12">
                    <!-- Pie Chart -->
                    <div class="card-feature !p-8">
                        <svg viewBox="0 0 200 200" class="w-full h-72">
                            @php
                                $composition = $compositionData['composition'] ?? config('platform.gcu.composition');
                                $colors = [
                                    'USD' => '#4f46e5',
                                    'EUR' => '#7c3aed',
                                    'GBP' => '#9333ea',
                                    'CHF' => '#0d9488',
                                    'JPY' => '#0891b2',
                                    'XAU' => '#d97706'
                                ];
                                $startAngle = 0;
                                $cx = 100;
                                $cy = 100;
                                $r = 80;
                            @endphp
                            @foreach($composition as $currency => $percentage)
                                @php
                                    $angle = ($percentage / 100) * 360;
                                    $endAngle = $startAngle + $angle;
                                    $largeArcFlag = $angle > 180 ? 1 : 0;
                                    $x1 = $cx + $r * cos(deg2rad($startAngle));
                                    $y1 = $cy + $r * sin(deg2rad($startAngle));
                                    $x2 = $cx + $r * cos(deg2rad($endAngle));
                                    $y2 = $cy + $r * sin(deg2rad($endAngle));
                                @endphp
                                <path d="M {{ $cx }} {{ $cy }} L {{ $x1 }} {{ $y1 }} A {{ $r }} {{ $r }} 0 {{ $largeArcFlag }} 1 {{ $x2 }} {{ $y2 }} Z"
                                      fill="{{ $colors[$currency] ?? '#6366f1' }}"
                                      class="hover:opacity-80 transition-opacity"
                                      stroke="white"
                                      stroke-width="2">
                                    <title>{{ $currency }}: {{ $percentage }}%</title>
                                </path>
                                @php $startAngle = $endAngle; @endphp
                            @endforeach
                            <circle cx="100" cy="100" r="50" fill="white" />
                            <text x="100" y="105" text-anchor="middle" dominant-baseline="middle" font-size="28" font-weight="bold" fill="#1e293b">Ǥ</text>
                        </svg>
                        <p class="text-center text-slate-500 text-sm mt-2">Optimised Currency Basket</p>
                    </div>

                    <!-- Composition List -->
                    <div class="space-y-4">
                        @php
                            $flags = ['USD' => '🇺🇸', 'EUR' => '🇪🇺', 'GBP' => '🇬🇧', 'CHF' => '🇨🇭', 'JPY' => '🇯🇵', 'XAU' => '🏆'];
                            $names = ['USD' => 'US Dollar', 'EUR' => 'Euro', 'GBP' => 'British Pound', 'CHF' => 'Swiss Franc', 'JPY' => 'Japanese Yen', 'XAU' => 'Gold (Troy Oz)'];
                        @endphp

                        @foreach($composition as $currency => $percentage)
                        <div class="currency-card bg-slate-50 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">{{ $flags[$currency] ?? '' }}</span>
                                    <div>
                                        <h4 class="font-semibold text-slate-900 text-sm">{{ $names[$currency] ?? $currency }}</h4>
                                        <p class="text-xs text-slate-500">{{ $currency }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xl font-bold text-slate-900">{{ $percentage }}%</span>
                                    @if(isset($compositionData['assets'][$currency]['price_change']))
                                    <p class="text-xs {{ $compositionData['assets'][$currency]['price_change'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $compositionData['assets'][$currency]['price_change'] >= 0 ? '+' : '' }}{{ number_format($compositionData['assets'][$currency]['price_change'], 2) }}% today
                                    </p>
                                    @endif
                                </div>
                            </div>
                            <div class="relative h-2 bg-slate-200 rounded-full overflow-hidden">
                                <div class="composition-bar absolute inset-0 rounded-full"
                                     style="width: {{ $percentage }}%; background-color: {{ $colors[$currency] ?? '#6366f1' }}"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Voting Information -->
                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-8 text-center">
                    @if(config('platform.gcu.voting_enabled'))
                        @php
                            $nextVoting = \Carbon\Carbon::parse(config('platform.gcu.next_voting_date'));
                            $daysUntil = now()->diffInDays($nextVoting);
                        @endphp
                        <h3 class="text-2xl font-bold text-slate-900 mb-4">Next Composition Vote</h3>
                        <div class="flex justify-center space-x-8 mb-6">
                            <div>
                                <div class="text-4xl font-bold text-indigo-600">{{ $daysUntil }}</div>
                                <div class="text-slate-500 text-sm">Days</div>
                            </div>
                            <div>
                                <div class="text-4xl font-bold text-indigo-600">{{ now()->diffInHours($nextVoting) % 24 }}</div>
                                <div class="text-slate-500 text-sm">Hours</div>
                            </div>
                            <div>
                                <div class="text-4xl font-bold text-indigo-600">{{ now()->diffInMinutes($nextVoting) % 60 }}</div>
                                <div class="text-slate-500 text-sm">Minutes</div>
                            </div>
                        </div>
                        <a href="{{ route('gcu.voting.index') }}" class="btn-primary px-6 py-3">
                            View Proposals
                        </a>
                    @else
                        <h3 class="text-2xl font-bold text-slate-900 mb-6">Democratic Voting — Coming Soon</h3>
                        <div class="grid md:grid-cols-2 gap-6 text-left max-w-2xl mx-auto mb-6">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-900 text-sm">Monthly Composition Votes</h4>
                                    <p class="text-slate-500 text-sm">Vote on currency weights every month</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-900 text-sm">Asset-Weighted Voting Power</h4>
                                    <p class="text-slate-500 text-sm">1 GCU = 1 vote, aligned incentives</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-900 text-sm">Transparent Verification</h4>
                                    <p class="text-slate-500 text-sm">Cryptographically signed, publicly verifiable</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center mt-0.5">
                                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-slate-900 text-sm">Automatic Rebalancing</h4>
                                    <p class="text-slate-500 text-sm">Executed across all partner banks on the 1st</p>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-indigo-600 font-medium">
                            Voting API implemented and ready. Web interface launching Q3 2025.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-slate-900 mb-4">How GCU Works</h2>
                <p class="text-lg text-slate-500 max-w-2xl mx-auto">
                    A professional approach to global currency that puts control in the community's hands
                </p>
            </div>

            <!-- Process Steps -->
            <div class="grid md:grid-cols-4 gap-8 mb-20">
                @php
                    $steps = [
                        ['number' => '1', 'color' => 'indigo', 'title' => 'Deposit Funds', 'description' => 'Convert any currency to GCU at transparent exchange rates'],
                        ['number' => '2', 'color' => 'purple', 'title' => 'Bank Storage', 'description' => 'Funds distributed across ' . config('platform.statistics.banking_partners') . ' insured banks'],
                        ['number' => '3', 'color' => 'teal', 'title' => 'Use Globally', 'description' => 'Send, receive, and spend anywhere in the world'],
                        ['number' => '4', 'color' => 'green', 'title' => 'Vote Monthly', 'description' => 'Shape the currency composition with your vote'],
                    ];
                @endphp
                @foreach($steps as $step)
                <div class="text-center">
                    <div class="w-16 h-16 bg-{{ $step['color'] }}-100 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-{{ $step['color'] }}-200">
                        <span class="text-xl font-bold text-{{ $step['color'] }}-600">{{ $step['number'] }}</span>
                    </div>
                    <h3 class="font-semibold text-slate-900 mb-2">{{ $step['title'] }}</h3>
                    <p class="text-slate-500 text-sm">{{ $step['description'] }}</p>
                </div>
                @endforeach
            </div>

            <!-- Detail Cards -->
            <div class="grid lg:grid-cols-2 gap-8">
                <div class="card-feature !p-8">
                    <h3 class="text-2xl font-bold text-slate-900 mb-6">The Banking Layer</h3>
                    <div class="space-y-5">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-900 mb-1">Multi-Bank Distribution</h4>
                                <p class="text-slate-500 text-sm">Funds distributed across {{ config('platform.statistics.banking_partners') }} regulated European banks, each providing €100,000 deposit insurance.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-900 mb-1">Government Protection</h4>
                                <p class="text-slate-500 text-sm">Each bank partner is regulated and provides government-backed deposit insurance.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-900 mb-1">Real-Time Reporting</h4>
                                <p class="text-slate-500 text-sm">Full transparency with real-time reporting of reserves, allocations, and bank balances.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-feature !p-8">
                    <h3 class="text-2xl font-bold text-slate-900 mb-6">The Voting System</h3>
                    <div class="space-y-5">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-900 mb-1">One GCU, One Vote</h4>
                                <p class="text-slate-500 text-sm">Your voting power is proportional to your GCU holdings — aligned incentives at every level.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-900 mb-1">Expert Proposals</h4>
                                <p class="text-slate-500 text-sm">Economic experts submit data-driven proposals for optimal currency composition.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-900 mb-1">Automatic Execution</h4>
                                <p class="text-slate-500 text-sm">Winning proposals execute automatically, rebalancing the basket across all partner banks.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Grid -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-slate-900 mb-4">Why Choose GCU?</h2>
                <p class="text-lg text-slate-500">Built for the future, secured by tradition</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $benefits = [
                        ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'color' => 'indigo', 'title' => 'Unmatched Stability', 'description' => 'Multi-currency basket design protects against single currency volatility and economic shocks'],
                        ['icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'color' => 'purple', 'title' => 'Bank-Level Security', 'description' => 'Funds secured in regulated banks with €100,000 government insurance per institution'],
                        ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'teal', 'title' => 'True Democracy', 'description' => 'Every GCU holder has a voice in shaping the currency through monthly governance votes'],
                        ['icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'blue', 'title' => 'Global Acceptance', 'description' => 'Instant conversion to any local currency with transparent, competitive exchange rates'],
                        ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'amber', 'title' => 'Lightning Fast', 'description' => 'Send GCU anywhere in the world in seconds, not days, with minimal fees'],
                        ['icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'color' => 'emerald', 'title' => 'Full Transparency', 'description' => 'Real-time visibility into reserves, voting results, and all system operations'],
                    ];
                @endphp

                @foreach($benefits as $benefit)
                <div class="card-feature !p-8">
                    <div class="w-12 h-12 bg-{{ $benefit['color'] }}-100 rounded-lg flex items-center justify-center mb-5">
                        <svg class="w-6 h-6 text-{{ $benefit['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $benefit['icon'] }}"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $benefit['title'] }}</h3>
                    <p class="text-slate-500 text-sm">{{ $benefit['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Use Cases -->
    <section class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-slate-900 mb-4">Use GCU For Everything</h2>
                <p class="text-lg text-slate-500">From daily transactions to international business</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                @php
                    $useCases = [
                        [
                            'title' => 'Personal Banking',
                            'description' => 'Use GCU for everyday purchases, savings, and personal transfers with minimal fees.',
                            'items' => ['Instant payments', 'Mobile app access', 'Debit card support'],
                            'color' => 'indigo',
                        ],
                        [
                            'title' => 'International Trade',
                            'description' => 'Eliminate currency risk in global business with a stable, multi-currency unit.',
                            'items' => ['No exchange risk', 'B2B settlements', 'API integration'],
                            'color' => 'purple',
                        ],
                        [
                            'title' => 'Treasury Management',
                            'description' => 'Optimise corporate treasuries with a naturally hedged global currency.',
                            'items' => ['Risk mitigation', 'Yield optimisation', 'Reporting tools'],
                            'color' => 'teal',
                        ],
                    ];
                @endphp
                @foreach($useCases as $useCase)
                <div class="card-feature overflow-hidden !p-0">
                    <div class="h-3 bg-{{ $useCase['color'] }}-500"></div>
                    <div class="p-8">
                        <h3 class="text-xl font-bold text-slate-900 mb-3">{{ $useCase['title'] }}</h3>
                        <p class="text-slate-500 mb-6 text-sm">{{ $useCase['description'] }}</p>
                        <ul class="space-y-2">
                            @foreach($useCase['items'] as $item)
                            <li class="flex items-center gap-2 text-sm text-slate-600">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                {{ $item }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Trusted Banking Partners -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="font-display text-3xl md:text-4xl font-bold text-slate-900 mb-4">Trusted Banking Partners</h2>
                <p class="text-lg text-slate-500">Your funds secured with Europe's most trusted institutions</p>
            </div>

            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-10">
                <div class="grid md:grid-cols-3 gap-8 mb-10 text-center">
                    <div>
                        <div class="text-4xl font-bold text-indigo-600 mb-1">{{ config('platform.statistics.banking_partners') }}</div>
                        <p class="text-slate-500 text-sm">Partner Banks</p>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-purple-600 mb-1">€300k</div>
                        <p class="text-slate-500 text-sm">Total Insurance Coverage</p>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-teal-600 mb-1">100%</div>
                        <p class="text-slate-500 text-sm">Regulatory Compliance</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 border border-slate-100">
                    <h3 class="font-semibold text-slate-900 mb-4">Security Features</h3>
                    <div class="grid md:grid-cols-2 gap-3">
                        @foreach(['€100,000 deposit insurance per bank', 'PSD2 compliant infrastructure', 'Real-time transaction monitoring', 'Multi-factor authentication'] as $feature)
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $feature }}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-fa-navy relative overflow-hidden">
        <div class="absolute inset-0 bg-dot-pattern"></div>
        <div class="relative max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8 py-20">
            <h2 class="font-display text-3xl md:text-4xl font-bold text-white mb-4">Ready to Join the Revolution?</h2>
            <p class="text-lg text-slate-400 mb-10 max-w-2xl mx-auto">
                Be part of the first truly democratic global currency
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="btn-primary px-8 py-4 text-base">
                    Create Free Account
                </a>
                <a href="{{ route('platform') }}" class="btn-outline px-8 py-4 text-base">
                    Learn About Platform
                </a>
            </div>
            <div class="mt-10 text-sm text-slate-500">
                Questions? Contact our team at <a href="mailto:{{ config('brand.support_email', 'info@zelta.app') }}" class="underline hover:text-slate-300 transition-colors">{{ config('brand.support_email', 'info@zelta.app') }}</a>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const match = entry.target.getAttribute('style').match(/width: ([\d.]+%)/);
                    if (match) {
                        entry.target.style.width = match[1];
                    }
                }
            });
        });

        document.querySelectorAll('.composition-bar').forEach(bar => {
            observer.observe(bar);
        });
    });
</script>
@endpush
