<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $officer_id
 * @property string $group_id
 * @property \Illuminate\Support\Carbon $collection_date
 * @property string $expected_amount
 * @property string $collected_amount
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<static> pending()
 * @method static Builder<static> forDate(string $date)
 * @method static Builder<static> where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static static|null find(mixed $id)
 * @method static static|null first()
 * @method static \Illuminate\Database\Eloquent\Collection<int, static> get()
 * @method static int count()
 */
class CollectionSheet extends Model
{
    use HasUuids;

    protected $table = 'mfi_collection_sheets';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'officer_id',
        'group_id',
        'collection_date',
        'expected_amount',
        'collected_amount',
        'status',
        'notes',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'collection_date'  => 'date',
            'expected_amount'  => 'decimal:2',
            'collected_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<FieldOfficer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(FieldOfficer::class, 'officer_id');
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * @param  Builder<static> $query
     * @return Builder<static>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * @param  Builder<static> $query
     * @return Builder<static>
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('collection_date', $date);
    }
}
