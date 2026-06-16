<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_until
 */
class TenantRegionMapping extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'tenant_region_mappings';

    protected $fillable = [
        'tenant_id',
        'region',
        'is_primary',
        'effective_from',
        'effective_until',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary'      => 'boolean',
            'effective_from'  => 'datetime',
            'effective_until' => 'datetime',
            'metadata'        => 'array',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('effective_until')
                ->orWhere('effective_until', '>', now());
        })->where(function ($q) {
            $q->whereNull('effective_from')
                ->orWhere('effective_from', '<=', now());
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    public function isActive(): bool
    {
        $now = now();

        if ($this->effective_from && $this->effective_from->isFuture()) {
            return false;
        }
        if ($this->effective_until && $this->effective_until->isPast()) {
            return false;
        }

        return true;
    }
}
