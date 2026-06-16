<?php

declare(strict_types=1);

namespace App\Domain\Exchange\LiquidityPool\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final readonly class PoolRatio
{
    private BigDecimal $baseReserve;

    private BigDecimal $quoteReserve;

    private BigDecimal $ratio;

    private BigDecimal $price;

    public function __construct(
        string|BigDecimal $baseReserve,
        string|BigDecimal $quoteReserve
    ) {
        $this->baseReserve = BigDecimal::of($baseReserve);
        $this->quoteReserve = BigDecimal::of($quoteReserve);

        if ($this->baseReserve->isNegative() || $this->quoteReserve->isNegative()) {
            throw new InvalidArgumentException('Reserves cannot be negative');
        }

        if ($this->baseReserve->isZero() || $this->quoteReserve->isZero()) {
            throw new InvalidArgumentException('Reserves cannot be zero');
        }

        $this->ratio = $this->baseReserve->dividedBy($this->quoteReserve, 18, RoundingMode::DOWN);
        $this->price = $this->quoteReserve->dividedBy($this->baseReserve, 18, RoundingMode::DOWN);
    }

    public function getBaseReserve(): BigDecimal
    {
        return $this->baseReserve;
    }

    public function getQuoteReserve(): BigDecimal
    {
        return $this->quoteReserve;
    }

    public function getRatio(): BigDecimal
    {
        return $this->ratio;
    }

    public function getPrice(): BigDecimal
    {
        return $this->price;
    }

    public function getK(): BigDecimal
    {
        return $this->baseReserve->multipliedBy($this->quoteReserve);
    }

    public function calculateDeviation(self $targetRatio): BigDecimal
    {
        return $this->ratio
            ->minus($targetRatio->ratio)
            ->abs()
            ->dividedBy($targetRatio->ratio, 18, RoundingMode::UP);
    }

    public function isDeviationWithinTolerance(self $targetRatio, string $tolerance): bool
    {
        $toleranceDecimal = BigDecimal::of($tolerance);
        $deviation = $this->calculateDeviation($targetRatio);

        return $deviation->isLessThanOrEqualTo($toleranceDecimal);
    }

    public function calculatePriceImpact(BigDecimal $inputAmount, bool $isBaseInput): BigDecimal
    {
        if ($isBaseInput) {
            $newBaseReserve = $this->baseReserve->plus($inputAmount);
            $newQuoteReserve = $this->getK()->dividedBy($newBaseReserve, 18, RoundingMode::DOWN);
            $outputAmount = $this->quoteReserve->minus($newQuoteReserve);
            $executionPrice = $outputAmount->dividedBy($inputAmount, 18, RoundingMode::DOWN);
        } else {
            $newQuoteReserve = $this->quoteReserve->plus($inputAmount);
            $newBaseReserve = $this->getK()->dividedBy($newQuoteReserve, 18, RoundingMode::DOWN);
            $outputAmount = $this->baseReserve->minus($newBaseReserve);
            $executionPrice = $inputAmount->dividedBy($outputAmount, 18, RoundingMode::DOWN);
        }

        return $this->price
            ->minus($executionPrice)
            ->abs()
            ->dividedBy($this->price, 18, RoundingMode::UP)
            ->multipliedBy(100);
    }

    public function toArray(): array
    {
        return [
            'base_reserve'  => $this->baseReserve->__toString(),
            'quote_reserve' => $this->quoteReserve->__toString(),
            'ratio'         => $this->ratio->toScale(6, RoundingMode::DOWN)->__toString(),
            'price'         => $this->price->toScale(2, RoundingMode::DOWN)->__toString(),
            'k'             => $this->getK()->__toString(),
        ];
    }
}
