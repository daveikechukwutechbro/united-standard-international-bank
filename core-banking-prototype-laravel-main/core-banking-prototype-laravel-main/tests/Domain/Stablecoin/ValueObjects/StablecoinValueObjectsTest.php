<?php

declare(strict_types=1);

namespace Tests\Domain\Stablecoin\ValueObjects;

use App\Domain\Stablecoin\ValueObjects\CollateralRatio;
use App\Domain\Stablecoin\ValueObjects\LiquidationThreshold;
use App\Domain\Stablecoin\ValueObjects\PriceData;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Stablecoin domain value objects.
 */
class StablecoinValueObjectsTest extends TestCase
{
    // LiquidationThreshold Tests

    public function test_liquidation_threshold_calculates_levels_correctly(): void
    {
        $threshold = new LiquidationThreshold(150); // 150% collateralization

        // Liquidation level is 150/100 = 1.5
        $this->assertEquals(1.5, $threshold->value());
        $this->assertEquals(150, $threshold->liquidationPercentage());

        // Margin call at 120% of liquidation = 1.8 (180%)
        $this->assertEquals(180, $threshold->marginCallPercentage());

        // Safe level at 150% of liquidation = 2.25 (225%)
        $this->assertEquals(225, $threshold->safePercentage());
    }

    public function test_liquidation_threshold_rejects_below_100_percent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Liquidation threshold must be at least 100%');

        new LiquidationThreshold(99);
    }

    public function test_liquidation_threshold_rejects_above_1000_percent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Liquidation threshold cannot exceed 1000%');

        new LiquidationThreshold(1001);
    }

    public function test_liquidation_threshold_is_ratio_safe(): void
    {
        $threshold = new LiquidationThreshold(150);

        // Safe level is 225% (2.25)
        $this->assertTrue($threshold->isRatioSafe(BigDecimal::of('2.5'))); // 250%
        $this->assertTrue($threshold->isRatioSafe(BigDecimal::of('2.25'))); // 225% (exactly safe)
        $this->assertFalse($threshold->isRatioSafe(BigDecimal::of('2.0'))); // 200%
    }

    public function test_liquidation_threshold_requires_margin_call(): void
    {
        $threshold = new LiquidationThreshold(150);

        // Margin call at 180% (1.8)
        $this->assertFalse($threshold->requiresMarginCall(BigDecimal::of('2.0'))); // 200%
        $this->assertTrue($threshold->requiresMarginCall(BigDecimal::of('1.7'))); // 170%
    }

    public function test_liquidation_threshold_requires_liquidation(): void
    {
        $threshold = new LiquidationThreshold(150);

        // Liquidation at 150% (1.5)
        $this->assertFalse($threshold->requiresLiquidation(BigDecimal::of('1.6'))); // 160%
        $this->assertTrue($threshold->requiresLiquidation(BigDecimal::of('1.4'))); // 140%
    }

    public function test_liquidation_threshold_to_string(): void
    {
        $threshold = new LiquidationThreshold(150);
        $string = $threshold->toString();

        $this->assertStringContainsString('Liquidation: 150.0%', $string);
        $this->assertStringContainsString('Margin Call: 180.0%', $string);
        $this->assertStringContainsString('Safe: 225.0%', $string);
    }

    // CollateralRatio Tests

    public function test_collateral_ratio_stores_value(): void
    {
        $ratio = new CollateralRatio(1.5);

        $this->assertTrue($ratio->value()->isEqualTo(BigDecimal::of('1.5')));
    }

    public function test_collateral_ratio_from_percentage(): void
    {
        $ratio = CollateralRatio::fromPercentage(150);

        $this->assertTrue($ratio->value()->isEqualTo(BigDecimal::of('1.5')));
    }

    public function test_collateral_ratio_to_percentage(): void
    {
        $ratio = new CollateralRatio(1.5);

        $this->assertEquals(150.0, $ratio->toPercentage());
    }

    public function test_collateral_ratio_rejects_negative_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Collateral ratio cannot be negative');

        new CollateralRatio(-0.5);
    }

    public function test_collateral_ratio_accepts_zero(): void
    {
        $ratio = new CollateralRatio(0);

        $this->assertTrue($ratio->value()->isEqualTo(BigDecimal::zero()));
    }

    public function test_collateral_ratio_is_healthy(): void
    {
        $threshold = new LiquidationThreshold(150); // Safe at 225%
        $healthyRatio = new CollateralRatio(2.5); // 250%
        $unhealthyRatio = new CollateralRatio(1.8); // 180%

        $this->assertTrue($healthyRatio->isHealthy($threshold));
        $this->assertFalse($unhealthyRatio->isHealthy($threshold));
    }

    public function test_collateral_ratio_requires_margin_call(): void
    {
        $threshold = new LiquidationThreshold(150); // Margin call at 180%
        $okRatio = new CollateralRatio(2.0); // 200%
        $marginCallRatio = new CollateralRatio(1.7); // 170%

        $this->assertFalse($okRatio->requiresMarginCall($threshold));
        $this->assertTrue($marginCallRatio->requiresMarginCall($threshold));
    }

    public function test_collateral_ratio_requires_liquidation(): void
    {
        $threshold = new LiquidationThreshold(150); // Liquidation at 150%
        $okRatio = new CollateralRatio(1.6); // 160%
        $liquidationRatio = new CollateralRatio(1.4); // 140%

        $this->assertFalse($okRatio->requiresLiquidation($threshold));
        $this->assertTrue($liquidationRatio->requiresLiquidation($threshold));
    }

    public function test_collateral_ratio_equals(): void
    {
        $ratio1 = new CollateralRatio(1.5);
        $ratio2 = new CollateralRatio(1.5);
        $ratio3 = new CollateralRatio(1.6);

        $this->assertTrue($ratio1->equals($ratio2));
        $this->assertFalse($ratio1->equals($ratio3));
    }

    public function test_collateral_ratio_to_string(): void
    {
        $ratio = new CollateralRatio(1.5678);

        $this->assertEquals('1.5678', $ratio->toString());
    }

    public function test_collateral_ratio_accepts_big_decimal(): void
    {
        $ratio = new CollateralRatio(BigDecimal::of('1.5'));

        $this->assertEquals(150.0, $ratio->toPercentage());
    }

    // PriceData Tests

    public function test_price_data_stores_properties(): void
    {
        $timestamp = Carbon::now();
        $priceData = new PriceData(
            base: 'ETH',
            quote: 'USD',
            price: '3500.50',
            source: 'chainlink',
            timestamp: $timestamp
        );

        $this->assertEquals('ETH', $priceData->base);
        $this->assertEquals('USD', $priceData->quote);
        $this->assertEquals('3500.50', $priceData->price);
        $this->assertEquals('chainlink', $priceData->source);
        $this->assertEquals($timestamp, $priceData->timestamp);
    }

    public function test_price_data_with_optional_fields(): void
    {
        $priceData = new PriceData(
            base: 'BTC',
            quote: 'USD',
            price: '65000.00',
            source: 'binance',
            timestamp: Carbon::now(),
            volume24h: '1234567890.50',
            changePercent24h: '2.5',
            metadata: ['market' => 'spot']
        );

        $this->assertEquals('1234567890.50', $priceData->volume24h);
        $this->assertEquals('2.5', $priceData->changePercent24h);
        $this->assertEquals(['market' => 'spot'], $priceData->metadata);
    }

    public function test_price_data_to_array(): void
    {
        $timestamp = Carbon::parse('2026-01-25 12:00:00');
        $priceData = new PriceData(
            base: 'ETH',
            quote: 'USD',
            price: '3500.50',
            source: 'chainlink',
            timestamp: $timestamp,
            volume24h: '100000.00',
            changePercent24h: '1.5',
            metadata: ['key' => 'value']
        );

        $array = $priceData->toArray();

        $this->assertEquals('ETH', $array['base']);
        $this->assertEquals('USD', $array['quote']);
        $this->assertEquals('3500.50', $array['price']);
        $this->assertEquals('chainlink', $array['source']);
        $this->assertStringContainsString('2026-01-25', $array['timestamp']);
        $this->assertEquals('100000.00', $array['volume_24h']);
        $this->assertEquals('1.5', $array['change_percent_24h']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
    }

    public function test_price_data_is_stale_returns_false_for_fresh_data(): void
    {
        $priceData = new PriceData(
            base: 'ETH',
            quote: 'USD',
            price: '3500.50',
            source: 'chainlink',
            timestamp: Carbon::now()
        );

        $this->assertFalse($priceData->isStale());
    }

    public function test_price_data_is_stale_returns_true_for_old_data(): void
    {
        $priceData = new PriceData(
            base: 'ETH',
            quote: 'USD',
            price: '3500.50',
            source: 'chainlink',
            timestamp: Carbon::now()->subMinutes(10)
        );

        // Default max age is 300 seconds (5 minutes)
        $this->assertTrue($priceData->isStale());
    }

    public function test_price_data_is_stale_with_custom_max_age(): void
    {
        $priceData = new PriceData(
            base: 'ETH',
            quote: 'USD',
            price: '3500.50',
            source: 'chainlink',
            timestamp: Carbon::now()->subMinutes(2)
        );

        // 2 minutes old, 60 seconds max age = stale
        $this->assertTrue($priceData->isStale(60));

        // 2 minutes old, 180 seconds max age = not stale
        $this->assertFalse($priceData->isStale(180));
    }
}
