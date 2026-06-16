<?php

declare(strict_types=1);

namespace App\Domain\FinancialInstitution\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $partner_id
 * @property string $category
 * @property string $provider
 * @property string $status
 * @property array<string, mixed>|null $config
 * @property string|null $webhook_url
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property int $error_count
 * @property string|null $last_error
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read FinancialInstitutionPartner $partner
 *
 * @method static Builder<static> active()
 * @method static Builder<static> forCategory(string $category)
 */
/** @use HasFactory<\Database\Factories\PartnerIntegrationFactory> */
class PartnerIntegration extends Model
{
    /** @use HasFactory<\Database\Factories\PartnerIntegrationFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $table = 'partner_integrations';

    protected $fillable = [
        'uuid',
        'partner_id',
        'category',
        'provider',
        'status',
        'config',
        'webhook_url',
        'last_synced_at',
        'error_count',
        'last_error',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config'         => 'encrypted:array',
            'metadata'       => 'array',
            'last_synced_at' => 'datetime',
            'error_count'    => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return BelongsTo<FinancialInstitutionPartner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(FinancialInstitutionPartner::class, 'partner_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function recordError(string $error): void
    {
        $this->update([
            'error_count' => $this->error_count + 1,
            'last_error'  => $error,
        ]);
    }

    public function markSynced(): void
    {
        $this->update([
            'last_synced_at' => now(),
            'error_count'    => 0,
            'last_error'     => null,
        ]);
    }
}
