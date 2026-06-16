<?php

declare(strict_types=1);

namespace App\Domain\Shared\Notifications;

use Psr\Log\LoggerInterface;
use Throwable;

class NotificationService
{
    /**
     * Supported notification channels.
     */
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_PUSH = 'push';

    public const CHANNEL_IN_APP = 'in_app';

    public const CHANNEL_WEBHOOK = 'webhook';

    public const CHANNEL_SMS = 'sms';

    /**
     * @var array<string, callable>
     */
    private array $channelHandlers = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $pendingNotifications = [];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Register a channel handler.
     */
    public function registerChannel(string $channel, callable $handler): void
    {
        $this->channelHandlers[$channel] = $handler;
    }

    /**
     * Send a notification through specified channels.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $channels
     * @return array<string, string>
     */
    public function send(string $userId, string $type, array $payload, array $channels = []): array
    {
        if (empty($channels)) {
            $channels = [self::CHANNEL_IN_APP];
        }

        $results = [];

        foreach ($channels as $channel) {
            try {
                if (isset($this->channelHandlers[$channel])) {
                    ($this->channelHandlers[$channel])($userId, $type, $payload);
                    $results[$channel] = 'sent';
                } else {
                    $results[$channel] = 'no_handler';
                }

                $this->logger?->debug("Notification sent: {$type} via {$channel}", [
                    'user_id' => $userId,
                    'type'    => $type,
                    'channel' => $channel,
                ]);
            } catch (Throwable $e) {
                $results[$channel] = "failed: {$e->getMessage()}";

                $this->logger?->error("Notification failed: {$type} via {$channel}", [
                    'user_id' => $userId,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Queue a notification for batch sending.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $channels
     */
    public function queue(string $userId, string $type, array $payload, array $channels = []): void
    {
        $this->pendingNotifications[] = [
            'user_id'  => $userId,
            'type'     => $type,
            'payload'  => $payload,
            'channels' => $channels,
        ];
    }

    /**
     * Flush all queued notifications.
     *
     * @return array<int, array<string, string>>
     */
    public function flush(): array
    {
        $results = [];

        foreach ($this->pendingNotifications as $notification) {
            $results[] = $this->send(
                $notification['user_id'],
                $notification['type'],
                $notification['payload'],
                $notification['channels'],
            );
        }

        $this->pendingNotifications = [];

        return $results;
    }

    /**
     * Get available channels.
     *
     * @return array<int, string>
     */
    public function getAvailableChannels(): array
    {
        return [
            self::CHANNEL_EMAIL,
            self::CHANNEL_PUSH,
            self::CHANNEL_IN_APP,
            self::CHANNEL_WEBHOOK,
            self::CHANNEL_SMS,
        ];
    }

    /**
     * Get registered channel handlers.
     *
     * @return array<int, string>
     */
    public function getRegisteredChannels(): array
    {
        return array_keys($this->channelHandlers);
    }

    /**
     * Check if a channel has a registered handler.
     */
    public function hasChannel(string $channel): bool
    {
        return isset($this->channelHandlers[$channel]);
    }

    /**
     * Get pending notification count.
     */
    public function getPendingCount(): int
    {
        return count($this->pendingNotifications);
    }

    /**
     * Create notification triggers for domain events.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getEventTriggers(): array
    {
        return [
            'account.created' => [
                'type'     => 'account_created',
                'channels' => [self::CHANNEL_EMAIL, self::CHANNEL_IN_APP],
                'template' => 'Your account has been created successfully.',
            ],
            'payment.completed' => [
                'type'     => 'payment_completed',
                'channels' => [self::CHANNEL_PUSH, self::CHANNEL_IN_APP],
                'template' => 'Payment of {amount} {currency} completed.',
            ],
            'compliance.alert' => [
                'type'     => 'compliance_alert',
                'channels' => [self::CHANNEL_EMAIL, self::CHANNEL_IN_APP, self::CHANNEL_WEBHOOK],
                'template' => 'Compliance alert: {title}',
            ],
            'fraud.detected' => [
                'type'     => 'fraud_alert',
                'channels' => [self::CHANNEL_EMAIL, self::CHANNEL_PUSH, self::CHANNEL_IN_APP],
                'template' => 'Suspicious activity detected on your account.',
            ],
            'wallet.transfer' => [
                'type'     => 'wallet_transfer',
                'channels' => [self::CHANNEL_PUSH, self::CHANNEL_IN_APP],
                'template' => 'Transfer of {amount} {asset} completed.',
            ],
            'loan.approved' => [
                'type'     => 'loan_approved',
                'channels' => [self::CHANNEL_EMAIL, self::CHANNEL_IN_APP],
                'template' => 'Your loan application has been approved.',
            ],
            'bridge.completed' => [
                'type'     => 'bridge_transfer_completed',
                'channels' => [self::CHANNEL_PUSH, self::CHANNEL_IN_APP],
                'template' => 'Cross-chain transfer completed.',
            ],
        ];
    }
}
