<?php

declare(strict_types=1);

namespace Tests\Domain\Lending\Enums;

use App\Domain\Lending\Enums\LoanPurpose;
use Tests\UnitTestCase;

class LoanPurposeTest extends UnitTestCase
{
    // ===========================================
    // Enum Cases Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = LoanPurpose::cases();

        expect($cases)->toHaveCount(8);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_correct_values(): void
    {
        expect(LoanPurpose::PERSONAL->value)->toBe('personal');
        expect(LoanPurpose::BUSINESS->value)->toBe('business');
        expect(LoanPurpose::HOME_IMPROVEMENT->value)->toBe('home_improvement');
        expect(LoanPurpose::DEBT_CONSOLIDATION->value)->toBe('debt_consolidation');
        expect(LoanPurpose::EDUCATION->value)->toBe('education');
        expect(LoanPurpose::MEDICAL->value)->toBe('medical');
        expect(LoanPurpose::VEHICLE->value)->toBe('vehicle');
        expect(LoanPurpose::OTHER->value)->toBe('other');
    }

    // ===========================================
    // label Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_labels(): void
    {
        expect(LoanPurpose::PERSONAL->label())->toBe('Personal Use');
        expect(LoanPurpose::BUSINESS->label())->toBe('Business Investment');
        expect(LoanPurpose::HOME_IMPROVEMENT->label())->toBe('Home Improvement');
        expect(LoanPurpose::DEBT_CONSOLIDATION->label())->toBe('Debt Consolidation');
        expect(LoanPurpose::EDUCATION->label())->toBe('Education');
        expect(LoanPurpose::MEDICAL->label())->toBe('Medical Expenses');
        expect(LoanPurpose::VEHICLE->label())->toBe('Vehicle Purchase');
        expect(LoanPurpose::OTHER->label())->toBe('Other');
    }

    // ===========================================
    // getBaseInterestRate Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_base_interest_rates(): void
    {
        expect(LoanPurpose::EDUCATION->getBaseInterestRate())->toBe(4.5);
        expect(LoanPurpose::MEDICAL->getBaseInterestRate())->toBe(5.0);
        expect(LoanPurpose::HOME_IMPROVEMENT->getBaseInterestRate())->toBe(6.0);
        expect(LoanPurpose::VEHICLE->getBaseInterestRate())->toBe(6.5);
        expect(LoanPurpose::BUSINESS->getBaseInterestRate())->toBe(7.0);
        expect(LoanPurpose::DEBT_CONSOLIDATION->getBaseInterestRate())->toBe(8.0);
        expect(LoanPurpose::PERSONAL->getBaseInterestRate())->toBe(9.0);
        expect(LoanPurpose::OTHER->getBaseInterestRate())->toBe(10.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_lowest_rate_for_education(): void
    {
        $rates = array_map(
            fn (LoanPurpose $purpose) => $purpose->getBaseInterestRate(),
            LoanPurpose::cases()
        );

        expect(min($rates))->toBe(LoanPurpose::EDUCATION->getBaseInterestRate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_highest_rate_for_other(): void
    {
        $rates = array_map(
            fn (LoanPurpose $purpose) => $purpose->getBaseInterestRate(),
            LoanPurpose::cases()
        );

        expect(max($rates))->toBe(LoanPurpose::OTHER->getBaseInterestRate());
    }

    // ===========================================
    // getMaxTermMonths Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_max_term_months(): void
    {
        expect(LoanPurpose::EDUCATION->getMaxTermMonths())->toBe(120);
        expect(LoanPurpose::HOME_IMPROVEMENT->getMaxTermMonths())->toBe(84);
        expect(LoanPurpose::BUSINESS->getMaxTermMonths())->toBe(60);
        expect(LoanPurpose::VEHICLE->getMaxTermMonths())->toBe(60);
        expect(LoanPurpose::DEBT_CONSOLIDATION->getMaxTermMonths())->toBe(48);
        expect(LoanPurpose::MEDICAL->getMaxTermMonths())->toBe(36);
        expect(LoanPurpose::PERSONAL->getMaxTermMonths())->toBe(36);
        expect(LoanPurpose::OTHER->getMaxTermMonths())->toBe(24);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_longest_term_for_education(): void
    {
        $terms = array_map(
            fn (LoanPurpose $purpose) => $purpose->getMaxTermMonths(),
            LoanPurpose::cases()
        );

        expect(max($terms))->toBe(LoanPurpose::EDUCATION->getMaxTermMonths());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_shortest_term_for_other(): void
    {
        $terms = array_map(
            fn (LoanPurpose $purpose) => $purpose->getMaxTermMonths(),
            LoanPurpose::cases()
        );

        expect(min($terms))->toBe(LoanPurpose::OTHER->getMaxTermMonths());
    }

    // ===========================================
    // From Value Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_valid_value(): void
    {
        expect(LoanPurpose::from('personal'))->toBe(LoanPurpose::PERSONAL);
        expect(LoanPurpose::from('business'))->toBe(LoanPurpose::BUSINESS);
        expect(LoanPurpose::from('education'))->toBe(LoanPurpose::EDUCATION);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_null_for_invalid_value_with_try_from(): void
    {
        expect(LoanPurpose::tryFrom('invalid'))->toBeNull();
        expect(LoanPurpose::tryFrom('PERSONAL'))->toBeNull();
    }
}
