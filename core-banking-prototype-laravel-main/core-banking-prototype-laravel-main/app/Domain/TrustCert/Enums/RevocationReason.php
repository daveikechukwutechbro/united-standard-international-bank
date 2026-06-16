<?php

declare(strict_types=1);

namespace App\Domain\TrustCert\Enums;

/**
 * Reasons for certificate or credential revocation.
 *
 * Based on RFC 5280 CRLReason.
 */
enum RevocationReason: string
{
    case UNSPECIFIED = 'unspecified';
    case KEY_COMPROMISE = 'key_compromise';
    case CA_COMPROMISE = 'ca_compromise';
    case AFFILIATION_CHANGED = 'affiliation_changed';
    case SUPERSEDED = 'superseded';
    case CESSATION_OF_OPERATION = 'cessation_of_operation';
    case CERTIFICATE_HOLD = 'certificate_hold';
    case PRIVILEGE_WITHDRAWN = 'privilege_withdrawn';
    case AA_COMPROMISE = 'aa_compromise';

    public function label(): string
    {
        return match ($this) {
            self::UNSPECIFIED            => 'Unspecified',
            self::KEY_COMPROMISE         => 'Key Compromise',
            self::CA_COMPROMISE          => 'CA Compromise',
            self::AFFILIATION_CHANGED    => 'Affiliation Changed',
            self::SUPERSEDED             => 'Superseded',
            self::CESSATION_OF_OPERATION => 'Cessation of Operation',
            self::CERTIFICATE_HOLD       => 'Certificate Hold',
            self::PRIVILEGE_WITHDRAWN    => 'Privilege Withdrawn',
            self::AA_COMPROMISE          => 'AA Compromise',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::UNSPECIFIED            => 'No specific reason provided',
            self::KEY_COMPROMISE         => 'The private key was compromised',
            self::CA_COMPROMISE          => 'The CA certificate was compromised',
            self::AFFILIATION_CHANGED    => 'The subject affiliation has changed',
            self::SUPERSEDED             => 'The certificate has been superseded',
            self::CESSATION_OF_OPERATION => 'The CA or subject has ceased operation',
            self::CERTIFICATE_HOLD       => 'The certificate is temporarily on hold',
            self::PRIVILEGE_WITHDRAWN    => 'Privileges have been withdrawn',
            self::AA_COMPROMISE          => 'Attribute Authority was compromised',
        };
    }

    /**
     * RFC 5280 numeric code.
     */
    public function rfcCode(): int
    {
        return match ($this) {
            self::UNSPECIFIED            => 0,
            self::KEY_COMPROMISE         => 1,
            self::CA_COMPROMISE          => 2,
            self::AFFILIATION_CHANGED    => 3,
            self::SUPERSEDED             => 4,
            self::CESSATION_OF_OPERATION => 5,
            self::CERTIFICATE_HOLD       => 6,
            self::PRIVILEGE_WITHDRAWN    => 9,
            self::AA_COMPROMISE          => 10,
        };
    }

    public function isPermanent(): bool
    {
        return $this !== self::CERTIFICATE_HOLD;
    }
}
