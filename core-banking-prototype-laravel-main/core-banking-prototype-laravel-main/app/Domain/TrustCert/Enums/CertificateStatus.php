<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Enums;

/**
 * Certificate lifecycle status.
 */
enum CertificateStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Pending',
            self::ACTIVE    => 'Active',
            self::SUSPENDED => 'Suspended',
            self::REVOKED   => 'Revoked',
            self::EXPIRED   => 'Expired',
        };
    }

    public function isValid(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canSign(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::REVOKED, self::EXPIRED], true);
    }

    /**
     * @return array<CertificateStatus>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING   => [self::ACTIVE, self::REVOKED],
            self::ACTIVE    => [self::SUSPENDED, self::REVOKED, self::EXPIRED],
            self::SUSPENDED => [self::ACTIVE, self::REVOKED],
            self::REVOKED   => [],
            self::EXPIRED   => [],
        };
    }

    public function canTransitionTo(CertificateStatus $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
}
