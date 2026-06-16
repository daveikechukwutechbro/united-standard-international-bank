<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $key_id
 * @property string $agent_id
 * @property string $name
 * @property string $key_hash
 * @property string $key_prefix
 * @property array $scopes
 * @property bool $is_active
 * @property Carbon|null $expires_at
 * @property Carbon|null $last_used_at
 * @property Carbon|null $revoked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AgentApiKey extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'agent_api_keys';

    protected $fillable = [
        'key_id',
        'agent_id',
        'name',
        'key_hash',
        'key_prefix',
        'scopes',
        'is_active',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'scopes'       => 'array',
        'is_active'    => 'boolean',
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    protected $hidden = [
        'key_hash',
    ];

    /**
     * Get the agent that owns this API key.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id', 'agent_id');
    }

    /**
     * Check if the key is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the key is valid (active and not expired).
     */
    public function isValid(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * Check if the key has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return true; // Empty scopes means all scopes allowed
        }

        return in_array($scope, $this->scopes, true)
            || in_array('*', $this->scopes, true);
    }

    /**
     * Scope for active keys.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for valid (active and not expired) keys.
     */
    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
