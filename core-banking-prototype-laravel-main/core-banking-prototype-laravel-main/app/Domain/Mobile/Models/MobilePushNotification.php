<?php

declare(strict_types=1);

namespace App\Domain\Mobile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mobile push notification model for tracking push notification delivery.
 *
 * @property string $id
 * @property int $user_id
 * @property string|null $mobile_device_id
 * @property string $notification_type
 * @property string $title
 * @property string $body
 * @property array<string, mixed>|null $data
 * @property string $status
 * @property string|null $external_id
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Carbon\Carbon|null $scheduled_at
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $read_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static> retryable()
 * @method static \Illuminate\Database\Eloquent\Builder<static> unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static> ofType(string $type)
 */
class MobilePushNotification extends Model
{
    use HasFactory;
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_READ = 'read';

    public const TYPE_TRANSACTION_RECEIVED = 'transaction.received';

    public const TYPE_TRANSACTION_SENT = 'transaction.sent';

    public const TYPE_TRANSACTION_FAILED = 'transaction.failed';

    public const TYPE_BALANCE_LOW = 'balance.low';

    public const TYPE_KYC_STATUS_CHANGED = 'kyc.status_changed';

    public const TYPE_SECURITY_LOGIN = 'security.login';

    public const TYPE_SECURITY_DEVICE_ADDED = 'security.device_added';

    public const TYPE_PRICE_ALERT = 'price.alert';

    public const TYPE_GENERAL = 'general';

    public const MAX_RETRIES = 3;

    protected $fillable = [
        'user_id',
        'mobile_device_id',
        'notification_type',
        'title',
        'body',
        'data',
        'status',
        'external_id',
        'error_message',
        'retry_count',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'data'         => 'array',
        'retry_count'  => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
    ];

    /**
     * Get the user for this notification.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the mobile device for this notification.
     *
     * @return BelongsTo<MobileDevice, $this>
     */
    public function mobileDevice(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class);
    }

    /**
     * Scope to get pending notifications.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get failed notifications that can be retried.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeRetryable(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_FAILED)
            ->where('retry_count', '<', self::MAX_RETRIES);
    }

    /**
     * Scope to get unread notifications.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeUnread(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to filter by notification type.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeOfType(\Illuminate\Database\Eloquent\Builder $query, string $type): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Check if notification can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED
            && $this->retry_count < self::MAX_RETRIES;
    }

    /**
     * Mark notification as sent.
     */
    public function markAsSent(string $externalId): void
    {
        $this->update([
            'status'      => self::STATUS_SENT,
            'external_id' => $externalId,
            'sent_at'     => now(),
        ]);
    }

    /**
     * Mark notification as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status'       => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'status'  => self::STATUS_READ,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status'        => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'retry_count'   => $this->retry_count + 1,
        ]);
    }

    /**
     * Reset for retry.
     */
    public function resetForRetry(): void
    {
        $this->update([
            'status'        => self::STATUS_PENDING,
            'error_message' => null,
        ]);
    }

    /**
     * Get available notification types.
     *
     * @return array<string>
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_TRANSACTION_RECEIVED,
            self::TYPE_TRANSACTION_SENT,
            self::TYPE_TRANSACTION_FAILED,
            self::TYPE_BALANCE_LOW,
            self::TYPE_KYC_STATUS_CHANGED,
            self::TYPE_SECURITY_LOGIN,
            self::TYPE_SECURITY_DEVICE_ADDED,
            self::TYPE_PRICE_ALERT,
            self::TYPE_GENERAL,
        ];
    }
}
