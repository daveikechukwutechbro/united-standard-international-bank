<?php

declare(strict_types=1);

namespace Tests\Domain\Lending\ValueObjects;

use App\Domain\Lending\ValueObjects\RiskRating;
use InvalidArgumentException;
use Tests\UnitTestCase;

class RiskRatingTest extends UnitTestCase
{
    // ===========================================
    // Construction Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_risk_rating_with_valid_data(): void
    {
        $riskRating = new RiskRating(
            rating: 'B',
            defaultProbability: 0.05,
            riskFactors: ['debt_ratio' => 0.3, 'employment_years' => 5]
        );

        expect($riskRating->rating)->toBe('B');
        expect($riskRating->defaultProbability)->toBe(0.05);
        expect($riskRating->riskFactors)->toBe(['debt_ratio' => 0.3, 'employment_years' => 5]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_risk_rating_with_all_valid_ratings(): void
    {
        $validRatings = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($validRatings as $rating) {
            $riskRating = new RiskRating($rating, 0.1, []);
            expect($riskRating->rating)->toBe($rating);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_risk_rating_with_zero_probability(): void
    {
        $riskRating = new RiskRating('A', 0.0, []);
        expect($riskRating->defaultProbability)->toBe(0.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_risk_rating_with_one_probability(): void
    {
        $riskRating = new RiskRating('F', 1.0, []);
        expect($riskRating->defaultProbability)->toBe(1.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_invalid_rating(): void
    {
        expect(fn () => new RiskRating(
            rating: 'G',
            defaultProbability: 0.1,
            riskFactors: []
        ))->toThrow(InvalidArgumentException::class, 'Invalid risk rating. Must be A-F');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_lowercase_rating(): void
    {
        expect(fn () => new RiskRating(
            rating: 'a',
            defaultProbability: 0.1,
            riskFactors: []
        ))->toThrow(InvalidArgumentException::class, 'Invalid risk rating. Must be A-F');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_negative_probability(): void
    {
        expect(fn () => new RiskRating(
            rating: 'A',
            defaultProbability: -0.1,
            riskFactors: []
        ))->toThrow(InvalidArgumentException::class, 'Default probability must be between 0 and 1');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_probability_above_one(): void
    {
        expect(fn () => new RiskRating(
            rating: 'A',
            defaultProbability: 1.1,
            riskFactors: []
        ))->toThrow(InvalidArgumentException::class, 'Default probability must be between 0 and 1');
    }

    // ===========================================
    // isLowRisk Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_low_risk_ratings(): void
    {
        $ratingA = new RiskRating('A', 0.01, []);
        $ratingB = new RiskRating('B', 0.03, []);

        expect($ratingA->isLowRisk())->toBeTrue();
        expect($ratingB->isLowRisk())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_correctly_rejects_non_low_risk_ratings(): void
    {
        $nonLowRisk = ['C', 'D', 'E', 'F'];

        foreach ($nonLowRisk as $rating) {
            $riskRating = new RiskRating($rating, 0.1, []);
            expect($riskRating->isLowRisk())->toBeFalse();
        }
    }

    // ===========================================
    // isMediumRisk Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_medium_risk_ratings(): void
    {
        $ratingC = new RiskRating('C', 0.10, []);
        $ratingD = new RiskRating('D', 0.15, []);

        expect($ratingC->isMediumRisk())->toBeTrue();
        expect($ratingD->isMediumRisk())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_correctly_rejects_non_medium_risk_ratings(): void
    {
        $nonMediumRisk = ['A', 'B', 'E', 'F'];

        foreach ($nonMediumRisk as $rating) {
            $riskRating = new RiskRating($rating, 0.1, []);
            expect($riskRating->isMediumRisk())->toBeFalse();
        }
    }

    // ===========================================
    // isHighRisk Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_high_risk_ratings(): void
    {
        $ratingE = new RiskRating('E', 0.25, []);
        $ratingF = new RiskRating('F', 0.40, []);

        expect($ratingE->isHighRisk())->toBeTrue();
        expect($ratingF->isHighRisk())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_correctly_rejects_non_high_risk_ratings(): void
    {
        $nonHighRisk = ['A', 'B', 'C', 'D'];

        foreach ($nonHighRisk as $rating) {
            $riskRating = new RiskRating($rating, 0.1, []);
            expect($riskRating->isHighRisk())->toBeFalse();
        }
    }

    // ===========================================
    // Risk Category Mutually Exclusive Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_ensures_only_one_risk_category_is_true(): void
    {
        $allRatings = ['A', 'B', 'C', 'D', 'E', 'F'];

        foreach ($allRatings as $rating) {
            $riskRating = new RiskRating($rating, 0.1, []);
            $categories = [
                $riskRating->isLowRisk(),
                $riskRating->isMediumRisk(),
                $riskRating->isHighRisk(),
            ];

            expect(array_sum(array_map('intval', $categories)))->toBe(1);
        }
    }

    // ===========================================
    // getInterestRateMultiplier Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_interest_rate_multipliers(): void
    {
        expect((new RiskRating('A', 0.01, []))->getInterestRateMultiplier())->toBe(1.0);
        expect((new RiskRating('B', 0.03, []))->getInterestRateMultiplier())->toBe(1.2);
        expect((new RiskRating('C', 0.10, []))->getInterestRateMultiplier())->toBe(1.5);
        expect((new RiskRating('D', 0.15, []))->getInterestRateMultiplier())->toBe(2.0);
        expect((new RiskRating('E', 0.25, []))->getInterestRateMultiplier())->toBe(2.5);
        expect((new RiskRating('F', 0.40, []))->getInterestRateMultiplier())->toBe(3.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_increasing_multipliers_with_worse_ratings(): void
    {
        $ratings = ['A', 'B', 'C', 'D', 'E', 'F'];
        $previousMultiplier = 0.0;

        foreach ($ratings as $rating) {
            $riskRating = new RiskRating($rating, 0.1, []);
            $multiplier = $riskRating->getInterestRateMultiplier();

            expect($multiplier)->toBeGreaterThan($previousMultiplier);
            $previousMultiplier = $multiplier;
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_lowest_multiplier_for_a_rating(): void
    {
        $allRatings = ['A', 'B', 'C', 'D', 'E', 'F'];
        $multipliers = array_map(
            fn (string $rating) => (new RiskRating($rating, 0.1, []))->getInterestRateMultiplier(),
            $allRatings
        );

        expect(min($multipliers))->toBe((new RiskRating('A', 0.1, []))->getInterestRateMultiplier());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_highest_multiplier_for_f_rating(): void
    {
        $allRatings = ['A', 'B', 'C', 'D', 'E', 'F'];
        $multipliers = array_map(
            fn (string $rating) => (new RiskRating($rating, 0.1, []))->getInterestRateMultiplier(),
            $allRatings
        );

        expect(max($multipliers))->toBe((new RiskRating('F', 0.1, []))->getInterestRateMultiplier());
    }

    // ===========================================
    // toArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_array(): void
    {
        $riskFactors = ['debt_ratio' => 0.4, 'payment_history' => 'good', 'years_employed' => 3];
        $riskRating = new RiskRating(
            rating: 'C',
            defaultProbability: 0.12,
            riskFactors: $riskFactors
        );

        $array = $riskRating->toArray();

        expect($array)->toBe([
            'rating'              => 'C',
            'default_probability' => 0.12,
            'risk_factors'        => $riskFactors,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_empty_risk_factors_to_array(): void
    {
        $riskRating = new RiskRating(
            rating: 'A',
            defaultProbability: 0.01,
            riskFactors: []
        );

        $array = $riskRating->toArray();

        expect($array['risk_factors'])->toBe([]);
    }
}
