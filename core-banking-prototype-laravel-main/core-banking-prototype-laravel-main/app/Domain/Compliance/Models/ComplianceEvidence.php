<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceEvidence extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'compliance_evidence';

    protected $fillable = [
        'tenant_id',
        'evidence_type',
        'period',
        'data',
        'integrity_hash',
        'collected_by',
        'metadata',
    ];

    protected $casts = [
        'data'       => 'array',
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeForType($query, string $type)
    {
        return $query->where('evidence_type', $type);
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function verifyIntegrity(): bool
    {
        return hash('sha256', json_encode($this->data)) === $this->integrity_hash;
    }
}
