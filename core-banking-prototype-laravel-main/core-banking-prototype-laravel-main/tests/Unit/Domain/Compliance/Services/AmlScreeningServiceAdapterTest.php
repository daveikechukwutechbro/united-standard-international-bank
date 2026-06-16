<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Compliance\Services;

use App\Domain\Compliance\Adapters\InternalSanctionsAdapter;
use App\Domain\Compliance\Contracts\SanctionsScreeningInterface;
use App\Domain\Compliance\Services\AmlScreeningService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;

class AmlScreeningServiceAdapterTest extends ServiceTestCase
{
    #[Test]
    public function test_service_uses_fallback_when_no_adapter_provided(): void
    {
        Http::fake();

        $service = new AmlScreeningService();

        $results = $service->performSanctionsCheck(['name' => 'John Doe']);

        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('lists_checked', $results);
        $this->assertContains('OFAC', $results['lists_checked']);
        $this->assertContains('EU', $results['lists_checked']);
        $this->assertContains('UN', $results['lists_checked']);
    }

    #[Test]
    public function test_service_delegates_to_adapter_when_provided(): void
    {
        $mockAdapter = $this->createMock(SanctionsScreeningInterface::class);
        $mockAdapter->method('getName')->willReturn('MockProvider');
        $mockAdapter->method('screenIndividual')->willReturn([
            'matches'       => ['MockProvider' => [['name' => 'Match', 'match_score' => 99]]],
            'lists_checked' => ['MockProvider'],
            'total_matches' => 1,
        ]);

        $service = new AmlScreeningService($mockAdapter);

        $results = $service->performSanctionsCheck(['name' => 'John Doe']);

        $this->assertEquals(1, $results['total_matches']);
        $this->assertContains('MockProvider', $results['lists_checked']);
        $this->assertArrayHasKey('MockProvider', $results['matches']);
    }

    #[Test]
    public function test_service_with_internal_adapter_behaves_same_as_fallback(): void
    {
        Http::fake();

        $internalAdapter = new InternalSanctionsAdapter();
        $serviceWithAdapter = new AmlScreeningService($internalAdapter);
        $serviceWithoutAdapter = new AmlScreeningService();

        $paramsClean = ['name' => 'Clean Person'];
        $paramsMatch = ['name' => 'Test Person'];

        $adapterClean = $serviceWithAdapter->performSanctionsCheck($paramsClean);
        $fallbackClean = $serviceWithoutAdapter->performSanctionsCheck($paramsClean);

        $this->assertEquals($adapterClean['total_matches'], $fallbackClean['total_matches']);
        $this->assertEquals($adapterClean['lists_checked'], $fallbackClean['lists_checked']);

        $adapterMatch = $serviceWithAdapter->performSanctionsCheck($paramsMatch);
        $fallbackMatch = $serviceWithoutAdapter->performSanctionsCheck($paramsMatch);

        $this->assertEquals($adapterMatch['total_matches'], $fallbackMatch['total_matches']);
        $this->assertArrayHasKey('OFAC', $adapterMatch['matches']);
        $this->assertArrayHasKey('OFAC', $fallbackMatch['matches']);
    }

    #[Test]
    public function test_adapter_screen_individual_called_with_correct_params(): void
    {
        $searchParams = [
            'name'          => 'Jane Smith',
            'date_of_birth' => '1985-03-15',
            'nationality'   => 'US',
        ];

        $mockAdapter = $this->createMock(SanctionsScreeningInterface::class);
        $mockAdapter->method('getName')->willReturn('TestAdapter');
        $mockAdapter->expects($this->once())
            ->method('screenIndividual')
            ->with($searchParams)
            ->willReturn([
                'matches'       => [],
                'lists_checked' => ['TestAdapter'],
                'total_matches' => 0,
            ]);

        $service = new AmlScreeningService($mockAdapter);
        $service->performSanctionsCheck($searchParams);
    }
}
