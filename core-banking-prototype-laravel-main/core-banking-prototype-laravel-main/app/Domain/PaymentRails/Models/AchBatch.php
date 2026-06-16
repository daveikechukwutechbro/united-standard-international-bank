<?php

declare(strict_types=1);

namespace App\Domain\PaymentRails\Models;

use App\Domain\PaymentRails\Enums\RailStatus;
use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string                    $id
 * @property string                    $batch_id
 * @property int                       $user_id
 * @property string                    $sec_code
 * @property RailStatus                $status
 * @property int                       $entry_count
 * @property string                    $total_debit
 * @property string                    $total_credit
 * @property \Illuminate\Support\Carbon|null $settlement_date
 * @property bool                      $same_day
 * @property string|null               $file_content
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AchBatch extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'ach_batches';

    protected $fillable = [
        'batch_id',
        'user_id',
        'sec_code',
        'status',
        'entry_count',
        'total_debit',
        'total_credit',
        'settlement_date',
        'same_day',
        'file_content',
    ];

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'status'          => RailStatus::class,
            'entry_count'     => 'integer',
            'total_debit'     => 'decimal:2',
            'total_credit'    => 'decimal:2',
            'settlement_date' => 'date',
            'same_day'        => 'boolean',
        ];
    }

    /** @return HasMany<AchEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(AchEntry::class, 'batch_id');
    }

    /** @param Builder<AchBatch> $query */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /** @param Builder<AchBatch> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', RailStatus::PENDING->value);
    }
}
