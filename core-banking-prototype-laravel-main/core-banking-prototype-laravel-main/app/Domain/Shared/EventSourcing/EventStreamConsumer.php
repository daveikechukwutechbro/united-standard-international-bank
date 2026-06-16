<?php

declare(strict_types=1);

namespace App\Domain\Shared\EventSourcing;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class EventStreamConsumer
{
    private readonly string $prefix;

    private readonly string $consumerGroup;

    private readonly int $blockTimeout;

    private readonly int $batchSize;

    private readonly int $idleTimeout;

    public function __construct()
    {
        $this->prefix = (string) config('event-streaming.prefix', 'finaegis:events');
        $this->consumerGroup = (string) config('event-streaming.consumer_group', 'finaegis-consumers');
        $this->blockTimeout = (int) config('event-streaming.block_timeout', 5000);
        $this->batchSize = (int) config('event-streaming.batch_size', 100);
        $this->idleTimeout = (int) config('event-streaming.consumer_idle_timeout', 30000);
    }

    /**
     * Create a consumer group for a domain stream.
     */
    public function createConsumerGroup(string $domain, string $startId = '0'): bool
    {
        $streamKey = $this->resolveStreamKey($domain);

        try {
            /** @phpstan-ignore argument.type */
            Redis::xgroup('CREATE', $streamKey, $this->consumerGroup, $startId, true);

            Log::info("Consumer group created: {$this->consumerGroup} on {$streamKey}");

            return true;
        } catch (Throwable $e) {
            // Group already exists is acceptable
            if (str_contains($e->getMessage(), 'BUSYGROUP')) {
                return true;
            }

            Log::error("Failed to create consumer group: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Read messages from a domain stream as a consumer.
     *
     * @return array<string, mixed>
     */
    public function consume(string $domain, string $consumerName): array
    {
        $streamKey = $this->resolveStreamKey($domain);

        try {
            /** @var array<string, mixed>|false $messages */
            $messages = Redis::xreadgroup(
                $this->consumerGroup,
                $consumerName,
                [$streamKey => '>'],
                $this->batchSize,
                $this->blockTimeout,
            );

            if ($messages === false) {
                return [];
            }

            return $messages[$streamKey] ?? [];
        } catch (Throwable $e) {
            Log::error("Failed to consume from stream: {$streamKey}", [
                'consumer' => $consumerName,
                'error'    => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Acknowledge a processed message.
     */
    public function acknowledge(string $domain, string $messageId): bool
    {
        $streamKey = $this->resolveStreamKey($domain);

        try {
            /** @var int $acknowledged */
            $acknowledged = Redis::xack($streamKey, $this->consumerGroup, [$messageId]);

            return $acknowledged > 0;
        } catch (Throwable $e) {
            Log::error("Failed to acknowledge message: {$messageId}", [
                'stream' => $streamKey,
                'error'  => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get pending messages that haven't been acknowledged.
     *
     * @return array<string, mixed>
     */
    public function getPending(string $domain): array
    {
        $streamKey = $this->resolveStreamKey($domain);

        try {
            /** @var array<string, mixed> $pending */
            $pending = Redis::xpending($streamKey, $this->consumerGroup);

            return $pending;
        } catch (Throwable $e) {
            Log::error("Failed to get pending messages: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Claim idle messages from other consumers.
     *
     * @return array<string, mixed>
     */
    public function claimIdleMessages(string $domain, string $consumerName, int $count = 10): array
    {
        $streamKey = $this->resolveStreamKey($domain);

        try {
            /** @var array<string, mixed> $claimed */
            $claimed = Redis::xautoclaim(
                $streamKey,
                $this->consumerGroup,
                $consumerName,
                $this->idleTimeout,
                '0-0',
                $count,
            );

            return $claimed;
        } catch (Throwable $e) {
            Log::error("Failed to claim idle messages: {$e->getMessage()}");

            return [];
        }
    }

    /**
     * Get consumer group info for monitoring.
     *
     * @return array<string, mixed>
     */
    public function getConsumerGroupInfo(string $domain): array
    {
        $streamKey = $this->resolveStreamKey($domain);

        try {
            /** @var array<int, array<string, mixed>> $groups */
            $groups = Redis::xinfo('GROUPS', $streamKey);

            foreach ($groups as $group) {
                if (($group['name'] ?? '') === $this->consumerGroup) {
                    return [
                        'name'              => $group['name'],
                        'consumers'         => $group['consumers'] ?? 0,
                        'pending'           => $group['pending'] ?? 0,
                        'last_delivered_id' => $group['last-delivered-id'] ?? '0-0',
                    ];
                }
            }

            return ['status' => 'not_found'];
        } catch (Throwable) {
            return ['status' => 'unavailable'];
        }
    }

    private function resolveStreamKey(string $domain): string
    {
        /** @var array<string, string> $streams */
        $streams = config('event-streaming.streams', []);
        $streamSuffix = $streams[strtolower($domain)] ?? "{$domain}-events";

        return "{$this->prefix}:{$streamSuffix}";
    }
}
