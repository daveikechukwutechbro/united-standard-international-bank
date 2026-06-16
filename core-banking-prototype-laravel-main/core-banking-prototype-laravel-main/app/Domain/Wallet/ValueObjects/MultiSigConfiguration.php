<?php

declare(strict_types=1);

namespace App\Domain\Wallet\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a multi-signature wallet configuration.
 */
final readonly class MultiSigConfiguration
{
    public const SCHEME_2_OF_3 = '2-of-3';

    public const SCHEME_3_OF_5 = '3-of-5';

    public const SCHEME_2_OF_2 = '2-of-2';

    public const SCHEME_3_OF_4 = '3-of-4';

    public const SUPPORTED_SCHEMES = [
        self::SCHEME_2_OF_3,
        self::SCHEME_3_OF_5,
        self::SCHEME_2_OF_2,
        self::SCHEME_3_OF_4,
    ];

    private function __construct(
        public int $requiredSignatures,
        public int $totalSigners,
        public string $chain,
        public string $name,
    ) {
        $this->validate();
    }

    /**
     * Create a configuration from a scheme string (e.g., "2-of-3").
     */
    public static function fromScheme(string $scheme, string $chain, string $name): self
    {
        if (! preg_match('/^(\d+)-of-(\d+)$/', $scheme, $matches)) {
            throw new InvalidArgumentException("Invalid scheme format: {$scheme}. Expected format: 'M-of-N'");
        }

        return new self(
            requiredSignatures: (int) $matches[1],
            totalSigners: (int) $matches[2],
            chain: $chain,
            name: $name,
        );
    }

    /**
     * Create a configuration from explicit values.
     */
    public static function create(int $requiredSignatures, int $totalSigners, string $chain, string $name): self
    {
        return new self(
            requiredSignatures: $requiredSignatures,
            totalSigners: $totalSigners,
            chain: $chain,
            name: $name,
        );
    }

    /**
     * Create from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            requiredSignatures: (int) ($data['required_signatures'] ?? $data['requiredSignatures'] ?? 0),
            totalSigners: (int) ($data['total_signers'] ?? $data['totalSigners'] ?? 0),
            chain: (string) ($data['chain'] ?? ''),
            name: (string) ($data['name'] ?? ''),
        );
    }

    /**
     * Validate the configuration.
     */
    private function validate(): void
    {
        $maxSigners = config('blockchain.multi_sig.max_signers', 10);
        $minSigners = config('blockchain.multi_sig.min_signers', 2);

        if ($this->totalSigners < $minSigners) {
            throw new InvalidArgumentException("Total signers must be at least {$minSigners}");
        }

        if ($this->totalSigners > $maxSigners) {
            throw new InvalidArgumentException("Total signers cannot exceed {$maxSigners}");
        }

        if ($this->requiredSignatures < 1) {
            throw new InvalidArgumentException('Required signatures must be at least 1');
        }

        if ($this->requiredSignatures > $this->totalSigners) {
            throw new InvalidArgumentException('Required signatures cannot exceed total signers');
        }

        if (empty($this->chain)) {
            throw new InvalidArgumentException('Chain must be specified');
        }

        if (empty($this->name)) {
            throw new InvalidArgumentException('Name must be specified');
        }

        if (strlen($this->name) > 100) {
            throw new InvalidArgumentException('Name cannot exceed 100 characters');
        }
    }

    /**
     * Get the scheme description.
     */
    public function getScheme(): string
    {
        return "{$this->requiredSignatures}-of-{$this->totalSigners}";
    }

    /**
     * Check if the scheme is a standard supported scheme.
     */
    public function isStandardScheme(): bool
    {
        return in_array($this->getScheme(), self::SUPPORTED_SCHEMES, true);
    }

    /**
     * Calculate the quorum percentage.
     */
    public function getQuorumPercentage(): float
    {
        return round(($this->requiredSignatures / $this->totalSigners) * 100, 2);
    }

    /**
     * Check if this is a majority scheme (more than 50%).
     */
    public function isMajorityRequired(): bool
    {
        return $this->requiredSignatures > ($this->totalSigners / 2);
    }

    /**
     * Check if this is a unanimous scheme (all signers required).
     */
    public function isUnanimousRequired(): bool
    {
        return $this->requiredSignatures === $this->totalSigners;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'required_signatures' => $this->requiredSignatures,
            'total_signers'       => $this->totalSigners,
            'chain'               => $this->chain,
            'name'                => $this->name,
            'scheme'              => $this->getScheme(),
            'quorum_percentage'   => $this->getQuorumPercentage(),
            'is_standard_scheme'  => $this->isStandardScheme(),
        ];
    }
}
