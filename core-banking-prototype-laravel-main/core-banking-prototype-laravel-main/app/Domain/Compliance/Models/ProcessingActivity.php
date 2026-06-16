<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * GDPR Article 30 — Record of Processing Activities (ROPA).
 */
class ProcessingActivity extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'processing_activities';

    protected $fillable = [
        'tenant_id',
        'name',
        'purpose',
        'legal_basis',
        'data_categories',
        'data_subjects',
        'recipients',
        'retention_period',
        'international_transfers',
        'security_measures',
        'controller_name',
        'controller_contact',
        'dpo_contact',
        'status',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_categories'         => 'array',
            'data_subjects'           => 'array',
            'recipients'              => 'array',
            'international_transfers' => 'array',
            'metadata'                => 'array',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForLegalBasis($query, string $basis)
    {
        return $query->where('legal_basis', $basis);
    }

    public function isComplete(): bool
    {
        return $this->name
            && $this->purpose
            && $this->legal_basis
            && $this->data_categories
            && $this->recipients;
    }
}
