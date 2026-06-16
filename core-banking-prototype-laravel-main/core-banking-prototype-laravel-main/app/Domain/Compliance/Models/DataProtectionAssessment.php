<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Data Protection Impact Assessment (DPIA) — GDPR Article 35.
 *
 * @property Carbon|null $approved_at
 */
class DataProtectionAssessment extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'data_protection_assessments';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'processing_activity_id',
        'risks',
        'mitigations',
        'risk_score',
        'status',
        'assessor',
        'reviewer',
        'approved_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'risks'       => 'array',
            'mitigations' => 'array',
            'risk_score'  => 'integer',
            'approved_at' => 'datetime',
            'metadata'    => 'array',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>=', 70);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->approved_at !== null;
    }

    public function getRiskLevel(): string
    {
        return match (true) {
            $this->risk_score >= 80 => 'critical',
            $this->risk_score >= 60 => 'high',
            $this->risk_score >= 40 => 'medium',
            default                 => 'low',
        };
    }
}
