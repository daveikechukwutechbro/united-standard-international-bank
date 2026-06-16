<?php

use App\Domain\Governance\Enums\PollStatus;
use App\Domain\Governance\Enums\PollType;
use App\Domain\Governance\Models\Poll;
use App\Domain\Governance\Models\Vote;
use App\Models\User;
use App\Models\Account;
use App\Domain\Asset\Models\Asset;
use App\Models\Balance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*']);
    
    // Create USD asset for balance requirements
    $this->usdAsset = Asset::firstOrCreate(
        ['code' => 'USD'],
        ['name' => 'US Dollar', 'type' => 'fiat', 'precision' => 2, 'is_active' => true, 'metadata' => []]
    );
    
    // Create user account with balance
    $this->account = Account::factory()->create(['user_id' => $this->user->id]);
    Balance::factory()->create([
        'account_id' => $this->account->id,
        'asset_id' => $this->usdAsset->id,
        'amount' => 100000 // $1000 balance for voting power
    ]);
});

describe('Poll API', function () {
    
    test('can list all polls', function () {
        // Create test polls
        Poll::factory()->count(3)->create([
            'status' => PollStatus::ACTIVE,
            'type' => PollType::BASKET_COMPOSITION
        ]);

        $response = $this->getJson('/api/polls');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'type',
                        'status',
                        'starts_at',
                        'ends_at',
                        'options',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'current_page',
                'last_page',
                'per_page',
                'total'
            ]);

        expect($response->json('data'))->toHaveCount(3);
    });

    test('can get active polls only', function () {
        Poll::factory()->create(['status' => PollStatus::ACTIVE]);
        Poll::factory()->create(['status' => PollStatus::DRAFT]);
        Poll::factory()->create(['status' => PollStatus::COMPLETED]);

        $response = $this->getJson('/api/polls/active');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.status'))->toBe(PollStatus::ACTIVE->value);
    });

    test('can create a new poll', function () {
        $pollData = [
            'title' => 'Test Basket Composition Poll',
            'description' => 'Vote on new basket composition',
            'type' => PollType::BASKET_COMPOSITION->value,
            'options' => [
                'option_1' => 'Bitcoin 50%',
                'option_2' => 'Ethereum 30%',
                'option_3' => 'Cash 20%'
            ],
            'starts_at' => now()->addHour()->toISOString(),
            'ends_at' => now()->addDays(7)->toISOString(),
            'minimum_balance_required' => 1000,
            'metadata' => [
                'basket_code' => 'CRYPTO',
                'rebalance_threshold' => 0.05
            ]
        ];

        $response = $this->postJson('/api/polls', $pollData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'type',
                'status',
                'options',
                'starts_at',
                'ends_at',
                'minimum_balance_required',
                'metadata'
            ]);

        expect($response->json('title'))->toBe($pollData['title']);
        expect($response->json('type'))->toBe($pollData['type']);
        expect($response->json('status'))->toBe(PollStatus::DRAFT->value);
    });

    test('validates poll creation data', function () {
        $response = $this->postJson('/api/polls', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'type', 'options', 'ends_at']);
    });

    test('can get specific poll details', function () {
        $poll = Poll::factory()->create([
            'status' => PollStatus::ACTIVE,
            'type' => PollType::BASKET_COMPOSITION
        ]);

        $response = $this->getJson("/api/polls/{$poll->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'uuid',
                'title',
                'description',
                'type',
                'status',
                'options',
                'starts_at',
                'ends_at',
                'vote_count',
                'total_voting_power'
            ]);

        expect($response->json('uuid'))->toBe($poll->uuid);
    });

    test('returns 404 for non-existent poll', function () {
        $response = $this->getJson('/api/polls/non-existent-uuid');

        $response->assertStatus(404);
    });

    test('can activate a poll', function () {
        $poll = Poll::factory()->create([
            'status' => PollStatus::DRAFT,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(7)
        ]);

        $response = $this->postJson("/api/polls/{$poll->uuid}/activate");

        $response->assertStatus(200);
        expect($response->json('status'))->toBe(PollStatus::ACTIVE->value);
        
        $poll->refresh();
        expect($poll->status)->toBe(PollStatus::ACTIVE);
    });

    test('cannot activate already active poll', function () {
        $poll = Poll::factory()->create(['status' => PollStatus::ACTIVE]);

        $response = $this->postJson("/api/polls/{$poll->uuid}/activate");

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    });

    test('can vote on active poll', function () {
        $poll = Poll::factory()->create([
            'status' => PollStatus::ACTIVE,
            'type' => PollType::BASKET_COMPOSITION,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(7),
            'options' => [
                'option_1' => 'Bitcoin 50%',
                'option_2' => 'Ethereum 30%'
            ]
        ]);

        $voteData = [
            'selected_options' => ['option_1'],
            'comment' => 'I prefer Bitcoin allocation'
        ];

        $response = $this->postJson("/api/polls/{$poll->uuid}/vote", $voteData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'vote' => [
                    'id',
                    'poll_id',
                    'user_id',
                    'selected_options',
                    'voting_power',
                    'comment'
                ]
            ]);

        expect($response->json('vote.selected_options'))->toBe($voteData['selected_options']);
    });

    test('cannot vote twice on same poll', function () {
        $poll = Poll::factory()->create([
            'status' => PollStatus::ACTIVE,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(7)
        ]);

        // First vote
        $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => ['option_1']
        ]);

        // Second vote attempt
        $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => ['option_2']
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    });

    test('cannot vote on inactive poll', function () {
        $poll = Poll::factory()->create(['status' => PollStatus::DRAFT]);

        $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => ['option_1']
        ]);

        $response->assertStatus(422);
    });

    test('validates vote data', function () {
        $poll = Poll::factory()->create(['status' => PollStatus::ACTIVE]);

        $response = $this->postJson("/api/polls/{$poll->uuid}/vote", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['selected_options']);
    });

    test('can get poll results', function () {
        $poll = Poll::factory()->create([
            'status' => PollStatus::COMPLETED,
            'options' => [
                'option_1' => 'Bitcoin 50%',
                'option_2' => 'Ethereum 30%'
            ]
        ]);

        // Create some votes
        Vote::factory()->count(3)->create([
            'poll_id' => $poll->id,
            'selected_options' => json_encode(['option_1']),
            'voting_power' => 1000
        ]);

        Vote::factory()->count(2)->create([
            'poll_id' => $poll->id,
            'selected_options' => json_encode(['option_2']),
            'voting_power' => 500
        ]);

        $response = $this->getJson("/api/polls/{$poll->uuid}/results");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'poll' => ['id', 'title', 'status'],
                'results' => [
                    '*' => [
                        'option',
                        'votes',
                        'voting_power',
                        'percentage'
                    ]
                ],
                'total_votes',
                'total_voting_power',
                'winning_option'
            ]);

        expect($response->json('total_votes'))->toBe(5);
        expect($response->json('winning_option'))->toBe('option_1');
    });

    test('can get user voting power for poll', function () {
        $poll = Poll::factory()->create([
            'status' => PollStatus::ACTIVE,
            'minimum_balance_required' => 500
        ]);

        $response = $this->getJson("/api/polls/{$poll->uuid}/voting-power");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'voting_power',
                'eligible',
                'balance',
                'minimum_required'
            ]);

        expect($response->json('eligible'))->toBeTrue();
        expect($response->json('voting_power'))->toBeGreaterThan(0);
    });

    test('returns correct voting power based on balance', function () {
        $poll = Poll::factory()->create([
            'status' => PollStatus::ACTIVE,
            'minimum_balance_required' => 200000 // $2000, more than user's $1000 balance
        ]);

        $response = $this->getJson("/api/polls/{$poll->uuid}/voting-power");

        $response->assertStatus(200);
        expect($response->json('eligible'))->toBeFalse();
        expect($response->json('voting_power'))->toBe(0);
    });

    test('requires authentication for protected endpoints', function () {
        $poll = Poll::factory()->create();
        
        // Remove authentication
        $this->withoutMiddleware();
        $this->withHeaders(['Authorization' => 'Bearer invalid-token']);

        $protectedEndpoints = [
            ['POST', "/api/polls"],
            ['POST', "/api/polls/{$poll->uuid}/activate"],
            ['POST', "/api/polls/{$poll->uuid}/vote"],
            ['GET', "/api/polls/{$poll->uuid}/voting-power"]
        ];

        foreach ($protectedEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint, []);
            $response->assertStatus(401);
        }
    });

    test('handles poll type filtering', function () {
        Poll::factory()->create(['type' => PollType::BASKET_COMPOSITION]);
        Poll::factory()->create(['type' => PollType::GOVERNANCE_PARAMETER]);
        Poll::factory()->create(['type' => PollType::GENERAL]);

        $response = $this->getJson('/api/polls?type=' . PollType::BASKET_COMPOSITION->value);

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.type'))->toBe(PollType::BASKET_COMPOSITION->value);
    });

    test('handles poll status filtering', function () {
        Poll::factory()->create(['status' => PollStatus::ACTIVE]);
        Poll::factory()->create(['status' => PollStatus::DRAFT]);
        Poll::factory()->count(2)->create(['status' => PollStatus::COMPLETED]);

        $response = $this->getJson('/api/polls?status=' . PollStatus::COMPLETED->value);

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(2);
    });

    test('handles pagination correctly', function () {
        Poll::factory()->count(25)->create();

        $response = $this->getJson('/api/polls?per_page=10');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(10);
        expect($response->json('total'))->toBe(25);
        expect($response->json('last_page'))->toBe(3);
    });

    test('validates poll activation timing', function () {
        $poll = Poll::factory()->create([
            'status' => PollStatus::DRAFT,
            'starts_at' => now()->addDays(1), // Future start
            'ends_at' => now()->addDays(7)
        ]);

        $response = $this->postJson("/api/polls/{$poll->uuid}/activate");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Poll cannot be activated before its start time']);
    });

    test('handles poll end time validation', function () {
        $poll = Poll::factory()->create([
            'status' => PollStatus::ACTIVE,
            'starts_at' => now()->subDays(8),
            'ends_at' => now()->subDay() // Already ended
        ]);

        $response = $this->postJson("/api/polls/{$poll->uuid}/vote", [
            'selected_options' => ['option_1']
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Poll voting period has ended']);
    });

    test('can search polls by title', function () {
        Poll::factory()->create(['title' => 'Bitcoin Allocation Poll']);
        Poll::factory()->create(['title' => 'Ethereum Strategy Discussion']);
        Poll::factory()->create(['title' => 'General Governance Vote']);

        $response = $this->getJson('/api/polls?search=Bitcoin');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toContain('Bitcoin');
    });
});