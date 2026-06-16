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
 * @property string $session_id
 * @property string $agent_id
 * @property string $token_hash
 * @property bool $is_revoked
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $expires_at
 * @property Carbon|null $last_activity_at
 * @property Carbon|null $revoked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AgentSession extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'agent_sessions';

    protected $fillable = [
        'session_id',
        'agent_id',
        'token_hash',
        'is_revoked',
        'ip_address',
        'user_agent',
        'expires_at',
        'last_activity_at',
        'revoked_at',
    ];

    protected $casts = [
        'is_revoked'       => 'boolean',
        'expires_at'       => 'datetime',
        'last_activity_at' => 'datetime',
        'revoked_at'       => 'datetime',
    ];

    protected $hidden = [
        'token_hash',
    ];

    /**
     * Get the agent that owns this session.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id', 'agent_id');
    }

    /**
     * Check if the session is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the session is valid (not revoked and not expired).
     */
    public function isValid(): bool
    {
        return ! $this->is_revoked && ! $this->isExpired();
    }

    /**
     * Update the last activity timestamp.
     */
    public function touchActivity(): bool
    {
        $this->last_activity_at = now();

        return $this->save();
    }

    /**
     * Revoke the session.
     */
    public function revoke(): bool
    {
        $this->is_revoked = true;
        $this->revoked_at = now();

        return $this->save();
    }

    /**
     * Scope for active (non-revoked) sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_revoked', false);
    }

    /**
     * Scope for valid (active and not expired) sessions.
     */
    public function scopeValid($query)
    {
        return $query->active()
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired sessions.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
