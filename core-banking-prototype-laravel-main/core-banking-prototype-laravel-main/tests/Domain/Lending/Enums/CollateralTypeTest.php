<?php

declare(strict_types=1);

namespace Tests\Domain\Lending\Enums;

use App\Domain\Lending\Enums\CollateralType;
use Tests\UnitTestCase;

class CollateralTypeTest extends UnitTestCase
{
    // ===========================================
    // Enum Cases Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = CollateralType::cases();

        expect($cases)->toHaveCount(9);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_values(): void
    {
        expect(CollateralType::REAL_ESTATE->value)->toBe('real_estate');
        expect(CollateralType::VEHICLE->value)->toBe('vehicle');
        expect(CollateralType::SECURITIES->value)->toBe('securities');
        expect(CollateralType::CRYPTO->value)->toBe('crypto');
        expect(CollateralType::EQUIPMENT->value)->toBe('equipment');
        expect(CollateralType::INVENTORY->value)->toBe('inventory');
        expect(CollateralType::ACCOUNTS_RECEIVABLE->value)->toBe('accounts_receivable');
        expect(CollateralType::PERSONAL_GUARANTEE->value)->toBe('personal_guarantee');
        expect(CollateralType::OTHER->value)->toBe('other');
    }

    // ===========================================
    // label Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_labels(): void
    {
        expect(CollateralType::REAL_ESTATE->label())->toBe('Real Estate');
        expect(CollateralType::VEHICLE->label())->toBe('Vehicle');
        expect(CollateralType::SECURITIES->label())->toBe('Securities');
        expect(CollateralType::CRYPTO->label())->toBe('Cryptocurrency');
        expect(CollateralType::EQUIPMENT->label())->toBe('Equipment');
        expect(CollateralType::INVENTORY->label())->toBe('Inventory');
        expect(CollateralType::ACCOUNTS_RECEIVABLE->label())->toBe('Accounts Receivable');
        expect(CollateralType::PERSONAL_GUARANTEE->label())->toBe('Personal Guarantee');
        expect(CollateralType::OTHER->label())->toBe('Other');
    }

    // ===========================================
    // getRequiredLTV Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_ltv_ratios(): void
    {
        expect(CollateralType::REAL_ESTATE->getRequiredLTV())->toBe(0.80);
        expect(CollateralType::SECURITIES->getRequiredLTV())->toBe(0.70);
        expect(CollateralType::VEHICLE->getRequiredLTV())->toBe(0.60);
        expect(CollateralType::EQUIPMENT->getRequiredLTV())->toBe(0.50);
        expect(CollateralType::INVENTORY->getRequiredLTV())->toBe(0.40);
        expect(CollateralType::ACCOUNTS_RECEIVABLE->getRequiredLTV())->toBe(0.50);
        expect(CollateralType::CRYPTO->getRequiredLTV())->toBe(0.30);
        expect(CollateralType::PERSONAL_GUARANTEE->getRequiredLTV())->toBe(1.00);
        expect(CollateralType::OTHER->getRequiredLTV())->toBe(0.50);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_lowest_ltv_for_crypto(): void
    {
        // Crypto has highest volatility, so lowest LTV
        $ltvs = array_map(
            fn (CollateralType $type) => $type->getRequiredLTV(),
            array_filter(
                CollateralType::cases(),
                fn (CollateralType $type) => $type !== CollateralType::PERSONAL_GUARANTEE
            )
        );

        expect(min($ltvs))->toBe(CollateralType::CRYPTO->getRequiredLTV());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_highest_ltv_for_real_estate(): void
    {
        // Real estate is most stable, so highest LTV (excluding personal guarantee)
        $assetBackedTypes = array_filter(
            CollateralType::cases(),
            fn (CollateralType $type) => $type !== CollateralType::PERSONAL_GUARANTEE
        );

        $ltvs = array_map(
            fn (CollateralType $type) => $type->getRequiredLTV(),
            $assetBackedTypes
        );

        expect(max($ltvs))->toBe(CollateralType::REAL_ESTATE->getRequiredLTV());
    }

    // ===========================================
    // getValuationFrequency Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_valuation_frequencies(): void
    {
        expect(CollateralType::CRYPTO->getValuationFrequency())->toBe(1);
        expect(CollateralType::SECURITIES->getValuationFrequency())->toBe(7);
        expect(CollateralType::INVENTORY->getValuationFrequency())->toBe(30);
        expect(CollateralType::ACCOUNTS_RECEIVABLE->getValuationFrequency())->toBe(30);
        expect(CollateralType::VEHICLE->getValuationFrequency())->toBe(90);
        expect(CollateralType::EQUIPMENT->getValuationFrequency())->toBe(180);
        expect(CollateralType::REAL_ESTATE->getValuationFrequency())->toBe(365);
        expect(CollateralType::PERSONAL_GUARANTEE->getValuationFrequency())->toBe(365);
        expect(CollateralType::OTHER->getValuationFrequency())->toBe(90);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_daily_valuation_for_crypto(): void
    {
        // Crypto needs most frequent valuation
        $frequencies = array_map(
            fn (CollateralType $type) => $type->getValuationFrequency(),
            CollateralType::cases()
        );

        expect(min($frequencies))->toBe(CollateralType::CRYPTO->getValuationFrequency());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_annual_valuation_for_real_estate(): void
    {
        // Real estate is most stable
        $frequencies = array_map(
            fn (CollateralType $type) => $type->getValuationFrequency(),
            CollateralType::cases()
        );

        expect(max($frequencies))->toBe(CollateralType::REAL_ESTATE->getValuationFrequency());
    }

    // ===========================================
    // From Value Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_valid_value(): void
    {
        expect(CollateralType::from('real_estate'))->toBe(CollateralType::REAL_ESTATE);
        expect(CollateralType::from('crypto'))->toBe(CollateralType::CRYPTO);
        expect(CollateralType::from('vehicle'))->toBe(CollateralType::VEHICLE);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_null_for_invalid_value_with_try_from(): void
    {
        expect(CollateralType::tryFrom('invalid'))->toBeNull();
        expect(CollateralType::tryFrom('REAL_ESTATE'))->toBeNull();
    }
}
