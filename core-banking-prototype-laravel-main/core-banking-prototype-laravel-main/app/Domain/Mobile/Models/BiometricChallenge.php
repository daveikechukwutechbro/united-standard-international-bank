<?php

declare(strict_types=1);

namespace App\Domain\Mobile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Biometric challenge model for secure device-bound authentication.
 *
 * @property string $id
 * @property string $mobile_device_id
 * @property int $user_id
 * @property string $challenge
 * @property string $status
 * @property string|null $ip_address
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $verified_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static> expired()
 */
class BiometricChallenge extends Model
{
    use HasFactory;
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FAILED = 'failed';

    public const CHALLENGE_TTL_SECONDS = 300; // 5 minutes

    protected $fillable = [
        'mobile_device_id',
        'user_id',
        'challenge',
        'status',
        'ip_address',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $hidden = [
        'challenge',
    ];

    /**
     * Get the mobile device for this challenge.
     *
     * @return BelongsTo<MobileDevice, $this>
     */
    public function mobileDevice(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class);
    }

    /**
     * Get the user for this challenge.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get pending challenges.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired challenges.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeExpired(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', self::STATUS_PENDING);
    }

    /**
     * Check if the challenge is still valid.
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at->isFuture();
    }

    /**
     * Check if the challenge is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark the challenge as verified.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'status'      => self::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);
    }

    /**
     * Mark the challenge as failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }

    /**
     * Mark the challenge as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Generate a new cryptographic challenge.
     */
    public static function generateChallenge(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Create a new challenge for a device.
     */
    public static function createForDevice(MobileDevice $device, ?string $ipAddress = null): self
    {
        return self::create([
            'mobile_device_id' => $device->id,
            'user_id'          => $device->user_id,
            'challenge'        => self::generateChallenge(),
            'status'           => self::STATUS_PENDING,
            'ip_address'       => $ipAddress,
            'expires_at'       => now()->addSeconds(self::CHALLENGE_TTL_SECONDS),
        ]);
    }
}
