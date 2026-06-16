@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-4">Fund Flow Visualization</h1>
            
            <!-- Filters -->
            <div class="mb-6">
                <form method="GET" action="{{ route('fund-flow.index') }}" class="flex gap-4">
                    <select name="period" class="rounded-md border-gray-300">
                        <option value="24hours" {{ request('period') == '24hours' ? 'selected' : '' }}>Last 24 Hours</option>
                        <option value="7days" {{ request('period', '7days') == '7days' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30days" {{ request('period') == '30days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="90days" {{ request('period') == '90days' ? 'selected' : '' }}>Last 90 Days</option>
                    </select>
                    
                    <select name="account" class="rounded-md border-gray-300">
                        <option value="all">All Accounts</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->uuid }}" {{ request('account') == $account->uuid ? 'selected' : '' }}>
                                {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                    
                    <select name="flow_type" class="rounded-md border-gray-300">
                        <option value="all">All Types</option>
                        <option value="deposit" {{ request('flow_type') == 'deposit' ? 'selected' : '' }}>Deposits</option>
                        <option value="withdrawal" {{ request('flow_type') == 'withdrawal' ? 'selected' : '' }}>Withdrawals</option>
                        <option value="transfer" {{ request('flow_type') == 'transfer' ? 'selected' : '' }}>Transfers</option>
                    </select>
                    
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Filter</button>
                </form>
            </div>
            
            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 p-4 rounded">
                    <h3 class="text-sm font-medium text-gray-500">Total Inflow</h3>
                    <p class="text-2xl font-bold text-green-600">${{ number_format($statistics->total_inflow / 100, 2) }}</p>
                    <p class="text-xs text-gray-500">Avg: ${{ number_format($statistics->avg_daily_inflow / 100, 2) }}/day</p>
                </div>
                
                <div class="bg-gray-50 p-4 rounded">
                    <h3 class="text-sm font-medium text-gray-500">Total Outflow</h3>
                    <p class="text-2xl font-bold text-red-600">${{ number_format($statistics->total_outflow / 100, 2) }}</p>
                    <p class="text-xs text-gray-500">Avg: ${{ number_format($statistics->avg_daily_outflow / 100, 2) }}/day</p>
                </div>
                
                <div class="bg-gray-50 p-4 rounded">
                    <h3 class="text-sm font-medium text-gray-500">Net Flow</h3>
                    <p class="text-2xl font-bold {{ $statistics->net_flow >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        ${{ number_format($statistics->net_flow / 100, 2) }}
                    </p>
                    <p class="text-xs text-gray-500">{{ $statistics->total_flows }} transactions</p>
                </div>
                
                <div class="bg-gray-50 p-4 rounded">
                    <h3 class="text-sm font-medium text-gray-500">Internal Transfers</h3>
                    <p class="text-2xl font-bold text-blue-600">${{ number_format($statistics->total_internal / 100, 2) }}</p>
                    <p class="text-xs text-gray-500">Between your accounts</p>
                </div>
            </div>
            
            <!-- Fund Flow Chart -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-4">Daily Flow Chart</h2>
                <div class="bg-gray-100 p-4 rounded" style="height: 300px;">
                    <!-- Chart would go here - using canvas or chart.js -->
                    <div id="flowChart" class="w-full h-full flex items-center justify-center text-gray-500">
                        Chart visualization would be rendered here
                    </div>
                </div>
            </div>
            
            <!-- Recent Flows -->
            <div>
                <h2 class="text-lg font-semibold mb-4">Recent Flows</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($flowData as $flow)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($flow['timestamp'])->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $flow['type'] == 'deposit' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $flow['type'] == 'withdrawal' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $flow['type'] == 'transfer' ? 'bg-blue-100 text-blue-800' : '' }}">
                                            {{ ucfirst($flow['type']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $flow['from']['name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $flow['to']['name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $flow['currency'] }} {{ number_format($flow['amount'] / 100, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No flows found for the selected period
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection