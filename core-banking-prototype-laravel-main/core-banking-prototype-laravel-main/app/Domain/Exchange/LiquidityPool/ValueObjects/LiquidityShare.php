<?php

declare(strict_types=1);

namespace App\Domain\Exchange\LiquidityPool\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final readonly class LiquidityShare
{
    private BigDecimal $amount;

    private BigDecimal $percentage;

    public function __construct(
        string|BigDecimal $amount,
        string|BigDecimal $totalShares
    ) {
        $this->amount = BigDecimal::of($amount);

        if ($this->amount->isNegative()) {
            throw new InvalidArgumentException('Liquidity share amount cannot be negative');
        }

        $total = BigDecimal::of($totalShares);

        if ($total->isZero()) {
            $this->percentage = BigDecimal::zero();
        } else {
            $this->percentage = $this->amount
                ->dividedBy($total, 18, RoundingMode::DOWN)
                ->multipliedBy(100);
        }
    }

    public static function zero(): self
    {
        return new self('0', '1');
    }

    public function getAmount(): BigDecimal
    {
        return $this->amount;
    }

    public function getPercentage(): BigDecimal
    {
        return $this->percentage;
    }

    public function add(self $other): self
    {
        return new self(
            $this->amount->plus($other->amount),
            '1' // Total shares need to be recalculated externally
        );
    }

    public function subtract(self $other): self
    {
        $newAmount = $this->amount->minus($other->amount);

        if ($newAmount->isNegative()) {
            throw new InvalidArgumentException('Cannot subtract more shares than available');
        }

        return new self($newAmount, '1');
    }

    public function calculateProportionalAmount(BigDecimal $totalAmount): BigDecimal
    {
        if ($this->percentage->isZero()) {
            return BigDecimal::zero();
        }

        return $totalAmount
            ->multipliedBy($this->percentage)
            ->dividedBy(100, 18, RoundingMode::DOWN);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->amount->isGreaterThan($other->amount);
    }

    public function isLessThan(self $other): bool
    {
        return $this->amount->isLessThan($other->amount);
    }

    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    public function __toString(): string
    {
        return $this->amount->__toString();
    }

    public function toArray(): array
    {
        return [
            'amount'     => $this->amount->__toString(),
            'percentage' => $this->percentage->toScale(2, RoundingMode::DOWN)->__toString() . '%',
        ];
    }
}
