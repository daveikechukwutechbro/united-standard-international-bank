<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ExchangeRateResource\Widgets;

use App\Domain\Asset\Models\ExchangeRate;
use Filament\Widgets\ChartWidget;

class ExchangeRateChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Exchange Rate Activity';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        // Get rate creation activity over the last 30 days
        $data = collect();

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = ExchangeRate::whereDate('created_at', $date)->count();

            $data->push(
                [
                    'date'  => $date->format('M j'),
                    'rates' => $count,
                ]
            );
        }

        return [
            'datasets' => [
                [
                    'label'           => 'New Rates Created',
                    'data'            => $data->pluck('rates')->toArray(),
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill'            => true,
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'scales'              => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}
