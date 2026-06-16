<?php

declare(strict_types=1);

namespace App\Domain\Exchange\LiquidityPool\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class PoolId implements Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Pool ID cannot be empty');
        }

        if (! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $value)) {
            throw new InvalidArgumentException('Pool ID must be a valid UUID');
        }

        $this->value = strtolower($value);
    }

    public static function generate(): self
    {
        return new self((string) \Illuminate\Support\Str::uuid());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'pool_id' => $this->value,
        ];
    }
}
