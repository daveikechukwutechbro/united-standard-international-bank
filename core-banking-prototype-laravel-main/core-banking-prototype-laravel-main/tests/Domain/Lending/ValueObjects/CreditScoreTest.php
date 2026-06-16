<?php

declare(strict_types=1);

namespace Tests\Domain\Lending\ValueObjects;

use App\Domain\Lending\ValueObjects\CreditScore;
use InvalidArgumentException;
use Tests\UnitTestCase;

class CreditScoreTest extends UnitTestCase
{
    // ===========================================
    // Construction Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_credit_score_with_valid_data(): void
    {
        $creditScore = new CreditScore(
            score: 750,
            bureau: 'Equifax',
            creditReport: ['inquiries' => 2, 'accounts' => 10]
        );

        expect($creditScore->score)->toBe(750);
        expect($creditScore->bureau)->toBe('Equifax');
        expect($creditScore->creditReport)->toBe(['inquiries' => 2, 'accounts' => 10]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_credit_score_at_minimum_boundary(): void
    {
        $creditScore = new CreditScore(
            score: 300,
            bureau: 'TransUnion',
            creditReport: []
        );

        expect($creditScore->score)->toBe(300);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_credit_score_at_maximum_boundary(): void
    {
        $creditScore = new CreditScore(
            score: 850,
            bureau: 'Experian',
            creditReport: []
        );

        expect($creditScore->score)->toBe(850);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_score_below_minimum(): void
    {
        expect(fn () => new CreditScore(
            score: 299,
            bureau: 'Equifax',
            creditReport: []
        ))->toThrow(InvalidArgumentException::class, 'Credit score must be between 300 and 850');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_for_score_above_maximum(): void
    {
        expect(fn () => new CreditScore(
            score: 851,
            bureau: 'Equifax',
            creditReport: []
        ))->toThrow(InvalidArgumentException::class, 'Credit score must be between 300 and 850');
    }

    // ===========================================
    // isExcellent Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_excellent_credit_score(): void
    {
        $score = new CreditScore(800, 'Equifax', []);
        expect($score->isExcellent())->toBeTrue();

        $score = new CreditScore(850, 'Equifax', []);
        expect($score->isExcellent())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_correctly_rejects_non_excellent_scores(): void
    {
        $score = new CreditScore(799, 'Equifax', []);
        expect($score->isExcellent())->toBeFalse();
    }

    // ===========================================
    // isGood Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_good_credit_score(): void
    {
        $score = new CreditScore(700, 'Equifax', []);
        expect($score->isGood())->toBeTrue();

        $score = new CreditScore(750, 'Equifax', []);
        expect($score->isGood())->toBeTrue();

        $score = new CreditScore(799, 'Equifax', []);
        expect($score->isGood())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_correctly_rejects_non_good_scores(): void
    {
        // Too low
        $score = new CreditScore(699, 'Equifax', []);
        expect($score->isGood())->toBeFalse();

        // Too high (excellent)
        $score = new CreditScore(800, 'Equifax', []);
        expect($score->isGood())->toBeFalse();
    }

    // ===========================================
    // isFair Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_fair_credit_score(): void
    {
        $score = new CreditScore(600, 'Equifax', []);
        expect($score->isFair())->toBeTrue();

        $score = new CreditScore(650, 'Equifax', []);
        expect($score->isFair())->toBeTrue();

        $score = new CreditScore(699, 'Equifax', []);
        expect($score->isFair())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_correctly_rejects_non_fair_scores(): void
    {
        // Too low (poor)
        $score = new CreditScore(599, 'Equifax', []);
        expect($score->isFair())->toBeFalse();

        // Too high (good)
        $score = new CreditScore(700, 'Equifax', []);
        expect($score->isFair())->toBeFalse();
    }

    // ===========================================
    // isPoor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_poor_credit_score(): void
    {
        $score = new CreditScore(300, 'Equifax', []);
        expect($score->isPoor())->toBeTrue();

        $score = new CreditScore(500, 'Equifax', []);
        expect($score->isPoor())->toBeTrue();

        $score = new CreditScore(599, 'Equifax', []);
        expect($score->isPoor())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_correctly_rejects_non_poor_scores(): void
    {
        $score = new CreditScore(600, 'Equifax', []);
        expect($score->isPoor())->toBeFalse();
    }

    // ===========================================
    // Score Category Mutually Exclusive Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_ensures_only_one_category_is_true(): void
    {
        $testCases = [300, 500, 599, 600, 650, 699, 700, 750, 799, 800, 850];

        foreach ($testCases as $scoreValue) {
            $score = new CreditScore($scoreValue, 'Equifax', []);
            $categories = [
                $score->isExcellent(),
                $score->isGood(),
                $score->isFair(),
                $score->isPoor(),
            ];

            expect(array_sum(array_map('intval', $categories)))->toBe(1);
        }
    }

    // ===========================================
    // toArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_array(): void
    {
        $creditReport = ['inquiries' => 3, 'accounts' => 15, 'delinquencies' => 0];
        $creditScore = new CreditScore(
            score: 725,
            bureau: 'Experian',
            creditReport: $creditReport
        );

        $array = $creditScore->toArray();

        expect($array)->toBe([
            'score'         => 725,
            'bureau'        => 'Experian',
            'credit_report' => $creditReport,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_empty_credit_report_to_array(): void
    {
        $creditScore = new CreditScore(
            score: 650,
            bureau: 'TransUnion',
            creditReport: []
        );

        $array = $creditScore->toArray();

        expect($array['credit_report'])->toBe([]);
    }
}
