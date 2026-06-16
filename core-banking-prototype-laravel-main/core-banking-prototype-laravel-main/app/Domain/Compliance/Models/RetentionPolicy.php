<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Data retention policy — configures auto-delete/anonymize/archive by data type.
 *
 * @property Carbon|null $last_enforced_at
 */
class RetentionPolicy extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'retention_policies';

    protected $fillable = [
        'tenant_id',
        'data_type',
        'model_class',
        'retention_days',
        'action',
        'enabled',
        'description',
        'last_enforced_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'retention_days'   => 'integer',
            'enabled'          => 'boolean',
            'last_enforced_at' => 'datetime',
            'metadata'         => 'array',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForDataType($query, string $dataType)
    {
        return $query->where('data_type', $dataType);
    }

    public function isOverdue(): bool
    {
        if (! $this->last_enforced_at) {
            return true;
        }

        return $this->last_enforced_at->addDays($this->retention_days)->isPast();
    }
}
