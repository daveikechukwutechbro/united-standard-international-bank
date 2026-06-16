<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Privacy;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PrivacyPoolStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_pool_stats_returns_statistics(): void
    {
        $response = $this->getJson('/api/v1/privacy/pool-stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'totalPoolSize',
                    'poolSizeCurrency',
                    'participantCount',
                    'privacyStrength',
                    'lastUpdated',
                ],
            ])
            ->assertJsonPath('data.poolSizeCurrency', 'USD');
    }

    public function test_pool_stats_returns_valid_privacy_strength(): void
    {
        $response = $this->getJson('/api/v1/privacy/pool-stats');

        $response->assertOk();

        $strength = $response->json('data.privacyStrength');
        $this->assertContains($strength, ['weak', 'moderate', 'strong']);
    }

    public function test_pool_stats_participant_count_is_non_negative(): void
    {
        $response = $this->getJson('/api/v1/privacy/pool-stats');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(0, $response->json('data.participantCount'));
    }

    public function test_pool_stats_is_public_endpoint(): void
    {
        // No auth token — should still succeed
        $response = $this->getJson('/api/v1/privacy/pool-stats');

        $response->assertOk();
    }
}
