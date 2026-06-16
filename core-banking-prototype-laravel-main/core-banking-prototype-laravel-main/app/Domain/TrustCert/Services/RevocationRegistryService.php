<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Services;

use App\Domain\TrustCert\Contracts\RevocationRegistryInterface;
use App\Domain\TrustCert\Enums\RevocationReason;
use App\Domain\TrustCert\Events\CredentialRevoked;
use App\Domain\TrustCert\ValueObjects\RevocationEntry;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Service for managing credential revocation registry.
 *
 * Implements W3C Verifiable Credentials Status List 2021.
 */
class RevocationRegistryService implements RevocationRegistryInterface
{
    /** @var array<string, RevocationEntry> */
    private array $revocations = [];

    /** @var array<string, array<string>> */
    private array $issuerIndex = [];

    /**
     * {@inheritDoc}
     */
    public function revoke(
        string $credentialId,
        RevocationReason $reason,
        ?string $revokedBy = null,
    ): RevocationEntry {
        if ($this->isRevoked($credentialId)) {
            throw new InvalidArgumentException("Credential {$credentialId} is already revoked");
        }

        $entryId = 'rev_' . Str::uuid()->toString();
        $revokedAt = new DateTimeImmutable();

        $entry = new RevocationEntry(
            entryId: $entryId,
            credentialId: $credentialId,
            reason: $reason,
            revokedAt: $revokedAt,
            revokedBy: $revokedBy,
        );

        $this->revocations[$credentialId] = $entry;

        Event::dispatch(new CredentialRevoked(
            credentialId: $credentialId,
            reason: $reason,
            revokedBy: $revokedBy,
            revokedAt: $revokedAt,
        ));

        return $entry;
    }

    /**
     * Revoke a credential with issuer tracking.
     */
    public function revokeWithIssuer(
        string $credentialId,
        string $issuerId,
        RevocationReason $reason,
        ?string $revokedBy = null,
        ?string $notes = null,
    ): RevocationEntry {
        if ($this->isRevoked($credentialId)) {
            throw new InvalidArgumentException("Credential {$credentialId} is already revoked");
        }

        $entryId = 'rev_' . Str::uuid()->toString();
        $revokedAt = new DateTimeImmutable();

        $entry = new RevocationEntry(
            entryId: $entryId,
            credentialId: $credentialId,
            reason: $reason,
            revokedAt: $revokedAt,
            issuerId: $issuerId,
            revokedBy: $revokedBy,
            notes: $notes,
        );

        $this->revocations[$credentialId] = $entry;

        // Index by issuer
        if (! isset($this->issuerIndex[$issuerId])) {
            $this->issuerIndex[$issuerId] = [];
        }
        $this->issuerIndex[$issuerId][] = $credentialId;

        Event::dispatch(new CredentialRevoked(
            credentialId: $credentialId,
            reason: $reason,
            revokedBy: $revokedBy,
            revokedAt: $revokedAt,
        ));

        return $entry;
    }

    /**
     * {@inheritDoc}
     */
    public function isRevoked(string $credentialId): bool
    {
        return isset($this->revocations[$credentialId]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRevocationEntry(string $credentialId): ?RevocationEntry
    {
        return $this->revocations[$credentialId] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getRevocationsByIssuer(string $issuerId): array
    {
        $credentialIds = $this->issuerIndex[$issuerId] ?? [];

        return array_map(
            fn (string $id) => $this->revocations[$id],
            $credentialIds,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getRevocationsSince(DateTimeInterface $since): array
    {
        return array_filter(
            $this->revocations,
            fn (RevocationEntry $entry) => $entry->revokedAt >= $since,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getRevocationCount(): int
    {
        return count($this->revocations);
    }

    /**
     * {@inheritDoc}
     */
    public function generateRevocationListHash(): string
    {
        $hashes = array_map(
            fn (RevocationEntry $entry) => $entry->getHash(),
            $this->revocations,
        );

        sort($hashes);

        return hash('sha256', implode('', $hashes));
    }

    /**
     * Remove a certificate hold (temporary revocation).
     */
    public function removeHold(string $credentialId): bool
    {
        $entry = $this->getRevocationEntry($credentialId);
        if ($entry === null) {
            return false;
        }

        if (! $entry->isHold()) {
            return false;
        }

        unset($this->revocations[$credentialId]);

        // Remove from issuer index
        if ($entry->issuerId !== null && isset($this->issuerIndex[$entry->issuerId])) {
            $this->issuerIndex[$entry->issuerId] = array_filter(
                $this->issuerIndex[$entry->issuerId],
                fn (string $id) => $id !== $credentialId,
            );
        }

        return true;
    }

    /**
     * Check multiple credentials at once.
     *
     * @param array<string> $credentialIds
     *
     * @return array<string, bool>
     */
    public function checkBatch(array $credentialIds): array
    {
        $results = [];
        foreach ($credentialIds as $id) {
            $results[$id] = $this->isRevoked($id);
        }

        return $results;
    }

    /**
     * Get revocations by reason.
     *
     * @return array<RevocationEntry>
     */
    public function getRevocationsByReason(RevocationReason $reason): array
    {
        return array_filter(
            $this->revocations,
            fn (RevocationEntry $entry) => $entry->reason === $reason,
        );
    }

    /**
     * Get all revocation entries.
     *
     * @return array<RevocationEntry>
     */
    public function getAllRevocations(): array
    {
        return array_values($this->revocations);
    }

    /**
     * Generate a W3C Status List 2021 compatible structure.
     *
     * @return array<string, mixed>
     */
    public function toStatusList2021(): array
    {
        $credentialIds = array_keys($this->revocations);

        return [
            '@context'          => ['https://www.w3.org/2018/credentials/v1', 'https://w3id.org/vc-status-list-2021/v1'],
            'id'                => 'urn:finaegis:revocation-list:' . time(),
            'type'              => ['VerifiableCredential', 'StatusList2021Credential'],
            'credentialSubject' => [
                'id'            => 'urn:finaegis:revocation-list:' . time() . '#list',
                'type'          => 'StatusList2021',
                'statusPurpose' => 'revocation',
                'encodedList'   => base64_encode(json_encode($credentialIds, JSON_THROW_ON_ERROR)),
            ],
            'validFrom' => (new DateTimeImmutable())->format('c'),
            'issuer'    => 'did:finaegis:issuer:revocation-authority',
        ];
    }
}
