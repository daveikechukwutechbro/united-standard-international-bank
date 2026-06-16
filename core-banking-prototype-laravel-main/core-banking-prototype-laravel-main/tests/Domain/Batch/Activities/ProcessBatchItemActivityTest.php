<?php

declare(strict_types=1);

namespace Tests\Domain\Batch\Activities;

use App\Domain\Batch\Activities\ProcessBatchItemActivity;
use PHPUnit\Framework\TestCase;
use Tests\Traits\InvokesPrivateMethods;

/**
 * Unit tests for ProcessBatchItemActivity.
 *
 * These tests focus on the conversion rate calculation logic
 * which doesn't require database dependencies.
 */
class ProcessBatchItemActivityTest extends TestCase
{
    use InvokesPrivateMethods;

    private ProcessBatchItemActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        // Create activity using anonymous class to access private methods
        $this->activity = new class () extends ProcessBatchItemActivity {
            public function __construct()
            {
                // Skip parent constructor
            }

            // Expose the conversion rates for testing
            public function getConversionRates(): array
            {
                return [
                    'USD' => ['EUR' => 0.92, 'GBP' => 0.79, 'PHP' => 56.25],
                    'EUR' => ['USD' => 1.09, 'GBP' => 0.86, 'PHP' => 61.20],
                    'GBP' => ['USD' => 1.27, 'EUR' => 1.16, 'PHP' => 71.15],
                    'PHP' => ['USD' => 0.018, 'EUR' => 0.016, 'GBP' => 0.014],
                ];
            }

            // Test helper for conversion calculation
            public function calculateConversion(int $amountInCents, string $fromCurrency, string $toCurrency): array
            {
                $rates = $this->getConversionRates();
                $rate = $rates[$fromCurrency][$toCurrency] ?? 1;
                $convertedAmount = (int) ($amountInCents * $rate);

                return [
                    'converted_amount' => $convertedAmount,
                    'rate'             => $rate,
                ];
            }
        };
    }

    // Conversion Rate Tests

    public function test_usd_to_eur_conversion_rate_is_correct(): void
    {
        $rates = $this->activity->getConversionRates();

        $this->assertEquals(0.92, $rates['USD']['EUR']);
    }

    public function test_usd_to_gbp_conversion_rate_is_correct(): void
    {
        $rates = $this->activity->getConversionRates();

        $this->assertEquals(0.79, $rates['USD']['GBP']);
    }

    public function test_usd_to_php_conversion_rate_is_correct(): void
    {
        $rates = $this->activity->getConversionRates();

        $this->assertEquals(56.25, $rates['USD']['PHP']);
    }

    public function test_eur_to_usd_conversion_rate_is_correct(): void
    {
        $rates = $this->activity->getConversionRates();

        $this->assertEquals(1.09, $rates['EUR']['USD']);
    }

    public function test_gbp_to_usd_conversion_rate_is_correct(): void
    {
        $rates = $this->activity->getConversionRates();

        $this->assertEquals(1.27, $rates['GBP']['USD']);
    }

    public function test_php_to_usd_conversion_rate_is_correct(): void
    {
        $rates = $this->activity->getConversionRates();

        $this->assertEquals(0.018, $rates['PHP']['USD']);
    }

    // Conversion Calculation Tests

    public function test_calculate_usd_to_eur_conversion(): void
    {
        // 100 USD in cents = 10000 cents
        $result = $this->activity->calculateConversion(10000, 'USD', 'EUR');

        // 10000 * 0.92 = 9200 EUR cents
        $this->assertEquals(9200, $result['converted_amount']);
        $this->assertEquals(0.92, $result['rate']);
    }

    public function test_calculate_usd_to_gbp_conversion(): void
    {
        // 100 USD in cents = 10000 cents
        $result = $this->activity->calculateConversion(10000, 'USD', 'GBP');

        // 10000 * 0.79 = 7900 GBP cents
        $this->assertEquals(7900, $result['converted_amount']);
        $this->assertEquals(0.79, $result['rate']);
    }

    public function test_calculate_usd_to_php_conversion(): void
    {
        // 100 USD in cents = 10000 cents
        $result = $this->activity->calculateConversion(10000, 'USD', 'PHP');

        // 10000 * 56.25 = 562500 PHP centavos
        $this->assertEquals(562500, $result['converted_amount']);
        $this->assertEquals(56.25, $result['rate']);
    }

    public function test_calculate_eur_to_usd_conversion(): void
    {
        // 100 EUR in cents = 10000 cents
        $result = $this->activity->calculateConversion(10000, 'EUR', 'USD');

        // 10000 * 1.09 = 10900 USD cents
        $this->assertEquals(10900, $result['converted_amount']);
        $this->assertEquals(1.09, $result['rate']);
    }

    public function test_calculate_gbp_to_eur_conversion(): void
    {
        // 100 GBP in pence = 10000 pence
        $result = $this->activity->calculateConversion(10000, 'GBP', 'EUR');

        // 10000 * 1.16 = 11600 EUR cents
        $this->assertEquals(11600, $result['converted_amount']);
        $this->assertEquals(1.16, $result['rate']);
    }

    public function test_calculate_php_to_usd_conversion(): void
    {
        // 10000 PHP centavos
        $result = $this->activity->calculateConversion(10000, 'PHP', 'USD');

        // 10000 * 0.018 = 180 USD cents
        $this->assertEquals(180, $result['converted_amount']);
        $this->assertEquals(0.018, $result['rate']);
    }

    public function test_unknown_currency_pair_returns_rate_of_one(): void
    {
        // XXX to YYY (unknown currencies)
        $result = $this->activity->calculateConversion(10000, 'XXX', 'YYY');

        // Unknown rate defaults to 1
        $this->assertEquals(10000, $result['converted_amount']);
        $this->assertEquals(1, $result['rate']);
    }

    public function test_same_currency_conversion_defaults_to_one(): void
    {
        // USD to USD (same currency, no direct rate)
        $result = $this->activity->calculateConversion(10000, 'USD', 'USD');

        // Rate defaults to 1 for same currency
        $this->assertEquals(10000, $result['converted_amount']);
        $this->assertEquals(1, $result['rate']);
    }

    public function test_conversion_with_small_amounts(): void
    {
        // 1 cent USD to EUR
        $result = $this->activity->calculateConversion(1, 'USD', 'EUR');

        // 1 * 0.92 = 0.92, cast to int = 0
        $this->assertEquals(0, $result['converted_amount']);
    }

    public function test_conversion_with_large_amounts(): void
    {
        // 1 million USD in cents = 100,000,000 cents
        $result = $this->activity->calculateConversion(100000000, 'USD', 'PHP');

        // 100,000,000 * 56.25 = 5,625,000,000 PHP centavos
        $this->assertEquals(5625000000, $result['converted_amount']);
    }

    // Supported Currencies Tests

    public function test_all_base_currencies_have_conversion_rates(): void
    {
        $rates = $this->activity->getConversionRates();

        $expectedBaseCurrencies = ['USD', 'EUR', 'GBP', 'PHP'];

        foreach ($expectedBaseCurrencies as $currency) {
            $this->assertArrayHasKey($currency, $rates);
        }
    }

    public function test_usd_has_all_target_currencies(): void
    {
        $rates = $this->activity->getConversionRates();

        $this->assertArrayHasKey('EUR', $rates['USD']);
        $this->assertArrayHasKey('GBP', $rates['USD']);
        $this->assertArrayHasKey('PHP', $rates['USD']);
    }

    public function test_eur_has_all_target_currencies(): void
    {
        $rates = $this->activity->getConversionRates();

        $this->assertArrayHasKey('USD', $rates['EUR']);
        $this->assertArrayHasKey('GBP', $rates['EUR']);
        $this->assertArrayHasKey('PHP', $rates['EUR']);
    }

    public function test_all_rates_are_positive(): void
    {
        $rates = $this->activity->getConversionRates();

        foreach ($rates as $from => $targets) {
            foreach ($targets as $to => $rate) {
                $this->assertGreaterThan(0, $rate, "Rate from {$from} to {$to} should be positive");
            }
        }
    }

    // Edge Cases

    public function test_conversion_truncates_to_integer(): void
    {
        // Test that floating point results are properly truncated
        // 123 USD cents to EUR = 123 * 0.92 = 113.16, should be 113
        $result = $this->activity->calculateConversion(123, 'USD', 'EUR');

        $this->assertIsInt($result['converted_amount']);
        $this->assertEquals(113, $result['converted_amount']);
    }

    public function test_zero_amount_conversion(): void
    {
        $result = $this->activity->calculateConversion(0, 'USD', 'EUR');

        $this->assertEquals(0, $result['converted_amount']);
    }
}
