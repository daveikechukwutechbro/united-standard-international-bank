<?php

declare(strict_types=1);

namespace App\Domain\Exchange\LiquidityPool\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final readonly class PoolFee
{
    private BigDecimal $rate;

    private BigDecimal $basisPoints;

    public function __construct(string|BigDecimal $rate)
    {
        $this->rate = BigDecimal::of($rate);

        if ($this->rate->isNegative()) {
            throw new InvalidArgumentException('Fee rate cannot be negative');
        }

        if ($this->rate->isGreaterThanOrEqualTo('0.1')) { // 10% max
            throw new InvalidArgumentException('Fee rate cannot exceed 10%');
        }

        $this->basisPoints = $this->rate->multipliedBy(10000);
    }

    public static function default(): self
    {
        return new self('0.003'); // 0.3% default
    }

    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self(BigDecimal::of($basisPoints)->dividedBy(10000, 6, RoundingMode::DOWN));
    }

    public function getRate(): BigDecimal
    {
        return $this->rate;
    }

    public function getBasisPoints(): int
    {
        return $this->basisPoints->toInt();
    }

    public function calculateFee(BigDecimal $amount): BigDecimal
    {
        return $amount->multipliedBy($this->rate);
    }

    public function applyFeeDeduction(BigDecimal $amount): BigDecimal
    {
        return $amount->multipliedBy(BigDecimal::one()->minus($this->rate));
    }

    public function isHigherThan(self $other): bool
    {
        return $this->rate->isGreaterThan($other->rate);
    }

    public function isLowerThan(self $other): bool
    {
        return $this->rate->isLessThan($other->rate);
    }

    public function equals(self $other): bool
    {
        return $this->rate->isEqualTo($other->rate);
    }

    public function __toString(): string
    {
        return $this->rate->toScale(4, RoundingMode::DOWN)->__toString();
    }

    public function toArray(): array
    {
        return [
            'rate'         => $this->rate->toScale(4, RoundingMode::DOWN)->__toString(),
            'basis_points' => $this->getBasisPoints(),
            'percentage'   => $this->rate->multipliedBy(100)->toScale(2, RoundingMode::DOWN)->__toString() . '%',
        ];
    }
}
