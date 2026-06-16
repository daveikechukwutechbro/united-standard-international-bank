<?php

declare(strict_types=1);

namespace App\Domain\Monitoring\Events\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringMetricsUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed> $metrics
     */
    public function __construct(
        public readonly array $metrics,
        public readonly string $source = 'system',
    ) {
    }

    /**
     * @return array<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('monitoring'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'metrics.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'metrics'   => $this->metrics,
            'source'    => $this->source,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
