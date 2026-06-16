<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\EventSourcing\EventStreamConsumer;
use App\Domain\Shared\EventSourcing\EventStreamPublisher;
use Illuminate\Console\Command;

class EventStreamMonitorCommand extends Command
{
    protected $signature = 'event-stream:monitor
        {--domain= : Monitor a specific domain stream}
        {--json : Output in JSON format}';

    protected $description = 'Monitor event stream health and consumer group status';

    public function __construct(
        private readonly EventStreamPublisher $publisher,
        private readonly EventStreamConsumer $consumer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Event Stream Monitor');
        $this->newLine();

        $domain = $this->option('domain');
        $streamInfo = $this->publisher->getStreamInfo();

        if ($domain) {
            $streamInfo = array_filter(
                $streamInfo,
                fn ($key) => strtolower($key) === strtolower($domain),
                ARRAY_FILTER_USE_KEY
            );
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'streams'   => $streamInfo,
                'timestamp' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($streamInfo as $domainName => $info) {
            $consumerInfo = $this->consumer->getConsumerGroupInfo($domainName);

            $rows[] = [
                $domainName,
                $info['stream_key'],
                (string) $info['length'],
                $info['status'],
                (string) ($consumerInfo['consumers'] ?? 'N/A'),
                (string) ($consumerInfo['pending'] ?? 'N/A'),
            ];
        }

        $this->table(
            ['Domain', 'Stream Key', 'Length', 'Status', 'Consumers', 'Pending'],
            $rows
        );

        $this->newLine();
        $this->info('Streaming is ' . (config('event-streaming.enabled') ? 'ENABLED' : 'DISABLED'));

        return self::SUCCESS;
    }
}
