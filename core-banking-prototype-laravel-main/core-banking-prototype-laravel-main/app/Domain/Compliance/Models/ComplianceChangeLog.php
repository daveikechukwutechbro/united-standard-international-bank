<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceChangeLog extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'compliance_change_logs';

    protected $fillable = [
        'tenant_id',
        'change_type',
        'description',
        'old_values',
        'new_values',
        'changed_by',
        'environment',
        'ticket_reference',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeForType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    public function scopeForEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }
}
