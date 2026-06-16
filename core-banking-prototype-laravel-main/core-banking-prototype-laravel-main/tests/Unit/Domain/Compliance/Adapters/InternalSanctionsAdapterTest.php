<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Compliance\Adapters;

use App\Domain\Compliance\Adapters\InternalSanctionsAdapter;
use App\Domain\Compliance\Contracts\SanctionsScreeningInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;

class InternalSanctionsAdapterTest extends ServiceTestCase
{
    private InternalSanctionsAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new InternalSanctionsAdapter();
    }

    #[Test]
    public function test_implements_sanctions_screening_interface(): void
    {
        $this->assertInstanceOf(SanctionsScreeningInterface::class, $this->adapter);
    }

    #[Test]
    public function test_get_name_returns_internal(): void
    {
        $this->assertEquals('Internal', $this->adapter->getName());
    }

    #[Test]
    public function test_screen_individual_returns_correct_structure(): void
    {
        $results = $this->adapter->screenIndividual(['name' => 'John Doe']);

        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('lists_checked', $results);
        $this->assertArrayHasKey('total_matches', $results);
        $this->assertIsArray($results['matches']);
        $this->assertIsArray($results['lists_checked']);
        $this->assertIsInt($results['total_matches']);
    }

    #[Test]
    public function test_screen_individual_checks_all_three_lists(): void
    {
        $results = $this->adapter->screenIndividual(['name' => 'John Doe']);

        $this->assertContains('OFAC', $results['lists_checked']);
        $this->assertContains('EU', $results['lists_checked']);
        $this->assertContains('UN', $results['lists_checked']);
    }

    #[Test]
    public function test_screen_individual_no_match_for_normal_name(): void
    {
        $results = $this->adapter->screenIndividual(['name' => 'John Doe']);

        $this->assertEquals(0, $results['total_matches']);
        $this->assertEmpty($results['matches']);
    }

    #[Test]
    public function test_screen_individual_matches_test_keyword(): void
    {
        $results = $this->adapter->screenIndividual(['name' => 'Test Person']);

        $this->assertGreaterThan(0, $results['total_matches']);
        $this->assertArrayHasKey('OFAC', $results['matches']);
        $this->assertEquals('Test Person', $results['matches']['OFAC'][0]['name']);
        $this->assertEquals(92, $results['matches']['OFAC'][0]['match_score']);
        $this->assertEquals('Individual', $results['matches']['OFAC'][0]['type']);
        $this->assertEquals('CYBER2', $results['matches']['OFAC'][0]['program']);
    }

    #[Test]
    public function test_screen_individual_matches_sanctioned_keyword(): void
    {
        $results = $this->adapter->screenIndividual(['name' => 'Sanctioned Entity']);

        $this->assertGreaterThan(0, $results['total_matches']);
        $this->assertArrayHasKey('OFAC', $results['matches']);
    }

    #[Test]
    public function test_screen_address_returns_empty_results(): void
    {
        $results = $this->adapter->screenAddress('0x1234567890abcdef', 'ethereum');

        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('lists_checked', $results);
        $this->assertArrayHasKey('total_matches', $results);
        $this->assertEquals(0, $results['total_matches']);
        $this->assertEmpty($results['matches']);
        $this->assertContains('Internal', $results['lists_checked']);
    }
}
