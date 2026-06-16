<?php

declare(strict_types=1);

namespace App\Domain\Banking\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $user_id
 * @property string $mandate_id
 * @property string $creditor_id
 * @property string $creditor_name
 * @property string $creditor_iban
 * @property string $debtor_name
 * @property string $debtor_iban
 * @property string $scheme
 * @property string $status
 * @property Carbon $signed_at
 * @property Carbon|null $last_collection_at
 * @property string|null $max_amount
 * @property string|null $frequency
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class SepaMandate extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'sepa_mandates';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'mandate_id',
        'creditor_id',
        'creditor_name',
        'creditor_iban',
        'debtor_name',
        'debtor_iban',
        'scheme',
        'status',
        'signed_at',
        'last_collection_at',
        'max_amount',
        'frequency',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'signed_at'          => 'datetime',
            'last_collection_at' => 'datetime',
            'max_amount'         => 'decimal:2',
            'scheme'             => 'string',
        ];
    }

    /** @param Builder<SepaMandate> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * @param Builder<SepaMandate> $query
     * @param int $userId
     */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * @param Builder<SepaMandate> $query
     * @param string $scheme
     */
    public function scopeByScheme(Builder $query, string $scheme): void
    {
        $query->where('scheme', $scheme);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * A SEPA mandate expires if no collection has been made in 36 months.
     * This applies to both CORE and B2B schemes.
     */
    public function isExpired(): bool
    {
        if ($this->last_collection_at === null) {
            // If never used, check from signed_at
            return $this->signed_at->lt(Carbon::now()->subMonths(36));
        }

        return $this->last_collection_at->lt(Carbon::now()->subMonths(36));
    }
}
