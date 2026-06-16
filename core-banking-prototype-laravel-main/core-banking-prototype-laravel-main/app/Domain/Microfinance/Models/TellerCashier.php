<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string|null $branch
 * @property string $vault_balance
 * @property string $currency
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_reconciled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<static> where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static static|null find(mixed $id)
 * @method static static|null first()
 * @method static \Illuminate\Database\Eloquent\Collection<int, static> get()
 * @method static int count()
 */
class TellerCashier extends Model
{
    use HasUuids;

    protected $table = 'mfi_teller_cashiers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'name',
        'branch',
        'vault_balance',
        'currency',
        'is_active',
        'last_reconciled_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'vault_balance'      => 'decimal:2',
            'is_active'          => 'boolean',
            'last_reconciled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
