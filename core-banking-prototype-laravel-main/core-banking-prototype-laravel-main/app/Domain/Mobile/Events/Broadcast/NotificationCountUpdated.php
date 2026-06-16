<?php

declare(strict_types=1);

namespace App\Domain\Mobile\Events\Broadcast;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast event for real-time notification unread count updates.
 *
 * Fires on the user.{userId} private channel whenever
 * the unread notification count changes (new notification, mark read, mark all read).
 */
class NotificationCountUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public static string $queue = 'events';

    public function __construct(
        public readonly int $userId,
        public readonly int $unreadCount,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.count.updated';
    }

    /**
     * @return array{unread_count: int}
     */
    public function broadcastWith(): array
    {
        return [
            'unread_count' => $this->unreadCount,
        ];
    }
}
