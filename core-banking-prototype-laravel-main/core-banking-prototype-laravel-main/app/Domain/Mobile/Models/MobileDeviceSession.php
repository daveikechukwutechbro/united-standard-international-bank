<?php

declare(strict_types=1);

namespace App\Domain\Mobile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mobile device session model for tracking active mobile sessions.
 *
 * @property string $id
 * @property string $mobile_device_id
 * @property int $user_id
 * @property string $session_token
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $last_activity_at
 * @property \Carbon\Carbon $expires_at
 * @property bool $is_biometric_session
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> active()
 * @method static \Illuminate\Database\Eloquent\Builder<static> expired()
 */
class MobileDeviceSession extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'mobile_device_id',
        'user_id',
        'session_token',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'expires_at',
        'is_biometric_session',
    ];

    protected $casts = [
        'last_activity_at'     => 'datetime',
        'expires_at'           => 'datetime',
        'is_biometric_session' => 'boolean',
    ];

    protected $hidden = [
        'session_token',
    ];

    /**
     * Get the mobile device for this session.
     *
     * @return BelongsTo<MobileDevice, $this>
     */
    public function mobileDevice(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class);
    }

    /**
     * Get the user for this session.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the session is still valid.
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Check if the session is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Extend the session expiration.
     */
    public function extend(int $minutes = 60): void
    {
        $this->update([
            'last_activity_at' => now(),
            'expires_at'       => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Invalidate the session.
     */
    public function invalidate(): void
    {
        $this->delete();
    }

    /**
     * Scope to get only active sessions.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired sessions.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeExpired(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Generate a new session token.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
