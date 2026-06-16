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

/**
 * Multi-signature approval request model.
 *
 * @property string $id
 * @property string $multi_sig_wallet_id
 * @property int $initiator_user_id
 * @property string $status
 * @property string $request_type
 * @property array<string, mixed> $transaction_data
 * @property string $raw_data_to_sign
 * @property int $required_signatures
 * @property int $current_signatures
 * @property string|null $transaction_hash
 * @property string|null $error_message
 * @property array<string, mixed>|null $metadata
 * @property Carbon $expires_at
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read MultiSigWallet $wallet
 * @property-read User $initiator
 * @property-read Collection<int, MultiSigSignerApproval> $signerApprovals
 */
class MultiSigApprovalRequest extends Model
{
    use HasUuids;
    use SoftDeletes;
    use UsesTenantConnection;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_BROADCASTING = 'broadcasting';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const TYPE_TRANSACTION = 'transaction';

    public const TYPE_CONFIG_CHANGE = 'config_change';

    public const TYPE_ADD_SIGNER = 'add_signer';

    public const TYPE_REMOVE_SIGNER = 'remove_signer';

    protected $table = 'multi_sig_approval_requests';

    protected $fillable = [
        'multi_sig_wallet_id',
        'initiator_user_id',
        'status',
        'request_type',
        'transaction_data',
        'raw_data_to_sign',
        'required_signatures',
        'current_signatures',
        'transaction_hash',
        'error_message',
        'metadata',
        'expires_at',
        'completed_at',
    ];

    protected $casts = [
        'transaction_data'    => 'array',
        'metadata'            => 'array',
        'required_signatures' => 'integer',
        'current_signatures'  => 'integer',
        'expires_at'          => 'datetime',
        'completed_at'        => 'datetime',
    ];

    /**
     * Get the multi-sig wallet for this request.
     *
     * @return BelongsTo<MultiSigWallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(MultiSigWallet::class, 'multi_sig_wallet_id');
    }

    /**
     * Get the user who initiated this request.
     *
     * @return BelongsTo<User, $this>
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_user_id');
    }

    /**
     * Get the signer approvals for this request.
     *
     * @return HasMany<MultiSigSignerApproval, $this>
     */
    public function signerApprovals(): HasMany
    {
        return $this->hasMany(MultiSigSignerApproval::class, 'approval_request_id');
    }

    /**
     * Get approved signer approvals.
     *
     * @return HasMany<MultiSigSignerApproval, $this>
     */
    public function approvedSignerApprovals(): HasMany
    {
        return $this->signerApprovals()->where('decision', MultiSigSignerApproval::DECISION_APPROVED);
    }

    /**
     * Scope to get pending requests.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get non-expired requests.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired requests.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to get requests awaiting approval.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query->pending()->notExpired();
    }

    /**
     * Scope to get requests for a specific wallet.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForWallet(Builder $query, string $walletId): Builder
    {
        return $query->where('multi_sig_wallet_id', $walletId);
    }

    /**
     * Check if the request is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the request has reached the required number of signatures.
     */
    public function hasReachedQuorum(): bool
    {
        return $this->current_signatures >= $this->required_signatures;
    }

    /**
     * Check if the request can accept more signatures.
     */
    public function canAcceptSignature(): bool
    {
        return $this->isPending() && ! $this->isExpired() && ! $this->hasReachedQuorum();
    }

    /**
     * Check if a user has already signed this request.
     */
    public function hasUserSigned(int $userId): bool
    {
        return $this->signerApprovals()
            ->where('user_id', $userId)
            ->where('decision', '!=', MultiSigSignerApproval::DECISION_PENDING)
            ->exists();
    }

    /**
     * Get the number of remaining signatures needed.
     */
    public function getRemainingSignaturesCount(): int
    {
        return max(0, $this->required_signatures - $this->current_signatures);
    }

    /**
     * Mark the request as approved (quorum reached).
     */
    public function markAsApproved(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Mark the request as rejected.
     */
    public function markAsRejected(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Mark the request as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Mark the request as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Mark the request as broadcasting.
     */
    public function markAsBroadcasting(): void
    {
        $this->update(['status' => self::STATUS_BROADCASTING]);
    }

    /**
     * Mark the request as completed.
     */
    public function markAsCompleted(string $transactionHash): void
    {
        $this->update([
            'status'           => self::STATUS_COMPLETED,
            'transaction_hash' => $transactionHash,
            'completed_at'     => now(),
        ]);
    }

    /**
     * Mark the request as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status'        => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Increment the signature count.
     */
    public function incrementSignatureCount(): void
    {
        $this->increment('current_signatures');
        $this->refresh();

        if ($this->hasReachedQuorum()) {
            $this->markAsApproved();
        }
    }

    /**
     * Get all collected signatures.
     *
     * @return array<int, array{signer_id: string, signature: string, public_key: string}>
     */
    public function getCollectedSignatures(): array
    {
        return $this->approvedSignerApprovals()
            ->whereNotNull('signature')
            ->get()
            ->map(fn (MultiSigSignerApproval $approval) => [
                'signer_id'  => $approval->signer_id,
                'signature'  => $approval->signature,
                'public_key' => $approval->public_key,
            ])
            ->toArray();
    }
}
