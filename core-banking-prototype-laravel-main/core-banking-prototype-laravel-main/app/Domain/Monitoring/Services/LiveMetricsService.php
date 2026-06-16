<?php

declare(strict_types=1);

namespace App\Domain\Monitoring\Services;

use App\Domain\Shared\EventSourcing\EventStreamPublisher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class LiveMetricsService
{
    public function __construct(
        private readonly EventStreamPublisher $streamPublisher,
    ) {
    }

    /**
     * Get real-time platform metrics.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        return Cache::remember('live_metrics', 10, function () {
            return [
                'timestamp'        => now()->toIso8601String(),
                'domain_health'    => $this->getDomainHealth(),
                'event_throughput' => $this->getEventThroughput(),
                'stream_status'    => $this->getStreamStatus(),
                'system_health'    => $this->getSystemHealth(),
            ];
        });
    }

    /**
     * Get domain health summary.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getDomainHealth(): array
    {
        $domains = [
            'accounts'             => 'Account',
            'wallets'              => 'Wallet',
            'orders'               => 'Exchange',
            'payment_transactions' => 'Payment',
            'loan_applications'    => 'Lending',
            'compliance_alerts'    => 'Compliance',
            'fraud_cases'          => 'Fraud',
        ];

        $health = [];

        foreach ($domains as $table => $domain) {
            try {
                $total = DB::table($table)->count();
                $recent = DB::table($table)
                    ->where('created_at', '>=', now()->subHour())
                    ->count();

                $health[$domain] = [
                    'total_records' => $total,
                    'last_hour'     => $recent,
                    'status'        => 'healthy',
                ];
            } catch (Throwable) {
                $health[$domain] = [
                    'total_records' => 0,
                    'last_hour'     => 0,
                    'status'        => 'unavailable',
                ];
            }
        }

        return $health;
    }

    /**
     * Get event throughput metrics.
     *
     * @return array<string, mixed>
     */
    public function getEventThroughput(): array
    {
        try {
            $lastHour = DB::table('stored_events')
                ->where('created_at', '>=', now()->subHour())
                ->count();

            $last24h = DB::table('stored_events')
                ->where('created_at', '>=', now()->subDay())
                ->count();

            return [
                'last_hour'      => $lastHour,
                'last_24h'       => $last24h,
                'avg_per_minute' => $lastHour > 0 ? round($lastHour / 60, 2) : 0,
                'status'         => 'active',
            ];
        } catch (Throwable) {
            return [
                'last_hour'      => 0,
                'last_24h'       => 0,
                'avg_per_minute' => 0,
                'status'         => 'unavailable',
            ];
        }
    }

    /**
     * Get event stream status.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getStreamStatus(): array
    {
        return $this->streamPublisher->getStreamInfo();
    }

    /**
     * Get system health metrics.
     *
     * @return array<string, mixed>
     */
    public function getSystemHealth(): array
    {
        return [
            'php_version'      => PHP_VERSION,
            'memory_usage_mb'  => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb'   => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'uptime_seconds'   => time() - (int) ($_SERVER['REQUEST_TIME'] ?? time()),
            'laravel_version'  => app()->version(),
            'queue_connection' => config('queue.default'),
            'cache_driver'     => config('cache.default'),
        ];
    }

    /**
     * Get projector lag summary.
     *
     * @return array<string, mixed>
     */
    public function getProjectorLag(): array
    {
        try {
            $totalEvents = DB::table('stored_events')->count();
            $lastProcessed = DB::table('stored_events')
                ->orderBy('id', 'desc')
                ->value('id');

            return [
                'total_events'  => $totalEvents,
                'last_event_id' => $lastProcessed,
                'estimated_lag' => 0,
                'status'        => 'healthy',
            ];
        } catch (Throwable) {
            return [
                'total_events'  => 0,
                'last_event_id' => null,
                'estimated_lag' => -1,
                'status'        => 'unavailable',
            ];
        }
    }
}
