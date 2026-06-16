<?php

declare(strict_types=1);

namespace App\Domain\Shared\EventSourcing;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class EventStreamPublisher
{
    private readonly string $prefix;

    private readonly int $maxLength;

    private readonly bool $enabled;

    /**
     * @var array<string, string>
     */
    private readonly array $streamMappings;

    public function __construct()
    {
        $this->prefix = (string) config('event-streaming.prefix', 'finaegis:events');
        $this->maxLength = (int) config('event-streaming.max_stream_length', 100000);
        $this->enabled = (bool) config('event-streaming.enabled', false);
        /** @var array<string, string> $streams */
        $streams = config('event-streaming.streams', []);
        $this->streamMappings = $streams;
    }

    /**
     * Publish a domain event to the appropriate Redis Stream.
     *
     * @param  array<string, mixed>  $eventData
     */
    public function publish(string $domain, array $eventData): ?string
    {
        if (! $this->enabled) {
            return null;
        }

        $streamKey = $this->resolveStreamKey($domain);

        try {
            $fields = [
                'domain'         => $domain,
                'event_class'    => $eventData['event_class'] ?? 'unknown',
                'aggregate_uuid' => $eventData['aggregate_uuid'] ?? '',
                'payload'        => json_encode($eventData, JSON_THROW_ON_ERROR),
                'published_at'   => now()->toIso8601String(),
            ];

            /** @var string $messageId */
            $messageId = Redis::xadd($streamKey, '*', $fields);

            // Trim stream to max length
            if ($this->maxLength > 0) {
                /** @phpstan-ignore argument.type */
                Redis::xtrim($streamKey, 'MAXLEN', $this->maxLength);
            }

            Log::debug("Event published to stream: {$streamKey}", [
                'message_id' => $messageId,
                'domain'     => $domain,
                'event'      => $eventData['event_class'] ?? 'unknown',
            ]);

            return $messageId;
        } catch (Throwable $e) {
            Log::error("Failed to publish event to stream: {$streamKey}", [
                'domain' => $domain,
                'error'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Publish multiple events in a pipeline for performance.
     *
     * @param  array<int, array{domain: string, data: array<string, mixed>}>  $events
     * @return array<int, string|null>
     */
    public function publishBatch(array $events): array
    {
        if (! $this->enabled) {
            return array_fill(0, count($events), null);
        }

        $results = [];

        foreach ($events as $event) {
            $results[] = $this->publish($event['domain'], $event['data']);
        }

        return $results;
    }

    /**
     * Get stream info for monitoring.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getStreamInfo(): array
    {
        $info = [];

        foreach ($this->streamMappings as $domain => $streamSuffix) {
            $streamKey = "{$this->prefix}:{$streamSuffix}";

            try {
                /** @var int $length */
                $length = Redis::xlen($streamKey);
                $info[$domain] = [
                    'stream_key' => $streamKey,
                    'length'     => $length,
                    'status'     => 'active',
                ];
            } catch (Throwable) {
                $info[$domain] = [
                    'stream_key' => $streamKey,
                    'length'     => 0,
                    'status'     => 'unavailable',
                ];
            }
        }

        return $info;
    }

    private function resolveStreamKey(string $domain): string
    {
        $streamSuffix = $this->streamMappings[strtolower($domain)] ?? "{$domain}-events";

        return "{$this->prefix}:{$streamSuffix}";
    }
}
