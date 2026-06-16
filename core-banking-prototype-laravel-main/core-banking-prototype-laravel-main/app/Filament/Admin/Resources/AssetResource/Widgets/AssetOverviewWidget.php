<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AssetResource\Widgets;

use App\Domain\Asset\Models\Asset;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class AssetOverviewWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        /**
         * @var Asset $asset
         */
        $asset = $this->record;

        $totalBalances = $asset->accountBalances()->count();
        $totalValue = $asset->accountBalances()->sum('balance');
        $activeRates = $asset->exchangeRatesFrom()->valid()->count();
        $avgBalance = $totalBalances > 0 ? $totalValue / $totalBalances : 0;

        return [
            Stat::make('Total Accounts', $totalBalances)
                ->description('Accounts holding this asset')
                ->icon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Total Value', $this->formatAssetValue($totalValue, $asset))
                ->description('Sum of all balances')
                ->icon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Average Balance', $this->formatAssetValue($avgBalance, $asset))
                ->description('Per account average')
                ->icon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Active Exchange Rates', $activeRates)
                ->description('Valid rates from this asset')
                ->icon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }

    private function formatAssetValue(float $value, Asset $asset): string
    {
        $formatted = number_format($value / (10 ** $asset->precision), $asset->precision);

        return "{$formatted} {$asset->code}";
    }
}
