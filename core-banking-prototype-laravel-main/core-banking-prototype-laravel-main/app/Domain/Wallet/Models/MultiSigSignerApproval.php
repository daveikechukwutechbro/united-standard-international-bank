<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Models;

use App\Domain\Shared\Traits\UsesTenantConnection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Multi-signature signer approval model.
 *
 * @property string $id
 * @property string $approval_request_id
 * @property string $signer_id
 * @property int $user_id
 * @property string $decision
 * @property string|null $signature
 * @property string|null $public_key
 * @property string|null $rejection_reason
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $decided_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MultiSigApprovalRequest $approvalRequest
 * @property-read MultiSigWalletSigner $signer
 * @property-read User $user
 */
class MultiSigSignerApproval extends Model
{
    use HasUuids;
    use UsesTenantConnection;

    public const DECISION_PENDING = 'pending';

    public const DECISION_APPROVED = 'approved';

    public const DECISION_REJECTED = 'rejected';

    protected $table = 'multi_sig_signer_approvals';

    protected $fillable = [
        'approval_request_id',
        'signer_id',
        'user_id',
        'decision',
        'signature',
        'public_key',
        'rejection_reason',
        'metadata',
        'decided_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'decided_at' => 'datetime',
    ];

    /**
     * Get the approval request this approval belongs to.
     *
     * @return BelongsTo<MultiSigApprovalRequest, $this>
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(MultiSigApprovalRequest::class, 'approval_request_id');
    }

    /**
     * Get the signer who made this approval.
     *
     * @return BelongsTo<MultiSigWalletSigner, $this>
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(MultiSigWalletSigner::class, 'signer_id');
    }

    /**
     * Get the user who made this approval.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get pending approvals.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('decision', self::DECISION_PENDING);
    }

    /**
     * Scope to get approved approvals.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('decision', self::DECISION_APPROVED);
    }

    /**
     * Scope to get rejected approvals.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('decision', self::DECISION_REJECTED);
    }

    /**
     * Check if the approval is pending.
     */
    public function isPending(): bool
    {
        return $this->decision === self::DECISION_PENDING;
    }

    /**
     * Check if the approval is approved.
     */
    public function isApproved(): bool
    {
        return $this->decision === self::DECISION_APPROVED;
    }

    /**
     * Check if the approval is rejected.
     */
    public function isRejected(): bool
    {
        return $this->decision === self::DECISION_REJECTED;
    }

    /**
     * Approve the request with a signature.
     */
    public function approve(string $signature, string $publicKey): void
    {
        $this->update([
            'decision'   => self::DECISION_APPROVED,
            'signature'  => $signature,
            'public_key' => $publicKey,
            'decided_at' => now(),
        ]);

        // Increment the signature count on the approval request
        $this->approvalRequest->incrementSignatureCount();
    }

    /**
     * Reject the request.
     */
    public function reject(?string $reason = null): void
    {
        $this->update([
            'decision'         => self::DECISION_REJECTED,
            'rejection_reason' => $reason,
            'decided_at'       => now(),
        ]);
    }
}
