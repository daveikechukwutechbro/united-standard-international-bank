<?php

declare(strict_types=1);

namespace App\Domain\OpenBanking\Models;

use App\Domain\OpenBanking\Enums\ConsentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string      $id
 * @property string      $tpp_id
 * @property int         $user_id
 * @property ConsentStatus $status
 * @property array<int, string> $permissions
 * @property array<int, string>|null $account_ids
 * @property \Illuminate\Support\Carbon $expires_at
 * @property int         $frequency_per_day
 * @property bool        $recurring_indicator
 * @property \Illuminate\Support\Carbon|null $authorized_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Consent extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;
    use HasUuids;

    protected $table = 'consents';

    protected $fillable = [
        'tpp_id',
        'user_id',
        'status',
        'permissions',
        'account_ids',
        'expires_at',
        'frequency_per_day',
        'recurring_indicator',
        'authorized_at',
        'revoked_at',
    ];

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'status'              => ConsentStatus::class,
            'permissions'         => 'array',
            'account_ids'         => 'array',
            'expires_at'          => 'datetime',
            'authorized_at'       => 'datetime',
            'revoked_at'          => 'datetime',
            'recurring_indicator' => 'boolean',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** @param Builder<Consent> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ConsentStatus::AUTHORIZED->value)
            ->where('expires_at', '>', now());
    }

    /**
     * @param Builder<Consent> $query
     * @param int $userId
     */
    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * @param Builder<Consent> $query
     * @param string $tppId
     */
    public function scopeForTpp(Builder $query, string $tppId): void
    {
        $query->where('tpp_id', $tppId);
    }

    /** @return BelongsTo<TppRegistration, $this> */
    public function tppRegistration(): BelongsTo
    {
        return $this->belongsTo(TppRegistration::class, 'tpp_id', 'tpp_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<ConsentAccessLog, $this> */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(ConsentAccessLog::class);
    }
}
