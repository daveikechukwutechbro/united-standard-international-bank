<?php

declare(strict_types=1);

namespace App\Domain\PaymentRails\Models;

use App\Domain\PaymentRails\Enums\PaymentRail;
use App\Domain\PaymentRails\Enums\RailStatus;
use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string                    $id
 * @property int                       $user_id
 * @property PaymentRail               $rail
 * @property string|null               $external_id
 * @property string                    $amount
 * @property string                    $currency
 * @property RailStatus                $status
 * @property string                    $direction
 * @property array<string, mixed>|null $metadata
 * @property string|null               $error_message
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PaymentRailTransaction extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'payment_rail_transactions';

    protected $fillable = [
        'user_id',
        'rail',
        'external_id',
        'amount',
        'currency',
        'status',
        'direction',
        'metadata',
        'error_message',
        'completed_at',
    ];

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'rail'         => PaymentRail::class,
            'amount'       => 'decimal:2',
            'status'       => RailStatus::class,
            'metadata'     => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /** @param Builder<PaymentRailTransaction> $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * @param Builder<PaymentRailTransaction> $query
     * @param PaymentRail $rail
     */
    public function scopeByRail(Builder $query, PaymentRail $rail): void
    {
        $query->where('rail', $rail->value);
    }
}
