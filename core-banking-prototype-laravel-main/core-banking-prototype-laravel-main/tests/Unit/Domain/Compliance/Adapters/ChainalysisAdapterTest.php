<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Compliance\Adapters;

use App\Domain\Compliance\Adapters\ChainalysisAdapter;
use App\Domain\Compliance\Contracts\SanctionsScreeningInterface;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\ServiceTestCase;

class ChainalysisAdapterTest extends ServiceTestCase
{
    private ChainalysisAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = new ChainalysisAdapter(
            apiKey: 'test-api-key',
            baseUrl: 'https://api.chainalysis.com/api/sanctions/v2',
        );
    }

    #[Test]
    public function test_implements_sanctions_screening_interface(): void
    {
        $this->assertInstanceOf(SanctionsScreeningInterface::class, $this->adapter);
    }

    #[Test]
    public function test_get_name_returns_chainalysis(): void
    {
        $this->assertEquals('Chainalysis', $this->adapter->getName());
    }

    #[Test]
    public function test_screen_individual_with_no_matches(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/entities*' => Http::response([], 200),
        ]);

        $results = $this->adapter->screenIndividual(['name' => 'John Doe']);

        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('lists_checked', $results);
        $this->assertArrayHasKey('total_matches', $results);
        $this->assertEquals(0, $results['total_matches']);
        $this->assertContains('Chainalysis', $results['lists_checked']);

        Http::assertSentCount(1);
    }

    #[Test]
    public function test_screen_individual_with_matches(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/entities*' => Http::response([
                [
                    'entity_id' => 'ch-98765',
                    'name'      => 'John Doe',
                    'type'      => 'Individual',
                    'sanctions' => [
                        ['program' => 'OFAC/SDN', 'list_name' => 'OFAC SDN List'],
                    ],
                    'description' => 'Sanctioned individual',
                ],
            ], 200),
        ]);

        $results = $this->adapter->screenIndividual(['name' => 'John Doe']);

        $this->assertEquals(1, $results['total_matches']);
        $this->assertArrayHasKey('Chainalysis', $results['matches']);
        $this->assertCount(1, $results['matches']['Chainalysis']);

        $match = $results['matches']['Chainalysis'][0];
        $this->assertEquals('ch-98765', $match['sdn_id']);
        $this->assertEquals('John Doe', $match['name']);
        $this->assertEquals('Individual', $match['type']);
        $this->assertStringContainsString('OFAC/SDN', $match['program']);
        $this->assertEquals('Chainalysis', $match['source']);
    }

    #[Test]
    public function test_screen_individual_with_empty_name_returns_empty(): void
    {
        Http::fake();

        $results = $this->adapter->screenIndividual(['name' => '']);

        $this->assertEquals(0, $results['total_matches']);
        $this->assertEmpty($results['matches']);

        Http::assertNothingSent();
    }

    #[Test]
    public function test_screen_individual_without_name_key_returns_empty(): void
    {
        Http::fake();

        $results = $this->adapter->screenIndividual(['date_of_birth' => '1990-01-01']);

        $this->assertEquals(0, $results['total_matches']);
        $this->assertEmpty($results['matches']);

        Http::assertNothingSent();
    }

    #[Test]
    public function test_screen_individual_handles_api_error_gracefully(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/entities*' => Http::response(
                ['error' => 'Unauthorized'],
                401
            ),
        ]);

        $results = $this->adapter->screenIndividual(['name' => 'John Doe']);

        $this->assertEquals(0, $results['total_matches']);
        $this->assertEmpty($results['matches']);
        $this->assertContains('Chainalysis', $results['lists_checked']);
    }

    #[Test]
    public function test_screen_individual_handles_server_error_gracefully(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/entities*' => Http::response(
                'Internal Server Error',
                500
            ),
        ]);

        $results = $this->adapter->screenIndividual(['name' => 'John Doe']);

        $this->assertEquals(0, $results['total_matches']);
        $this->assertContains('Chainalysis', $results['lists_checked']);
    }

    #[Test]
    public function test_screen_address_with_no_identifications(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/addresses/*' => Http::sequence()
                ->push(['address' => '0xabc123'], 200)   // POST register
                ->push(['identifications' => []], 200),    // GET check
        ]);

        $results = $this->adapter->screenAddress('0xabc123', 'ethereum');

        $this->assertArrayHasKey('matches', $results);
        $this->assertArrayHasKey('lists_checked', $results);
        $this->assertArrayHasKey('total_matches', $results);
        $this->assertEquals(0, $results['total_matches']);
        $this->assertContains('Chainalysis', $results['lists_checked']);
    }

    #[Test]
    public function test_screen_address_with_sanctioned_address(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/addresses/*' => Http::sequence()
                ->push(['address' => '0xabc123'], 200)  // POST register
                ->push([                                  // GET check
                    'identifications' => [
                        [
                            'entity_id'   => 'ch-addr-001',
                            'category'    => 'sanctions',
                            'name'        => 'Lazarus Group',
                            'description' => 'North Korean hacking group',
                            'url'         => 'https://chainalysis.com/entity/001',
                        ],
                    ],
                ], 200),
        ]);

        $results = $this->adapter->screenAddress('0xabc123', 'ethereum');

        $this->assertEquals(1, $results['total_matches']);
        $this->assertArrayHasKey('Chainalysis', $results['matches']);

        $match = $results['matches']['Chainalysis'][0];
        $this->assertEquals('ch-addr-001', $match['sdn_id']);
        $this->assertEquals('Lazarus Group', $match['name']);
        $this->assertEquals(100, $match['match_score']);
        $this->assertEquals('Address', $match['type']);
        $this->assertEquals('sanctions', $match['program']);
        $this->assertEquals('0xabc123', $match['address']);
        $this->assertEquals('ethereum', $match['chain']);
        $this->assertEquals('Chainalysis', $match['source']);
    }

    #[Test]
    public function test_screen_address_handles_already_registered(): void
    {
        // With retry(3), a 409 will be retried, so we need enough responses
        // in the sequence for both the retried POST attempts and the GET request.
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/addresses/*' => Http::sequence()
                ->push(['error' => 'Already registered'], 409) // POST attempt 1
                ->push(['error' => 'Already registered'], 409) // POST attempt 2 (retry)
                ->push(['error' => 'Already registered'], 409) // POST attempt 3 (retry)
                ->push(['identifications' => []], 200),         // GET check
        ]);

        $results = $this->adapter->screenAddress('0xabc123', 'ethereum');

        // Should still return results even if registration returned 409
        $this->assertArrayHasKey('matches', $results);
        $this->assertEquals(0, $results['total_matches']);
        $this->assertContains('Chainalysis', $results['lists_checked']);
    }

    #[Test]
    public function test_screen_address_handles_registration_failure(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/addresses/*' => Http::response(
                ['error' => 'Bad Request'],
                400
            ),
        ]);

        $results = $this->adapter->screenAddress('0xabc123', 'ethereum');

        $this->assertEquals(0, $results['total_matches']);
        $this->assertContains('Chainalysis', $results['lists_checked']);
    }

    #[Test]
    public function test_screen_address_with_empty_address_returns_empty(): void
    {
        Http::fake();

        $results = $this->adapter->screenAddress('', 'ethereum');

        $this->assertEquals(0, $results['total_matches']);
        $this->assertEmpty($results['matches']);

        Http::assertNothingSent();
    }

    #[Test]
    public function test_screen_individual_sends_correct_auth_header(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/entities*' => Http::response([], 200),
        ]);

        $this->adapter->screenIndividual(['name' => 'John Doe']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Token', 'test-api-key')
                && $request->hasHeader('Accept', 'application/json');
        });
    }

    #[Test]
    public function test_screen_individual_multiple_matches(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/entities*' => Http::response([
                [
                    'entity_id'   => 'ch-001',
                    'name'        => 'John Doe Sr.',
                    'type'        => 'Individual',
                    'sanctions'   => [['program' => 'OFAC/SDN']],
                    'description' => 'First match',
                ],
                [
                    'entity_id'   => 'ch-002',
                    'name'        => 'John Doe Jr.',
                    'type'        => 'Individual',
                    'sanctions'   => [['program' => 'EU/CFSP']],
                    'description' => 'Second match',
                ],
            ], 200),
        ]);

        $results = $this->adapter->screenIndividual(['name' => 'John Doe']);

        $this->assertEquals(2, $results['total_matches']);
        $this->assertCount(2, $results['matches']['Chainalysis']);
        $this->assertEquals('ch-001', $results['matches']['Chainalysis'][0]['sdn_id']);
        $this->assertEquals('ch-002', $results['matches']['Chainalysis'][1]['sdn_id']);
    }

    #[Test]
    public function test_screen_individual_skips_entities_without_name(): void
    {
        Http::fake([
            'api.chainalysis.com/api/sanctions/v2/entities*' => Http::response([
                [
                    'entity_id' => 'ch-001',
                    // No 'name' or 'full_name' key
                    'type'      => 'Individual',
                    'sanctions' => [['program' => 'OFAC/SDN']],
                ],
                [
                    'entity_id' => 'ch-002',
                    'name'      => 'Valid Entity',
                    'type'      => 'Individual',
                    'sanctions' => [['program' => 'EU/CFSP']],
                ],
            ], 200),
        ]);

        $results = $this->adapter->screenIndividual(['name' => 'Valid Entity']);

        $this->assertEquals(1, $results['total_matches']);
    }
}
