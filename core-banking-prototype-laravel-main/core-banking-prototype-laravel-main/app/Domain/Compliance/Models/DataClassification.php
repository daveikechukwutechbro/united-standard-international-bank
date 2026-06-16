<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataClassification extends Model
{
    use UsesTenantConnection;
    use HasFactory;
    use HasUuids;

    protected $table = 'data_classifications';

    protected $fillable = [
        'tenant_id',
        'model_class',
        'field_name',
        'classification_level',
        'encryption_required',
        'encryption_verified',
        'access_logging_enabled',
        'retention_days',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'encryption_required'    => 'boolean',
            'encryption_verified'    => 'boolean',
            'access_logging_enabled' => 'boolean',
            'retention_days'         => 'integer',
            'metadata'               => 'array',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForModel($query, string $modelClass)
    {
        return $query->where('model_class', $modelClass);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeForLevel($query, string $level)
    {
        return $query->where('classification_level', $level);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeRequiringEncryption($query)
    {
        return $query->where('encryption_required', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeUnverifiedEncryption($query)
    {
        return $query->where('encryption_required', true)
            ->where('encryption_verified', false);
    }

    public function isCompliant(): bool
    {
        if ($this->encryption_required && ! $this->encryption_verified) {
            return false;
        }

        return true;
    }
}
