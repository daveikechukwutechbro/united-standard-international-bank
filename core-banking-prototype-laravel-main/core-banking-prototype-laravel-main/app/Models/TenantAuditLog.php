<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log entry for tenant lifecycle events.
 *
 * @property int $id
 * @property string $tenant_id
 * @property int|null $user_id
 * @property string $action
 * @property array<string, mixed>|null $before_data
 * @property array<string, mixed>|null $after_data
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 */
class TenantAuditLog extends Model
{
    /** @var string */
    protected $table = 'tenant_audit_logs';

    /** @var bool */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'before_data',
        'after_data',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before_data' => 'array',
            'after_data'  => 'array',
            'created_at'  => 'datetime',
        ];
    }

    /**
     * Get the tenant this audit log belongs to.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
