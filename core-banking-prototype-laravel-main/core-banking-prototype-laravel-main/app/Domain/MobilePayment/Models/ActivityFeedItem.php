<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Models;

use App\Domain\MobilePayment\Enums\ActivityItemType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Denormalized activity feed item (CQRS read model).
 *
 * @property string $id
 * @property int $user_id
 * @property ActivityItemType $activity_type
 * @property string|null $merchant_name
 * @property string|null $merchant_icon_url
 * @property string $amount
 * @property string $asset
 * @property string|null $network
 * @property string $status
 * @property bool $protected
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property string|null $from_address
 * @property string|null $to_address
 * @property \Carbon\Carbon $occurred_at
 * @property array<string, mixed>|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ActivityFeedItem extends Model
{
    use HasUuids;

    protected $table = 'activity_feed_items';

    protected $fillable = [
        'user_id',
        'activity_type',
        'merchant_name',
        'merchant_icon_url',
        'amount',
        'asset',
        'network',
        'status',
        'protected',
        'reference_type',
        'reference_id',
        'from_address',
        'to_address',
        'occurred_at',
        'metadata',
    ];

    /** @var list<string> */
    protected $hidden = [
        'user_id',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'activity_type' => ActivityItemType::class,
        'protected'     => 'boolean',
        'metadata'      => 'array',
        'occurred_at'   => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<ActivityFeedItem> $query
     * @return \Illuminate\Database\Eloquent\Builder<ActivityFeedItem>
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<ActivityFeedItem> $query
     * @return \Illuminate\Database\Eloquent\Builder<ActivityFeedItem>
     */
    public function scopeIncome($query)
    {
        return $query->whereIn('activity_type', [
            ActivityItemType::TRANSFER_IN,
            ActivityItemType::UNSHIELD,
        ]);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<ActivityFeedItem> $query
     * @return \Illuminate\Database\Eloquent\Builder<ActivityFeedItem>
     */
    public function scopeExpenses($query)
    {
        return $query->whereIn('activity_type', [
            ActivityItemType::MERCHANT_PAYMENT,
            ActivityItemType::TRANSFER_OUT,
            ActivityItemType::SHIELD,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiResponse(): array
    {
        $response = [
            'id'        => $this->id,
            'type'      => $this->activity_type->value,
            'amount'    => $this->amount,
            'asset'     => $this->asset,
            'timestamp' => $this->occurred_at->toIso8601String(),
            'status'    => $this->status,
            'protected' => $this->protected,
        ];

        if ($this->merchant_name) {
            $response['merchantName'] = $this->merchant_name;
            $response['merchantIconUrl'] = $this->merchant_icon_url;
        }

        if ($this->from_address) {
            $response['fromAddress'] = $this->from_address;
        }

        if ($this->to_address) {
            $response['toAddress'] = $this->to_address;
        }

        return $response;
    }
}
