<?php

declare(strict_types=1);

namespace App\Domain\Wallet\ValueObjects;

use App\Domain\Wallet\Models\MultiSigApprovalRequest;
use App\Domain\Wallet\Models\MultiSigSignerApproval;
use Carbon\Carbon;

/**
 * Value object representing the status of a multi-sig approval request.
 */
final readonly class ApprovalStatus
{
    /**
     * @param  array<int, array{signer_id: string, signer_name: string, decision: string, decided_at: ?Carbon}>  $signerStatuses
     */
    private function __construct(
        public string $requestId,
        public string $status,
        public int $requiredSignatures,
        public int $currentSignatures,
        public int $remainingSignatures,
        public bool $isExpired,
        public ?Carbon $expiresAt,
        public ?Carbon $completedAt,
        public array $signerStatuses,
        public ?string $transactionHash,
        public ?string $errorMessage,
    ) {
    }

    /**
     * Create from an approval request model.
     */
    public static function fromRequest(MultiSigApprovalRequest $request): self
    {
        $signerStatuses = $request->signerApprovals
            ->map(fn (MultiSigSignerApproval $approval) => [
                'signer_id'   => $approval->signer_id,
                'signer_name' => $approval->signer->getDisplayName(),
                'decision'    => $approval->decision,
                'decided_at'  => $approval->decided_at,
            ])
            ->toArray();

        return new self(
            requestId: $request->id,
            status: $request->status,
            requiredSignatures: $request->required_signatures,
            currentSignatures: $request->current_signatures,
            remainingSignatures: $request->getRemainingSignaturesCount(),
            isExpired: $request->isExpired(),
            expiresAt: $request->expires_at,
            completedAt: $request->completed_at,
            signerStatuses: $signerStatuses,
            transactionHash: $request->transaction_hash,
            errorMessage: $request->error_message,
        );
    }

    /**
     * Check if the approval is pending.
     */
    public function isPending(): bool
    {
        return $this->status === MultiSigApprovalRequest::STATUS_PENDING;
    }

    /**
     * Check if the approval has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === MultiSigApprovalRequest::STATUS_APPROVED;
    }

    /**
     * Check if the approval has been completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === MultiSigApprovalRequest::STATUS_COMPLETED;
    }

    /**
     * Check if the approval has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === MultiSigApprovalRequest::STATUS_FAILED;
    }

    /**
     * Check if the approval can accept more signatures.
     */
    public function canAcceptSignature(): bool
    {
        return $this->isPending() && ! $this->isExpired && $this->remainingSignatures > 0;
    }

    /**
     * Get the progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->requiredSignatures === 0) {
            return 0.0;
        }

        return round(($this->currentSignatures / $this->requiredSignatures) * 100, 2);
    }

    /**
     * Get time remaining until expiration.
     */
    public function getTimeRemaining(): ?string
    {
        if ($this->expiresAt === null || $this->isExpired) {
            return null;
        }

        return $this->expiresAt->diffForHumans(['parts' => 2, 'short' => true]);
    }

    /**
     * Convert to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'request_id'           => $this->requestId,
            'status'               => $this->status,
            'required_signatures'  => $this->requiredSignatures,
            'current_signatures'   => $this->currentSignatures,
            'remaining_signatures' => $this->remainingSignatures,
            'progress_percentage'  => $this->getProgressPercentage(),
            'is_expired'           => $this->isExpired,
            'can_accept_signature' => $this->canAcceptSignature(),
            'expires_at'           => $this->expiresAt?->toIso8601String(),
            'time_remaining'       => $this->getTimeRemaining(),
            'completed_at'         => $this->completedAt?->toIso8601String(),
            'transaction_hash'     => $this->transactionHash,
            'error_message'        => $this->errorMessage,
            'signer_statuses'      => array_map(fn (array $s) => [
                'signer_id'   => $s['signer_id'],
                'signer_name' => $s['signer_name'],
                'decision'    => $s['decision'],
                'decided_at'  => $s['decided_at']?->toIso8601String(),
            ], $this->signerStatuses),
        ];
    }
}
