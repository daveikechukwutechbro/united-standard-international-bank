<?php

declare(strict_types=1);

namespace Tests\Domain\Treasury\ValueObjects;

use App\Domain\Treasury\ValueObjects\RiskProfile;
use InvalidArgumentException;
use Tests\UnitTestCase;

class RiskProfileTest extends UnitTestCase
{
    // ===========================================
    // Constructor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_valid_values(): void
    {
        $profile = new RiskProfile('medium', 45.0, ['volatility' => 'moderate']);

        expect($profile->getLevel())->toBe('medium');
        expect($profile->getScore())->toBe(45.0);
        expect($profile->getFactors())->toBe(['volatility' => 'moderate']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_all_valid_levels(): void
    {
        $validLevels = ['low', 'medium', 'high', 'very_high'];

        foreach ($validLevels as $level) {
            $profile = new RiskProfile($level, 50.0);
            expect($profile->getLevel())->toBe($level);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_invalid_level(): void
    {
        expect(fn () => new RiskProfile('invalid', 50.0))
            ->toThrow(InvalidArgumentException::class, 'Invalid risk level: invalid');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_negative_score(): void
    {
        expect(fn () => new RiskProfile('low', -1.0))
            ->toThrow(InvalidArgumentException::class, 'Risk score must be between 0 and 100');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_score_over_100(): void
    {
        expect(fn () => new RiskProfile('high', 101.0))
            ->toThrow(InvalidArgumentException::class, 'Risk score must be between 0 and 100');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_boundary_scores(): void
    {
        $zero = new RiskProfile('low', 0.0);
        $hundred = new RiskProfile('very_high', 100.0);

        expect($zero->getScore())->toBe(0.0);
        expect($hundred->getScore())->toBe(100.0);
    }

    // ===========================================
    // fromScore Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_low_profile_from_score(): void
    {
        $profile = RiskProfile::fromScore(20.0);

        expect($profile->getLevel())->toBe('low');
        expect($profile->getScore())->toBe(20.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_medium_profile_from_score(): void
    {
        $profile = RiskProfile::fromScore(40.0);

        expect($profile->getLevel())->toBe('medium');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_high_profile_from_score(): void
    {
        $profile = RiskProfile::fromScore(60.0);

        expect($profile->getLevel())->toBe('high');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_very_high_profile_from_score(): void
    {
        $profile = RiskProfile::fromScore(85.0);

        expect($profile->getLevel())->toBe('very_high');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_respects_score_thresholds(): void
    {
        expect(RiskProfile::fromScore(25.0)->getLevel())->toBe('low');
        expect(RiskProfile::fromScore(25.1)->getLevel())->toBe('medium');
        expect(RiskProfile::fromScore(50.0)->getLevel())->toBe('medium');
        expect(RiskProfile::fromScore(50.1)->getLevel())->toBe('high');
        expect(RiskProfile::fromScore(75.0)->getLevel())->toBe('high');
        expect(RiskProfile::fromScore(75.1)->getLevel())->toBe('very_high');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_factors_from_score(): void
    {
        $factors = ['market' => 'volatile', 'credit' => 'stable'];
        $profile = RiskProfile::fromScore(50.0, $factors);

        expect($profile->getFactors())->toBe($factors);
    }

    // ===========================================
    // getMaxExposure Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_max_exposure(): void
    {
        expect((new RiskProfile('low', 10.0))->getMaxExposure())->toBe(0.10);
        expect((new RiskProfile('medium', 40.0))->getMaxExposure())->toBe(0.25);
        expect((new RiskProfile('high', 60.0))->getMaxExposure())->toBe(0.50);
        expect((new RiskProfile('very_high', 90.0))->getMaxExposure())->toBe(0.75);
    }

    // ===========================================
    // getRequiredLiquidity Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_correct_required_liquidity(): void
    {
        expect((new RiskProfile('low', 10.0))->getRequiredLiquidity())->toBe(0.50);
        expect((new RiskProfile('medium', 40.0))->getRequiredLiquidity())->toBe(0.35);
        expect((new RiskProfile('high', 60.0))->getRequiredLiquidity())->toBe(0.20);
        expect((new RiskProfile('very_high', 90.0))->getRequiredLiquidity())->toBe(0.10);
    }

    // ===========================================
    // isAcceptable Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_determines_acceptable_risk(): void
    {
        expect((new RiskProfile('low', 25.0))->isAcceptable())->toBeTrue();
        expect((new RiskProfile('medium', 50.0))->isAcceptable())->toBeTrue();
        expect((new RiskProfile('high', 75.0))->isAcceptable())->toBeTrue();
        expect((new RiskProfile('very_high', 76.0))->isAcceptable())->toBeFalse();
        expect((new RiskProfile('very_high', 100.0))->isAcceptable())->toBeFalse();
    }

    // ===========================================
    // requiresApproval Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_determines_approval_requirement(): void
    {
        expect((new RiskProfile('low', 10.0))->requiresApproval())->toBeFalse();
        expect((new RiskProfile('medium', 40.0))->requiresApproval())->toBeFalse();
        expect((new RiskProfile('high', 60.0))->requiresApproval())->toBeTrue();
        expect((new RiskProfile('very_high', 90.0))->requiresApproval())->toBeTrue();
    }

    // ===========================================
    // equals Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_equal_profiles(): void
    {
        $profile1 = new RiskProfile('medium', 50.0);
        $profile2 = new RiskProfile('medium', 50.0);
        $profile3 = new RiskProfile('medium', 50.005); // Within tolerance

        expect($profile1->equals($profile2))->toBeTrue();
        expect($profile1->equals($profile3))->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_unequal_profiles(): void
    {
        $profile1 = new RiskProfile('medium', 50.0);
        $profile2 = new RiskProfile('high', 50.0);
        $profile3 = new RiskProfile('medium', 55.0);

        expect($profile1->equals($profile2))->toBeFalse();
        expect($profile1->equals($profile3))->toBeFalse();
    }

    // ===========================================
    // __toString Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_string(): void
    {
        $profile = new RiskProfile('high', 65.5);

        expect((string) $profile)->toBe('high (65.50)');
    }
}
