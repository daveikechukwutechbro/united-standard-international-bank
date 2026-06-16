<?php

declare(strict_types=1);

namespace Plugins\DashboardWidget;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DomainHealthWidget extends StatsOverviewWidget
{
    protected static ?string $heading = 'Domain Health Overview';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $stats = Cache::remember('plugin.dashboard-widget.stats', 60, function () {
            return [
                'accounts'   => $this->getTableCount('accounts'),
                'wallets'    => $this->getTableCount('wallets'),
                'orders'     => $this->getTableCount('orders'),
                'payments'   => $this->getTableCount('payment_transactions'),
                'events'     => $this->getTableCount('stored_events'),
                'alerts'     => $this->getTableCount('compliance_alerts'),
            ];
        });

        return [
            Stat::make('Accounts', number_format($stats['accounts']))
                ->description('Total accounts')
                ->color('success'),
            Stat::make('Wallets', number_format($stats['wallets']))
                ->description('Total wallets')
                ->color('info'),
            Stat::make('Orders', number_format($stats['orders']))
                ->description('Exchange orders')
                ->color('warning'),
            Stat::make('Payments', number_format($stats['payments']))
                ->description('Payment transactions')
                ->color('primary'),
            Stat::make('Events', number_format($stats['events']))
                ->description('Stored events')
                ->color('gray'),
            Stat::make('Alerts', number_format($stats['alerts']))
                ->description('Compliance alerts')
                ->color('danger'),
        ];
    }

    private function getTableCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
