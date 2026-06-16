<?php

use App\Models\User;
use App\Models\BasketAsset;
use App\Models\BasketValue;
use App\Domain\Asset\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    
    // Create test assets
    $this->btc = Asset::firstOrCreate(
        ['code' => 'BTC'],
        ['name' => 'Bitcoin', 'type' => 'crypto', 'precision' => 8, 'is_active' => true, 'metadata' => []]
    );
    
    $this->eth = Asset::firstOrCreate(
        ['code' => 'ETH'],
        ['name' => 'Ethereum', 'type' => 'crypto', 'precision' => 18, 'is_active' => true, 'metadata' => []]
    );
    
    $this->usd = Asset::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'type' => 'fiat', 'precision' => 2, 'is_active' => true, 'metadata' => []]
    );
    
    // Create test basket assets
    $this->cryptoBasket = BasketAsset::factory()->create([
        'code' => 'CRYPTO',
        'name' => 'Crypto Basket',
        'description' => 'Diversified cryptocurrency basket',
        'is_active' => true,
        'components' => [
            ['asset_code' => 'BTC', 'weight' => 0.6, 'target_allocation' => 60.0],
            ['asset_code' => 'ETH', 'weight' => 0.4, 'target_allocation' => 40.0]
        ],
        'rebalance_threshold' => 0.05,
        'management_fee' => 0.001
    ]);
    
    $this->balancedBasket = BasketAsset::factory()->create([
        'code' => 'BALANCED',
        'name' => 'Balanced Portfolio',
        'description' => 'Balanced investment portfolio',
        'is_active' => true,
        'components' => [
            ['asset_code' => 'BTC', 'weight' => 0.3, 'target_allocation' => 30.0],
            ['asset_code' => 'ETH', 'weight' => 0.2, 'target_allocation' => 20.0],
            ['asset_code' => 'USD', 'weight' => 0.5, 'target_allocation' => 50.0]
        ]
    ]);
});

describe('Basket API - Public Endpoints', function () {
    
    test('can list all baskets', function () {
        $response = $this->getJson('/api/v2/baskets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'description',
                        'is_active',
                        'components',
                        'rebalance_threshold',
                        'management_fee',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'current_page',
                'total'
            ]);

        expect($response->json('data'))->toHaveCount(2);
    });

    test('can get specific basket details', function () {
        $response = $this->getJson('/api/v2/baskets/CRYPTO');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'code',
                'name',
                'description',
                'is_active',
                'components' => [
                    '*' => [
                        'asset_code',
                        'weight',
                        'target_allocation'
                    ]
                ],
                'rebalance_threshold',
                'management_fee',
                'current_value',
                'total_supply'
            ]);

        expect($response->json('code'))->toBe('CRYPTO');
        expect($response->json('components'))->toHaveCount(2);
    });

    test('returns 404 for non-existent basket', function () {
        $response = $this->getJson('/api/v2/baskets/NONEXISTENT');

        $response->assertStatus(404);
    });

    test('can get basket value', function () {
        // Create basket value record
        BasketValue::factory()->create([
            'basket_code' => 'CRYPTO',
            'total_value_usd' => 1000000,
            'unit_value_usd' => 100.50,
            'total_supply' => 9950.25,
            'component_values' => [
                'BTC' => ['value_usd' => 600000, 'allocation' => 60.0, 'drift' => 0.0],
                'ETH' => ['value_usd' => 400000, 'allocation' => 40.0, 'drift' => 0.0]
            ]
        ]);

        $response = $this->getJson('/api/v2/baskets/CRYPTO/value');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'basket_code',
                'total_value_usd',
                'unit_value_usd',
                'total_supply',
                'component_values' => [
                    '*' => [
                        'value_usd',
                        'allocation',
                        'drift'
                    ]
                ],
                'calculated_at'
            ]);

        expect($response->json('basket_code'))->toBe('CRYPTO');
        expect($response->json('unit_value_usd'))->toBe(100.50);
    });

    test('can get basket value history', function () {
        // Create historical value records
        BasketValue::factory()->count(5)->create([
            'basket_code' => 'CRYPTO',
            'created_at' => now()->subDays(rand(1, 30))
        ]);

        $response = $this->getJson('/api/v2/baskets/CRYPTO/history?days=30');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'basket_code',
                'history' => [
                    '*' => [
                        'date',
                        'unit_value_usd',
                        'total_value_usd',
                        'total_supply'
                    ]
                ],
                'period_start',
                'period_end'
            ]);

        expect($response->json('history'))->toHaveCount(5);
    });

    test('filters baskets by active status', function () {
        // Create inactive basket
        BasketAsset::factory()->create([
            'code' => 'INACTIVE',
            'is_active' => false
        ]);

        $response = $this->getJson('/api/v2/baskets?active=true');

        $response->assertStatus(200);
        
        $activeCodes = collect($response->json('data'))->pluck('code')->toArray();
        expect($activeCodes)->not->toContain('INACTIVE');
        expect($activeCodes)->toContain('CRYPTO');
    });

    test('handles pagination for basket listing', function () {
        // Create additional baskets
        BasketAsset::factory()->count(15)->create();

        $response = $this->getJson('/api/v2/baskets?per_page=5');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(5);
        expect($response->json('total'))->toBe(17); // 2 + 15
    });
});

describe('Basket API - Protected Endpoints', function () {
    
    beforeEach(function () {
        Sanctum::actingAs($this->user, ['*']);
    });

    test('can create new basket', function () {
        $basketData = [
            'code' => 'TECH',
            'name' => 'Technology Basket',
            'description' => 'Technology-focused investment basket',
            'components' => [
                ['asset_code' => 'BTC', 'weight' => 0.7, 'target_allocation' => 70.0],
                ['asset_code' => 'ETH', 'weight' => 0.3, 'target_allocation' => 30.0]
            ],
            'rebalance_threshold' => 0.03,
            'management_fee' => 0.002
        ];

        $response = $this->postJson('/api/v2/baskets', $basketData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'code',
                'name',
                'description',
                'components',
                'rebalance_threshold',
                'management_fee',
                'created_at'
            ]);

        expect($response->json('code'))->toBe('TECH');
        expect($response->json('components'))->toHaveCount(2);
    });

    test('validates basket creation data', function () {
        $response = $this->postJson('/api/v2/baskets', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name', 'description', 'components']);
    });

    test('validates component weights sum to 1', function () {
        $basketData = [
            'code' => 'INVALID',
            'name' => 'Invalid Basket',
            'description' => 'Test basket',
            'components' => [
                ['asset_code' => 'BTC', 'weight' => 0.7, 'target_allocation' => 70.0],
                ['asset_code' => 'ETH', 'weight' => 0.4, 'target_allocation' => 40.0] // Total weight = 1.1
            ]
        ];

        $response = $this->postJson('/api/v2/baskets', $basketData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['components']);
    });

    test('prevents duplicate basket codes', function () {
        $basketData = [
            'code' => 'CRYPTO', // Already exists
            'name' => 'Another Crypto Basket',
            'description' => 'Test basket',
            'components' => [
                ['asset_code' => 'BTC', 'weight' => 1.0, 'target_allocation' => 100.0]
            ]
        ];

        $response = $this->postJson('/api/v2/baskets', $basketData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    });

    test('can trigger basket rebalancing', function () {
        $response = $this->postJson("/api/v2/baskets/CRYPTO/rebalance");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'rebalance_id',
                'basket_code',
                'triggered_at',
                'estimated_completion'
            ]);

        expect($response->json('basket_code'))->toBe('CRYPTO');
    });

    test('prevents rebalancing of inactive baskets', function () {
        $this->cryptoBasket->update(['is_active' => false]);

        $response = $this->postJson("/api/v2/baskets/CRYPTO/rebalance");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot rebalance inactive basket']);
    });

    test('handles rebalancing cooldown period', function () {
        // Mock a recent rebalance
        BasketValue::factory()->create([
            'basket_code' => 'CRYPTO',
            'metadata' => ['last_rebalance' => now()->subMinutes(5)->toISOString()]
        ]);

        $response = $this->postJson("/api/v2/baskets/CRYPTO/rebalance");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Rebalancing is on cooldown']);
    });

    test('requires authentication for protected endpoints', function () {
        // Remove authentication
        $this->withHeaders(['Authorization' => 'Bearer invalid-token']);

        $protectedEndpoints = [
            ['POST', '/api/v2/baskets'],
            ['POST', '/api/v2/baskets/CRYPTO/rebalance']
        ];

        foreach ($protectedEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint, []);
            $response->assertStatus(401);
        }
    });

    test('validates asset codes in components', function () {
        $basketData = [
            'code' => 'INVALID',
            'name' => 'Invalid Basket',
            'description' => 'Test basket',
            'components' => [
                ['asset_code' => 'NONEXISTENT', 'weight' => 1.0, 'target_allocation' => 100.0]
            ]
        ];

        $response = $this->postJson('/api/v2/baskets', $basketData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['components.0.asset_code']);
    });

    test('validates weight ranges', function () {
        $basketData = [
            'code' => 'INVALID',
            'name' => 'Invalid Basket',
            'description' => 'Test basket',
            'components' => [
                ['asset_code' => 'BTC', 'weight' => -0.1, 'target_allocation' => 100.0]
            ]
        ];

        $response = $this->postJson('/api/v2/baskets', $basketData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['components.0.weight']);
    });

    test('can search baskets by name or description', function () {
        $response = $this->getJson('/api/v2/baskets?search=crypto');

        $response->assertStatus(200);
        
        $foundBaskets = collect($response->json('data'));
        expect($foundBaskets->where('code', 'CRYPTO'))->toHaveCount(1);
    });

    test('handles basket value calculation errors gracefully', function () {
        // Create basket with invalid component
        $invalidBasket = BasketAsset::factory()->create([
            'code' => 'INVALID',
            'components' => [
                ['asset_code' => 'NONEXISTENT', 'weight' => 1.0, 'target_allocation' => 100.0]
            ]
        ]);

        $response = $this->getJson('/api/v2/baskets/INVALID/value');

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Unable to calculate basket value']);
    });

    test('returns basket metrics in value response', function () {
        BasketValue::factory()->create([
            'basket_code' => 'CRYPTO',
            'component_values' => [
                'BTC' => ['value_usd' => 580000, 'allocation' => 58.0, 'drift' => -2.0],
                'ETH' => ['value_usd' => 420000, 'allocation' => 42.0, 'drift' => 2.0]
            ]
        ]);

        $response = $this->getJson('/api/v2/baskets/CRYPTO/value');

        $response->assertStatus(200);
        
        expect($response->json('component_values.BTC.drift'))->toBe(-2.0);
        expect($response->json('component_values.ETH.drift'))->toBe(2.0);
    });

    test('can filter history by date range', function () {
        // Create values across different dates
        BasketValue::factory()->create([
            'basket_code' => 'CRYPTO',
            'created_at' => now()->subDays(10)
        ]);
        
        BasketValue::factory()->create([
            'basket_code' => 'CRYPTO',
            'created_at' => now()->subDays(45) // Outside 30-day range
        ]);

        $response = $this->getJson('/api/v2/baskets/CRYPTO/history?days=30');

        $response->assertStatus(200);
        expect($response->json('history'))->toHaveCount(1);
    });
});