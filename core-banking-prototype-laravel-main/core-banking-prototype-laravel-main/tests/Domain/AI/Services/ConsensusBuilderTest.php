<?php

declare(strict_types=1);

namespace Tests\Domain\AI\Services;

use App\Domain\AI\Services\ConsensusBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConsensusBuilder service.
 */
class ConsensusBuilderTest extends TestCase
{
    private ConsensusBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ConsensusBuilder();
    }

    public function test_build_returns_correct_structure(): void
    {
        $inputs = [
            ['agent' => 'agent1', 'value' => 100],
            ['agent' => 'agent2', 'value' => 105],
        ];

        $result = $this->builder->build($inputs);

        $this->assertArrayHasKey('consensus_type', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('participants', $result);
    }

    public function test_build_returns_majority_consensus_type(): void
    {
        $inputs = [['data' => 'test']];

        $result = $this->builder->build($inputs);

        $this->assertEquals('majority', $result['consensus_type']);
    }

    public function test_build_returns_confidence_value(): void
    {
        $inputs = [['data' => 'test']];

        $result = $this->builder->build($inputs);

        $this->assertEquals(0.75, $result['confidence']);
    }

    public function test_build_counts_participants_correctly(): void
    {
        $inputs = [
            ['agent' => 'agent1'],
            ['agent' => 'agent2'],
            ['agent' => 'agent3'],
        ];

        $result = $this->builder->build($inputs);

        $this->assertEquals(3, $result['participants']);
    }

    public function test_build_handles_empty_inputs(): void
    {
        $result = $this->builder->build([]);

        $this->assertEquals(0, $result['participants']);
        $this->assertEquals('majority', $result['consensus_type']);
    }

    public function test_build_handles_single_input(): void
    {
        $inputs = [['single' => 'participant']];

        $result = $this->builder->build($inputs);

        $this->assertEquals(1, $result['participants']);
    }

    public function test_build_handles_large_number_of_inputs(): void
    {
        $inputs = array_fill(0, 100, ['data' => 'test']);

        $result = $this->builder->build($inputs);

        $this->assertEquals(100, $result['participants']);
    }
}
