<?php

declare(strict_types=1);

namespace App\Domain\Commerce\Services;

use App\Domain\Commerce\Contracts\OnChainSbtServiceInterface;
use App\Domain\Commerce\Contracts\TokenIssuerInterface;
use App\Domain\Commerce\Enums\TokenType;
use App\Domain\Commerce\Events\SoulboundTokenIssued;
use App\Domain\Commerce\Events\SoulboundTokenMintedOnChain;
use App\Domain\Commerce\Events\SoulboundTokenRevoked;
use App\Domain\Commerce\Events\SoulboundTokenRevokedOnChain;
use App\Domain\Commerce\ValueObjects\SoulboundToken;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Service for managing soulbound tokens (SBTs).
 *
 * Soulbound tokens are non-transferable tokens permanently bound to an identity.
 * They are useful for:
 * - Identity attestations
 * - Reputation credentials
 * - Membership badges
 * - Achievement tokens
 */
class SoulboundTokenService implements TokenIssuerInterface
{
    /**
     * In-memory revocation list (in production, this would be persisted).
     *
     * @var array<string, array{reason: string, revoked_at: string}>
     */
    private array $revocationList = [];

    public function __construct(
        private readonly string $issuerId = 'finaegis-issuer',
        private readonly ?OnChainSbtServiceInterface $onChainService = null,
    ) {
    }

    /**
     * Issue a new soulbound token.
     *
     * @param array<string, mixed> $metadata
     */
    public function issueToken(
        TokenType $type,
        string $recipientId,
        array $metadata,
    ): SoulboundToken {
        $tokenId = Str::uuid()->toString();
        $issuedAt = new DateTimeImmutable();

        // Calculate expiry based on metadata or default
        $validityDays = $metadata['validity_days'] ?? 365;
        $expiresAt = $validityDays > 0
            ? $issuedAt->modify("+{$validityDays} days")
            : null;

        // Remove internal metadata keys
        unset($metadata['validity_days']);

        $token = new SoulboundToken(
            tokenId: $tokenId,
            type: $type,
            issuerId: $this->issuerId,
            recipientId: $recipientId,
            metadata: $metadata,
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
        );

        Event::dispatch(new SoulboundTokenIssued(
            tokenId: $tokenId,
            tokenType: $type,
            issuerId: $this->issuerId,
            recipientId: $recipientId,
            issuedAt: $issuedAt,
        ));

        // Anchor on-chain if enabled
        $txHash = $this->anchorOnChain($token);
        if ($txHash !== null) {
            $metadata['on_chain_tx_hash'] = $txHash;

            // Recreate token with on-chain metadata
            $token = new SoulboundToken(
                tokenId: $tokenId,
                type: $type,
                issuerId: $this->issuerId,
                recipientId: $recipientId,
                metadata: $metadata,
                issuedAt: $issuedAt,
                expiresAt: $expiresAt,
            );
        }

        return $token;
    }

    /**
     * Issue a KYC verification badge.
     *
     * @param array<string, mixed> $verificationDetails
     */
    public function issueKycBadge(
        string $userId,
        int $verificationLevel,
        array $verificationDetails = [],
    ): SoulboundToken {
        return $this->issueToken(
            type: TokenType::SOULBOUND,
            recipientId: $userId,
            metadata: [
                'badge_type'         => 'kyc_verification',
                'verification_level' => $verificationLevel,
                'verified_at'        => (new DateTimeImmutable())->format('c'),
                'details'            => $verificationDetails,
                'validity_days'      => 365,
            ],
        );
    }

    /**
     * Issue a membership token.
     */
    public function issueMembershipToken(
        string $userId,
        string $tier,
        ?DateTimeImmutable $expiresAt = null,
    ): SoulboundToken {
        $validityDays = $expiresAt !== null
            ? max(1, (int) ((new DateTimeImmutable())->diff($expiresAt)->days))
            : 365;

        return $this->issueToken(
            type: TokenType::SOULBOUND,
            recipientId: $userId,
            metadata: [
                'badge_type'    => 'membership',
                'tier'          => $tier,
                'validity_days' => $validityDays,
            ],
        );
    }

    /**
     * Issue a reputation token.
     */
    public function issueReputationToken(
        string $userId,
        int $score,
        string $category,
    ): SoulboundToken {
        return $this->issueToken(
            type: TokenType::SOULBOUND,
            recipientId: $userId,
            metadata: [
                'badge_type'    => 'reputation',
                'score'         => $score,
                'category'      => $category,
                'calculated_at' => (new DateTimeImmutable())->format('c'),
                'validity_days' => 90, // Reputation scores update quarterly
            ],
        );
    }

    /**
     * Verify a token's authenticity.
     */
    public function verifyToken(SoulboundToken $token): bool
    {
        // Check basic validity
        if (! $token->isValid()) {
            return false;
        }

        // Check if token is revoked
        if ($this->isRevoked($token->tokenId)) {
            return false;
        }

        // Verify issuer
        if ($token->issuerId !== $this->issuerId) {
            return false;
        }

        // Verify token hash integrity
        $expectedHash = $this->calculateTokenHash($token);
        if ($token->getTokenHash() !== $expectedHash) {
            return false;
        }

        return true;
    }

    /**
     * Revoke a token.
     *
     * Persists revocation in cache to survive process restarts.
     */
    public function revokeToken(string $tokenId, string $reason): bool
    {
        if ($this->isRevoked($tokenId)) {
            return false;
        }

        $revokedAt = new DateTimeImmutable();

        $revocationData = [
            'reason'     => $reason,
            'revoked_at' => $revokedAt->format('c'),
        ];

        // Persist in both memory and cache
        $this->revocationList[$tokenId] = $revocationData;
        Cache::put("sbt_revoked:{$tokenId}", $revocationData);

        Event::dispatch(new SoulboundTokenRevoked(
            tokenId: $tokenId,
            reason: $reason,
            revokedAt: $revokedAt,
        ));

        // Revoke on-chain if enabled and on-chain token exists
        $this->revokeOnChain($tokenId);

        return true;
    }

    /**
     * Check if a token is revoked.
     *
     * Checks both in-memory cache and persistent cache store.
     */
    public function isRevoked(string $tokenId): bool
    {
        return isset($this->revocationList[$tokenId])
            || Cache::has("sbt_revoked:{$tokenId}");
    }

    /**
     * Get revocation details.
     *
     * @return array{reason: string, revoked_at: string}|null
     */
    public function getRevocationDetails(string $tokenId): ?array
    {
        return $this->revocationList[$tokenId]
            ?? Cache::get("sbt_revoked:{$tokenId}");
    }

    /**
     * Anchor a soulbound token on-chain by minting it on the configured network.
     *
     * Returns the transaction hash if successful, null if on-chain anchoring is disabled.
     */
    public function anchorOnChain(SoulboundToken $token): ?string
    {
        if (! $this->isOnChainAnchoringEnabled()) {
            return null;
        }

        if ($this->onChainService === null || ! $this->onChainService->isAvailable()) {
            Log::warning('On-chain SBT service unavailable, skipping anchoring', [
                'token_id' => $token->tokenId,
            ]);

            return null;
        }

        try {
            $contractAddress = (string) config('commerce.soulbound_tokens.contract_address', '');
            $tokenUri = "finaegis://sbt/{$token->tokenId}";

            $result = $this->onChainService->mintToken(
                contractAddress: $contractAddress,
                recipientAddress: $token->recipientId,
                tokenUri: $tokenUri,
                metadata: $token->metadata,
            );

            Event::dispatch(new SoulboundTokenMintedOnChain(
                tokenId: $token->tokenId,
                onChainTokenId: $result['token_id'],
                contractAddress: $result['contract_address'],
                txHash: $result['tx_hash'],
                network: $result['network'],
            ));

            return $result['tx_hash'];
        } catch (Throwable $e) {
            Log::error('On-chain SBT minting failed', [
                'token_id' => $token->tokenId,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Revoke a token on-chain if on-chain anchoring is enabled.
     */
    private function revokeOnChain(string $tokenId): void
    {
        if (! $this->isOnChainAnchoringEnabled()) {
            return;
        }

        if ($this->onChainService === null || ! $this->onChainService->isAvailable()) {
            return;
        }

        try {
            $contractAddress = (string) config('commerce.soulbound_tokens.contract_address', '');
            $onChainTokenId = (int) Cache::get("sbt_on_chain_id:{$tokenId}", 0);

            if ($onChainTokenId === 0) {
                return;
            }

            $result = $this->onChainService->revokeToken($contractAddress, $onChainTokenId);

            Event::dispatch(new SoulboundTokenRevokedOnChain(
                tokenId: $tokenId,
                onChainTokenId: $onChainTokenId,
                contractAddress: $result['contract_address'],
                txHash: $result['tx_hash'],
                network: $result['network'],
            ));
        } catch (Throwable $e) {
            Log::error('On-chain SBT revocation failed', [
                'token_id' => $tokenId,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function isOnChainAnchoringEnabled(): bool
    {
        return (bool) config('commerce.soulbound_tokens.on_chain_anchoring', false);
    }

    /**
     * Calculate HMAC token hash for verification.
     *
     * Includes all token fields to prevent undetected modification.
     */
    private function calculateTokenHash(SoulboundToken $token): string
    {
        $data = [
            'token_id'     => $token->tokenId,
            'type'         => $token->type->value,
            'issuer_id'    => $token->issuerId,
            'recipient_id' => $token->recipientId,
            'issued_at'    => $token->issuedAt->format('c'),
            'metadata'     => $token->metadata,
        ];

        if ($token->expiresAt !== null) {
            $data['expires_at'] = $token->expiresAt->format('c');
        }

        return hash_hmac('sha256', json_encode($data, JSON_THROW_ON_ERROR), config('app.key'));
    }
}
