<?php

declare(strict_types=1);

namespace App\Domain\Account\DataObjects;

use InvalidArgumentException;
use JustSteveKing\DataObjects\Contracts\DataObjectContract;

final readonly class Hash extends DataObject implements DataObjectContract
{
    public function __construct(
        private string $hash
    ) {
        if (! $this->isValidHash($hash)) {
            throw new InvalidArgumentException(
                message: 'Invalid hash hash provided.'
            );
        }
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Create hash from data using SHA3-512.
     */
    public static function fromData(string $data): self
    {
        return new self(hash('sha3-512', $data));
    }

    /**
     * Get string representation of the hash.
     */
    public function toString(): string
    {
        return $this->hash;
    }

    /**
     * Validate the hash format.
     */
    private function isValidHash(string $hash): bool
    {
        // SHA3-512 produces a 128-character hexadecimal string
        return ctype_xdigit($hash) && strlen($hash) === 128; // SHA3-512 length
    }

    public function equals(self $hash): bool
    {
        return hash_equals($this->hash, $hash->getHash());
    }

    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
        ];
    }
}
