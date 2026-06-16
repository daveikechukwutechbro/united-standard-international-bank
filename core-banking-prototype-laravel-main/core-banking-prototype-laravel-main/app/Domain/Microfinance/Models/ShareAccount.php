<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Models;

use App\Domain\Microfinance\Enums\ShareAccountStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $group_id
 * @property string $account_number
 * @property int $shares_purchased
 * @property string $nominal_value
 * @property string $total_value
 * @property ShareAccountStatus $status
 * @property string $currency
 * @property string $dividend_balance
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<static> active()
 * @method static Builder<static> forUser(int $userId)
 * @method static Builder<static> where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static static|null find(mixed $id)
 * @method static static|null first()
 * @method static \Illuminate\Database\Eloquent\Collection<int, static> get()
 * @method static int count()
 */
class ShareAccount extends Model
{
    use HasUuids;

    protected $table = 'mfi_share_accounts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'group_id',
        'account_number',
        'shares_purchased',
        'nominal_value',
        'total_value',
        'status',
        'currency',
        'dividend_balance',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status'           => ShareAccountStatus::class,
            'shares_purchased' => 'integer',
            'nominal_value'    => 'decimal:2',
            'total_value'      => 'decimal:2',
            'dividend_balance' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ShareAccountStatus::ACTIVE->value);
    }

    /**
     * @param  Builder<static> $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
