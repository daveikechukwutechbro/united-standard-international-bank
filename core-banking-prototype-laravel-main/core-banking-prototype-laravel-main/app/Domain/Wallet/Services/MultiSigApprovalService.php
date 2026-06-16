<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Services;

use App\Domain\Wallet\Events\MultiSigApprovalCompleted;
use App\Domain\Wallet\Events\MultiSigApprovalCreated;
use App\Domain\Wallet\Events\MultiSigSignatureSubmitted;
use App\Domain\Wallet\Models\MultiSigApprovalRequest;
use App\Domain\Wallet\Models\MultiSigSignerApproval;
use App\Domain\Wallet\Models\MultiSigWallet;
use App\Domain\Wallet\ValueObjects\ApprovalStatus;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service for managing multi-signature approval requests.
 */
class MultiSigApprovalService
{
    /**
     * Create a new approval request for a transaction.
     *
     * @param  array<string, mixed>  $transactionData
     * @param  array<string, mixed>  $metadata
     */
    public function createApprovalRequest(
        MultiSigWallet $wallet,
        User $initiator,
        array $transactionData,
        string $requestType = MultiSigApprovalRequest::TYPE_TRANSACTION,
        array $metadata = [],
    ): MultiSigApprovalRequest {
        $this->validateWalletForApproval($wallet);
        $this->validateUserIsSignerOrOwner($wallet, $initiator);

        $rawDataToSign = $this->prepareDataToSign($wallet, $transactionData);
        $ttlSeconds = config('blockchain.multi_sig.approval_ttl_seconds', 86400);

        return DB::transaction(function () use (
            $wallet,
            $initiator,
            $transactionData,
            $requestType,
            $rawDataToSign,
            $ttlSeconds,
            $metadata
        ) {
            $approvalRequest = MultiSigApprovalRequest::create([
                'multi_sig_wallet_id' => $wallet->id,
                'initiator_user_id'   => $initiator->id,
                'status'              => MultiSigApprovalRequest::STATUS_PENDING,
                'request_type'        => $requestType,
                'transaction_data'    => $transactionData,
                'raw_data_to_sign'    => $rawDataToSign,
                'required_signatures' => $wallet->required_signatures,
                'current_signatures'  => 0,
                'metadata'            => $metadata,
                'expires_at'          => now()->addSeconds($ttlSeconds),
            ]);

            // Create pending signer approvals for all active signers
            foreach ($wallet->activeSigners as $signer) {
                MultiSigSignerApproval::create([
                    'approval_request_id' => $approvalRequest->id,
                    'signer_id'           => $signer->id,
                    'user_id'             => $signer->user_id ?? $initiator->id,
                    'decision'            => MultiSigSignerApproval::DECISION_PENDING,
                ]);
            }

            $tenantId = $this->getTenantId();
            event(new MultiSigApprovalCreated(
                tenantId: $tenantId,
                approvalRequestId: $approvalRequest->id,
                walletId: $wallet->id,
                walletName: $wallet->name,
                initiatorUserId: $initiator->id,
                requiredSignatures: $wallet->required_signatures,
                transactionData: $transactionData,
                expiresAt: $approvalRequest->expires_at->toIso8601String(),
            ));

            return $approvalRequest;
        });
    }

    /**
     * Submit a signature for an approval request.
     */
    public function submitSignature(
        MultiSigApprovalRequest $request,
        User $user,
        string $signature,
        string $publicKey,
    ): MultiSigSignerApproval {
        $this->validateRequestForSignature($request);

        $signerApproval = $this->getSignerApprovalForUser($request, $user);

        if ($signerApproval === null) {
            throw new InvalidArgumentException('User is not a signer for this request');
        }

        if (! $signerApproval->isPending()) {
            throw new RuntimeException('User has already submitted their decision');
        }

        // Validate the signature (in production, this would verify cryptographically)
        $this->validateSignature($request, $signature, $publicKey);

        return DB::transaction(function () use ($signerApproval, $signature, $publicKey, $request) {
            $signerApproval->approve($signature, $publicKey);

            $request->refresh();

            $tenantId = $this->getTenantId();
            event(new MultiSigSignatureSubmitted(
                tenantId: $tenantId,
                approvalRequestId: $request->id,
                walletId: $request->multi_sig_wallet_id,
                signerId: $signerApproval->signer_id,
                signerName: $signerApproval->signer->getDisplayName(),
                userId: $signerApproval->user_id,
                currentSignatures: $request->current_signatures,
                requiredSignatures: $request->required_signatures,
                quorumReached: $request->hasReachedQuorum(),
            ));

            return $signerApproval;
        });
    }

    /**
     * Reject an approval request.
     */
    public function rejectRequest(
        MultiSigApprovalRequest $request,
        User $user,
        ?string $reason = null,
    ): MultiSigSignerApproval {
        $this->validateRequestForSignature($request);

        $signerApproval = $this->getSignerApprovalForUser($request, $user);

        if ($signerApproval === null) {
            throw new InvalidArgumentException('User is not a signer for this request');
        }

        if (! $signerApproval->isPending()) {
            throw new RuntimeException('User has already submitted their decision');
        }

        return DB::transaction(function () use ($signerApproval, $reason) {
            $signerApproval->reject($reason);

            return $signerApproval;
        });
    }

    /**
     * Broadcast the transaction once quorum is reached.
     */
    public function broadcastTransaction(MultiSigApprovalRequest $request): void
    {
        if (! $request->hasReachedQuorum()) {
            throw new RuntimeException('Cannot broadcast: quorum not reached');
        }

        if (
            ! in_array($request->status, [
            MultiSigApprovalRequest::STATUS_PENDING,
            MultiSigApprovalRequest::STATUS_APPROVED,
            ], true)
        ) {
            throw new RuntimeException("Cannot broadcast request with status: {$request->status}");
        }

        DB::transaction(function () use ($request) {
            $request->markAsBroadcasting();

            try {
                // Get all collected signatures
                $signatures = $request->getCollectedSignatures();

                // In production, this would actually broadcast to the blockchain
                $transactionHash = $this->broadcastToBlockchain(
                    $request->wallet,
                    $request->transaction_data,
                    $signatures,
                );

                $request->markAsCompleted($transactionHash);

                $tenantId = $this->getTenantId();
                event(new MultiSigApprovalCompleted(
                    tenantId: $tenantId,
                    approvalRequestId: $request->id,
                    walletId: $request->multi_sig_wallet_id,
                    walletName: $request->wallet->name,
                    transactionHash: $transactionHash,
                    status: 'completed',
                ));
            } catch (Exception $e) {
                $request->markAsFailed($e->getMessage());

                $tenantId = $this->getTenantId();
                event(new MultiSigApprovalCompleted(
                    tenantId: $tenantId,
                    approvalRequestId: $request->id,
                    walletId: $request->multi_sig_wallet_id,
                    walletName: $request->wallet->name,
                    transactionHash: '',
                    status: 'failed',
                    errorMessage: $e->getMessage(),
                ));

                throw $e;
            }
        });
    }

    /**
     * Cancel an approval request (only by initiator or wallet owner).
     */
    public function cancelRequest(MultiSigApprovalRequest $request, User $user): void
    {
        if (! $request->isPending()) {
            throw new RuntimeException('Can only cancel pending requests');
        }

        $wallet = $request->wallet;
        if ($request->initiator_user_id !== $user->id && $wallet->user_id !== $user->id) {
            throw new InvalidArgumentException('Only the initiator or wallet owner can cancel the request');
        }

        $request->markAsCancelled();
    }

    /**
     * Get approval status for a request.
     */
    public function getApprovalStatus(MultiSigApprovalRequest $request): ApprovalStatus
    {
        $request->load(['signerApprovals.signer']);

        return ApprovalStatus::fromRequest($request);
    }

    /**
     * Get pending approval requests for a user.
     *
     * @return Collection<int, MultiSigApprovalRequest>
     */
    public function getPendingRequestsForUser(User $user): Collection
    {
        return MultiSigApprovalRequest::with(['wallet', 'initiator', 'signerApprovals.signer'])
            ->whereHas('signerApprovals', function ($query) use ($user) {
                /** @phpstan-ignore-next-line (valid Eloquent column names) */
                $query->where(['user_id' => $user->id, 'decision' => MultiSigSignerApproval::DECISION_PENDING]);
            })
            ->awaitingApproval()
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Get requests for a specific wallet.
     *
     * @return Collection<int, MultiSigApprovalRequest>
     */
    public function getRequestsForWallet(
        MultiSigWallet $wallet,
        ?string $status = null,
        int $limit = 50,
    ): Collection {
        $query = MultiSigApprovalRequest::with(['initiator', 'signerApprovals.signer'])
            ->forWallet($wallet->id);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Expire old approval requests.
     */
    public function expireOldRequests(): int
    {
        $expiredCount = 0;

        MultiSigApprovalRequest::pending()
            ->expired()
            ->chunkById(100, function ($requests) use (&$expiredCount) {
                foreach ($requests as $request) {
                    $request->markAsExpired();
                    $expiredCount++;
                }
            });

        return $expiredCount;
    }

    /**
     * Get signer approval for a specific user.
     */
    private function getSignerApprovalForUser(MultiSigApprovalRequest $request, User $user): ?MultiSigSignerApproval
    {
        return $request->signerApprovals()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Validate wallet is ready for approval requests.
     */
    private function validateWalletForApproval(MultiSigWallet $wallet): void
    {
        if (! $wallet->isActive()) {
            throw new RuntimeException("Wallet is not active (status: {$wallet->status})");
        }

        if (! $wallet->isFullySetUp()) {
            throw new RuntimeException('Wallet does not have all required signers');
        }

        // Check for existing pending requests (optional: configurable limit)
        $maxPending = config('blockchain.hardware_wallets.security.max_pending_requests', 5);
        $pendingCount = $wallet->pendingApprovalRequests()->count();

        if ($pendingCount >= $maxPending) {
            throw new RuntimeException("Maximum pending requests ({$maxPending}) reached for this wallet");
        }
    }

    /**
     * Validate user is a signer or owner of the wallet.
     */
    private function validateUserIsSignerOrOwner(MultiSigWallet $wallet, User $user): void
    {
        if ($wallet->user_id === $user->id) {
            return; // Owner can always create requests
        }

        if ($wallet->isUserSigner($user->id)) {
            return; // Active signer can create requests
        }

        throw new InvalidArgumentException('User is not authorized to create approval requests for this wallet');
    }

    /**
     * Validate request can accept signatures.
     */
    private function validateRequestForSignature(MultiSigApprovalRequest $request): void
    {
        if (! $request->isPending()) {
            throw new RuntimeException("Request is not pending (status: {$request->status})");
        }

        if ($request->isExpired()) {
            $request->markAsExpired();
            throw new RuntimeException('Request has expired');
        }
    }

    /**
     * Prepare the data to be signed.
     *
     * @param  array<string, mixed>  $transactionData
     */
    private function prepareDataToSign(MultiSigWallet $wallet, array $transactionData): string
    {
        // Create a deterministic hash of the transaction data
        $dataToHash = json_encode([
            'wallet_id'        => $wallet->id,
            'wallet_address'   => $wallet->address,
            'chain'            => $wallet->chain,
            'transaction_data' => $transactionData,
            'timestamp'        => now()->toIso8601String(),
            'nonce'            => Str::random(32),
        ], JSON_THROW_ON_ERROR);

        return hash('sha256', $dataToHash);
    }

    /**
     * Validate a signature.
     * In production, this would cryptographically verify the signature.
     */
    private function validateSignature(
        MultiSigApprovalRequest $request,
        string $signature,
        string $publicKey,
    ): void {
        // Verify signature format
        if (strlen($signature) < 64) {
            throw new InvalidArgumentException('Invalid signature format');
        }

        if (strlen($publicKey) < 64) {
            throw new InvalidArgumentException('Invalid public key format');
        }

        // In production, cryptographically verify:
        // 1. The signature is valid for the raw_data_to_sign
        // 2. The public key belongs to an active signer
        // 3. The signature matches the expected format for the chain
    }

    /**
     * Broadcast transaction to the blockchain.
     * In production, this would use the appropriate blockchain connector.
     *
     * @param  array<string, mixed>  $transactionData
     * @param  array<int, array{signer_id: string, signature: string, public_key: string}>  $signatures
     */
    private function broadcastToBlockchain(
        MultiSigWallet $wallet,
        array $transactionData,
        array $signatures,
    ): string {
        // In production:
        // 1. Construct the multi-sig transaction with all signatures
        // 2. Use the appropriate blockchain connector
        // 3. Broadcast the transaction
        // 4. Return the transaction hash

        // For now, generate a mock transaction hash
        $hashData = json_encode([
            'wallet_id' => $wallet->id,
            'chain'     => $wallet->chain,
            'tx_data'   => $transactionData,
            'sig_count' => count($signatures),
            'timestamp' => now()->timestamp,
        ], JSON_THROW_ON_ERROR);

        return '0x' . hash('sha256', $hashData);
    }

    /**
     * Get the current tenant ID.
     */
    private function getTenantId(): string
    {
        if (function_exists('tenant') && tenant()) {
            return (string) tenant()->getTenantKey();
        }

        return 'default';
    }
}
