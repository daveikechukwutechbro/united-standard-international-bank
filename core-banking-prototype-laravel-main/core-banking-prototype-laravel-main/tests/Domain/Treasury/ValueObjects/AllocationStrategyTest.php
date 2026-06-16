<?php

declare(strict_types=1);

namespace Tests\Domain\Treasury\ValueObjects;

use App\Domain\Treasury\ValueObjects\AllocationStrategy;
use InvalidArgumentException;
use Tests\UnitTestCase;

class AllocationStrategyTest extends UnitTestCase
{
    // ===========================================
    // Constructor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_valid_strategy(): void
    {
        $strategy = new AllocationStrategy('balanced');

        expect($strategy->getValue())->toBe('balanced');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_all_valid_strategies(): void
    {
        $validStrategies = ['conservative', 'balanced', 'aggressive', 'custom'];

        foreach ($validStrategies as $strategyValue) {
            if ($strategyValue === 'custom') {
                $strategy = new AllocationStrategy($strategyValue, [
                    ['type' => 'cash', 'percentage' => 100.0],
                ]);
            } else {
                $strategy = new AllocationStrategy($strategyValue);
            }
            expect($strategy->getValue())->toBe($strategyValue);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_invalid_strategy(): void
    {
        expect(fn () => new AllocationStrategy('invalid'))
            ->toThrow(InvalidArgumentException::class, 'Invalid allocation strategy: invalid');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_custom_without_allocations(): void
    {
        expect(fn () => new AllocationStrategy('custom'))
            ->toThrow(InvalidArgumentException::class, 'Custom strategy requires allocation details');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_allocations_not_summing_to_100(): void
    {
        expect(fn () => new AllocationStrategy('custom', [
            ['type' => 'cash', 'percentage' => 50.0],
            ['type' => 'bonds', 'percentage' => 30.0],
        ]))->toThrow(InvalidArgumentException::class, 'Allocations must sum to 100%');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_allocations_summing_to_100(): void
    {
        $allocations = [
            ['type' => 'cash', 'percentage' => 30.0],
            ['type' => 'bonds', 'percentage' => 40.0],
            ['type' => 'equities', 'percentage' => 30.0],
        ];

        $strategy = new AllocationStrategy('custom', $allocations);

        expect($strategy->getAllocations())->toBe($allocations);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_allocations_with_floating_point_tolerance(): void
    {
        $allocations = [
            ['type' => 'cash', 'percentage' => 33.33],
            ['type' => 'bonds', 'percentage' => 33.33],
            ['type' => 'equities', 'percentage' => 33.34],
        ];

        $strategy = new AllocationStrategy('custom', $allocations);

        expect($strategy->getValue())->toBe('custom');
    }

    // ===========================================
    // getDefaultAllocations Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_conservative_default_allocations(): void
    {
        $strategy = new AllocationStrategy('conservative');
        $allocations = $strategy->getDefaultAllocations();

        expect($allocations)->toHaveCount(3);
        expect($this->findAllocation($allocations, 'cash'))->toBe(40.0);
        expect($this->findAllocation($allocations, 'bonds'))->toBe(50.0);
        expect($this->findAllocation($allocations, 'equities'))->toBe(10.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_balanced_default_allocations(): void
    {
        $strategy = new AllocationStrategy('balanced');
        $allocations = $strategy->getDefaultAllocations();

        expect($this->findAllocation($allocations, 'cash'))->toBe(20.0);
        expect($this->findAllocation($allocations, 'bonds'))->toBe(40.0);
        expect($this->findAllocation($allocations, 'equities'))->toBe(40.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_aggressive_default_allocations(): void
    {
        $strategy = new AllocationStrategy('aggressive');
        $allocations = $strategy->getDefaultAllocations();

        expect($this->findAllocation($allocations, 'cash'))->toBe(10.0);
        expect($this->findAllocation($allocations, 'bonds'))->toBe(20.0);
        expect($this->findAllocation($allocations, 'equities'))->toBe(70.0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_custom_allocations_for_custom_strategy(): void
    {
        $customAllocations = [
            ['type' => 'crypto', 'percentage' => 50.0],
            ['type' => 'cash', 'percentage' => 50.0],
        ];

        $strategy = new AllocationStrategy('custom', $customAllocations);

        expect($strategy->getDefaultAllocations())->toBe($customAllocations);
    }

    // ===========================================
    // equals Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_equal_strategies(): void
    {
        $strategy1 = new AllocationStrategy('balanced');
        $strategy2 = new AllocationStrategy('balanced');

        expect($strategy1->equals($strategy2))->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_unequal_strategies(): void
    {
        $strategy1 = new AllocationStrategy('balanced');
        $strategy2 = new AllocationStrategy('aggressive');

        expect($strategy1->equals($strategy2))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_custom_strategies_with_same_allocations(): void
    {
        $allocations = [['type' => 'cash', 'percentage' => 100.0]];

        $strategy1 = new AllocationStrategy('custom', $allocations);
        $strategy2 = new AllocationStrategy('custom', $allocations);

        expect($strategy1->equals($strategy2))->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_custom_strategies_with_different_allocations(): void
    {
        $strategy1 = new AllocationStrategy('custom', [['type' => 'cash', 'percentage' => 100.0]]);
        $strategy2 = new AllocationStrategy('custom', [['type' => 'bonds', 'percentage' => 100.0]]);

        expect($strategy1->equals($strategy2))->toBeFalse();
    }

    // ===========================================
    // __toString Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_string(): void
    {
        $strategy = new AllocationStrategy('aggressive');

        expect((string) $strategy)->toBe('aggressive');
    }

    // ===========================================
    // Helper Methods
    // ===========================================

    /**
     * @param array<int, array{type: string, percentage: float}> $allocations
     */
    private function findAllocation(array $allocations, string $type): ?float
    {
        foreach ($allocations as $allocation) {
            if ($allocation['type'] === $type) {
                return $allocation['percentage'];
            }
        }

        return null;
    }
}
