<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Models;

use App\Domain\Ledger\Enums\AccountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $code
 * @property string $name
 * @property AccountType $type
 * @property string|null $parent_code
 * @property string $currency
 * @property bool $is_active
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder<LedgerAccount> active()
 * @method static Builder<LedgerAccount> byType(AccountType $type)
 * @method static Builder<LedgerAccount> where(string $column, mixed $operator = null, mixed $value = null)
 * @method static LedgerAccount|null find(mixed $id, array<int, string> $columns = ['*'])
 * @method static LedgerAccount|null first(array<int, string> $columns = ['*'])
 * @method static LedgerAccount firstOrFail(array<int, string> $columns = ['*'])
 * @method static LedgerAccount create(array<string, mixed> $attributes = [])
 */
class LedgerAccount extends Model
{
    use HasUuids;

    protected $table = 'ledger_accounts';

    /** @var list<string> */
    protected $guarded = [];

    /**
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'type'      => AccountType::class,
        'is_active' => 'boolean',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency'  => 'USD',
        'is_active' => true,
    ];

    /**
     * Scope: only active accounts.
     *
     * @param  Builder<LedgerAccount> $query
     * @return Builder<LedgerAccount>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter by account type.
     *
     * @param  Builder<LedgerAccount> $query
     * @return Builder<LedgerAccount>
     */
    public function scopeByType(Builder $query, AccountType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * Returns true when the normal balance side is debit.
     */
    public function isDebitNormal(): bool
    {
        return $this->type->normalBalance() === 'debit';
    }

    /**
     * Child accounts (same parent_code).
     *
     * @return HasMany<LedgerAccount, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_code', 'code');
    }

    /**
     * Parent account.
     *
     * @return BelongsTo<LedgerAccount, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_code', 'code');
    }
}
