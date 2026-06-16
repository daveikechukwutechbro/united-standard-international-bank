<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataTransferLog extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'data_transfer_logs';

    protected $fillable = [
        'tenant_id',
        'from_region',
        'to_region',
        'data_type',
        'reason',
        'approved_by',
        'status',
        'user_uuid',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeFromRegion($query, string $region)
    {
        return $query->where('from_region', $region);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeToRegion($query, string $region)
    {
        return $query->where('to_region', $region);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeCrossRegion($query)
    {
        return $query->whereColumn('from_region', '!=', 'to_region');
    }

    public function isCrossRegion(): bool
    {
        return $this->from_region !== $this->to_region;
    }
}
