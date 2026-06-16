<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

/**
 * Multi-signature wallet model.
 *
 * @property string $id
 * @property int $user_id
 * @property string $name
 * @property string|null $address
 * @property string $chain
 * @property int $required_signatures
 * @property int $total_signers
 * @property string $status
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Collection<int, MultiSigWalletSigner> $signers
 * @property-read Collection<int, MultiSigApprovalRequest> $approvalRequests
 */
class MultiSigWallet extends Model
{
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    public const STATUS_PENDING_SETUP = 'pending_setup';

    public const STATUS_AWAITING_SIGNERS = 'awaiting_signers';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'multi_sig_wallets';

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'chain',
        'required_signatures',
        'total_signers',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata'            => 'array',
        'required_signatures' => 'integer',
        'total_signers'       => 'integer',
    ];

    /**
     * Get the user that owns this multi-sig wallet.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the signers for this wallet.
     *
     * @return HasMany<MultiSigWalletSigner, $this>
     */
    public function signers(): HasMany
    {
        return $this->hasMany(MultiSigWalletSigner::class)->orderBy('signer_order');
    }

    /**
     * Get active signers only.
     *
     * @return HasMany<MultiSigWalletSigner, $this>
     */
    public function activeSigners(): HasMany
    {
        return $this->signers()->where('is_active', true);
    }

    /**
     * Get the approval requests for this wallet.
     *
     * @return HasMany<MultiSigApprovalRequest, $this>
     */
    public function approvalRequests(): HasMany
    {
        return $this->hasMany(MultiSigApprovalRequest::class);
    }

    /**
     * Get pending approval requests.
     *
     * @return HasMany<MultiSigApprovalRequest, $this>
     */
    public function pendingApprovalRequests(): HasMany
    {
        return $this->approvalRequests()
            ->where('status', MultiSigApprovalRequest::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope to get active wallets.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get wallets for a specific chain.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForChain(Builder $query, string $chain): Builder
    {
        return $query->where('chain', $chain);
    }

    /**
     * Scope to get wallets owned by a user.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get wallets where user is a signer.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereUserIsSigner(Builder $query, int $userId): Builder
    {
        return $query->whereHas('signers', function (Builder $q) use ($userId) {
            // @phpstan-ignore-next-line (valid Eloquent column names)
            $q->where('user_id', $userId)->where('is_active', true);
        });
    }

    /**
     * Check if the wallet is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the wallet is fully set up with all signers.
     */
    public function isFullySetUp(): bool
    {
        return $this->activeSigners()->count() >= $this->total_signers;
    }

    /**
     * Check if a user is an active signer of this wallet.
     */
    public function isUserSigner(int $userId): bool
    {
        return $this->activeSigners()->where('user_id', $userId)->exists();
    }

    /**
     * Get the signature scheme description (e.g., "2-of-3").
     */
    public function getSchemeDescription(): string
    {
        return "{$this->required_signatures}-of-{$this->total_signers}";
    }

    /**
     * Activate the wallet (after all signers are added).
     */
    public function activate(): void
    {
        if (! $this->isFullySetUp()) {
            throw new RuntimeException('Cannot activate wallet: not all signers have been added');
        }

        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Suspend the wallet.
     */
    public function suspend(): void
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    /**
     * Archive the wallet.
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Set the wallet address.
     */
    public function setAddress(string $address): void
    {
        $this->update(['address' => $address]);
    }
}
