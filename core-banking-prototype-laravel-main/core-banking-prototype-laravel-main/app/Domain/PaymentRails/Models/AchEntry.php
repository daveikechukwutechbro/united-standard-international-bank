<?php

declare(strict_types=1);

namespace App\Domain\PaymentRails\Models;

use App\Domain\PaymentRails\Enums\RailStatus;
use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string                    $id
 * @property string                    $batch_id
 * @property string                    $trace_number
 * @property string                    $routing_number
 * @property string                    $account_number
 * @property string                    $amount
 * @property string                    $transaction_code
 * @property string                    $individual_name
 * @property string|null               $individual_id
 * @property string|null               $addenda
 * @property RailStatus                $status
 * @property string|null               $return_code
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class AchEntry extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;
    use HasUuids;
    use UsesTenantConnection;

    protected $table = 'ach_entries';

    protected $fillable = [
        'batch_id',
        'trace_number',
        'routing_number',
        'account_number',
        'amount',
        'transaction_code',
        'individual_name',
        'individual_id',
        'addenda',
        'status',
        'return_code',
    ];

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RailStatus::class,
        ];
    }

    /** @return BelongsTo<AchBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(AchBatch::class, 'batch_id');
    }
}
