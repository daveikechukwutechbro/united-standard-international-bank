# Feature Completeness v6.8–v6.12 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete 5 remaining phases of the production readiness roadmap, shipping as v6.8.0 through v6.12.0.

**Architecture:** Each phase is an independent release with its own feature branch and PR. Phases follow existing DDD patterns — services in `app/Domain/*/Services/`, GraphQL resolvers in `app/GraphQL/{Queries,Mutations}/`, tests in `tests/`. All code must pass PHPStan Level 8, php-cs-fixer, and Pest.

**Tech Stack:** PHP 8.4, Laravel 12, Lighthouse GraphQL, Pest, PHPStan Level 8, Sanctum auth

---

## Phase 1: v6.8.0 — Card Issuance Completion

### Task 1: Create missing CardIssuance GraphQL query resolvers

**Files:**
- Create: `app/GraphQL/Queries/CardIssuance/CardTransactionsQuery.php`
- Create: `app/GraphQL/Queries/CardIssuance/CardholdersQuery.php`
- Create: `app/GraphQL/Queries/CardIssuance/CardholderQuery.php`
- Test: `tests/Integration/GraphQL/CardIssuanceGraphQLTest.php` (modify existing)

- [ ] **Step 1: Create CardTransactionsQuery resolver**

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\CardIssuance;

use App\Domain\CardIssuance\Services\CardProvisioningService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CardTransactionsQuery
{
    public function __construct(
        private readonly CardProvisioningService $cardProvisioningService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $limit = $args['first'] ?? 20;
        $result = $this->cardProvisioningService->getTransactions($args['card_id'], $limit);

        return array_map(fn ($tx) => [
            'id'                => $tx->id,
            'card_id'           => $args['card_id'],
            'merchant_name'     => $tx->merchantName,
            'merchant_category' => $tx->merchantCategory,
            'amount_cents'      => $tx->amountCents,
            'currency'          => $tx->currency,
            'status'            => $tx->status,
            'transacted_at'     => $tx->transactedAt?->toDateTimeString(),
        ], $result['transactions']);
    }
}
```

- [ ] **Step 2: Create CardholdersQuery resolver**

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\CardIssuance;

use App\Domain\CardIssuance\Models\Cardholder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CardholdersQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $cardholders = Cardholder::where('user_id', $user->id)->get();

        return $cardholders->map(fn (Cardholder $ch) => [
            'id'          => $ch->id,
            'first_name'  => $ch->first_name,
            'last_name'   => $ch->last_name,
            'full_name'   => $ch->first_name . ' ' . $ch->last_name,
            'email'       => $ch->email,
            'kyc_status'  => $ch->kyc_status ?? 'pending',
            'is_verified' => $ch->verified_at !== null,
            'card_count'  => $ch->cards()->count(),
            'created_at'  => $ch->created_at?->toDateTimeString(),
        ])->all();
    }
}
```

- [ ] **Step 3: Create CardholderQuery resolver**

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\CardIssuance;

use App\Domain\CardIssuance\Models\Cardholder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CardholderQuery
{
    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>|null
     */
    public function __invoke(mixed $rootValue, array $args): ?array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $cardholder = Cardholder::where('id', $args['id'])
            ->where('user_id', $user->id)
            ->first();

        if ($cardholder === null) {
            return null;
        }

        return [
            'id'          => $cardholder->id,
            'first_name'  => $cardholder->first_name,
            'last_name'   => $cardholder->last_name,
            'full_name'   => $cardholder->first_name . ' ' . $cardholder->last_name,
            'email'       => $cardholder->email,
            'kyc_status'  => $cardholder->kyc_status ?? 'pending',
            'is_verified' => $cardholder->verified_at !== null,
            'card_count'  => $cardholder->cards()->count(),
            'created_at'  => $cardholder->created_at?->toDateTimeString(),
        ];
    }
}
```

- [ ] **Step 4: Run PHPStan and fix any type issues**

Run: `XDEBUG_MODE=off vendor/bin/phpstan analyse app/GraphQL/Queries/CardIssuance/ --memory-limit=2G`

- [ ] **Step 5: Add tests for new query resolvers**

Append to `tests/Integration/GraphQL/CardIssuanceGraphQLTest.php`:

```php
it('queries card transactions by card_id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/graphql', [
            'query' => '
                query ($cardId: String!) {
                    cardTransactions(card_id: $cardId, first: 10) {
                        id
                        card_id
                        merchant_name
                        amount_cents
                        currency
                        status
                    }
                }
            ',
            'variables' => ['cardId' => 'test-card-token'],
        ]);

    $response->assertOk();
    expect($response->json())->toHaveKey('data');
});

it('queries cardholders list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/graphql', [
            'query' => '{ cardholders { id first_name last_name kyc_status } }',
        ]);

    $response->assertOk();
    expect($response->json('data.cardholders'))->toBeArray();
});

it('queries single cardholder by id', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/graphql', [
            'query' => '
                query ($id: ID!) {
                    cardholder(id: $id) { id first_name last_name }
                }
            ',
            'variables' => ['id' => 'non-existent'],
        ]);

    $response->assertOk();
    expect($response->json('data.cardholder'))->toBeNull();
});
```

- [ ] **Step 6: Run tests**

Run: `./vendor/bin/pest tests/Integration/GraphQL/CardIssuanceGraphQLTest.php --stop-on-failure`

- [ ] **Step 7: Commit**

```bash
git add app/GraphQL/Queries/CardIssuance/{CardTransactionsQuery,CardholdersQuery,CardholderQuery}.php tests/Integration/GraphQL/CardIssuanceGraphQLTest.php
git commit -m "feat(card-issuance): add 3 missing GraphQL query resolvers"
```

---

### Task 2: Create missing CardIssuance GraphQL mutation resolvers

**Files:**
- Create: `app/GraphQL/Mutations/CardIssuance/CreateCardMutation.php`
- Create: `app/GraphQL/Mutations/CardIssuance/FreezeCardMutation.php`
- Create: `app/GraphQL/Mutations/CardIssuance/UnfreezeCardMutation.php`
- Create: `app/GraphQL/Mutations/CardIssuance/CancelCardMutation.php`
- Create: `app/GraphQL/Mutations/CardIssuance/CreateCardholderMutation.php`

- [ ] **Step 1: Create CreateCardMutation**

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\CardIssuance;

use App\Domain\CardIssuance\Enums\CardNetwork;
use App\Domain\CardIssuance\Services\CardProvisioningService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CreateCardMutation
{
    public function __construct(
        private readonly CardProvisioningService $cardProvisioningService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $network = isset($args['network']) ? CardNetwork::from($args['network']) : null;

        $card = $this->cardProvisioningService->createCard(
            userId: (string) $user->id,
            cardholderName: '', // Will be resolved from cardholder
            metadata: ['cardholder_id' => $args['cardholder_id']],
            network: $network,
            label: $args['label'] ?? null,
        );

        return [
            'id'              => $card->cardToken,
            'card_token'      => $card->cardToken,
            'cardholder_name' => $card->cardholderName,
            'last_four'       => $card->last4,
            'network'         => $card->network->value,
            'status'          => $card->status->value,
            'currency'        => $args['currency'] ?? 'USD',
            'label'           => $card->label ?? $args['label'] ?? null,
            'expires_at'      => $card->expiresAt->format('Y-m-d'),
            'created_at'      => now()->toDateTimeString(),
        ];
    }
}
```

- [ ] **Step 2: Create FreezeCardMutation**

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\CardIssuance;

use App\Domain\CardIssuance\Services\CardProvisioningService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class FreezeCardMutation
{
    public function __construct(
        private readonly CardProvisioningService $cardProvisioningService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $success = $this->cardProvisioningService->freezeCard($args['id']);

        if (! $success) {
            throw new RuntimeException('Failed to freeze card.');
        }

        $card = $this->cardProvisioningService->getCard($args['id']);

        return [
            'id'              => $args['id'],
            'card_token'      => $card?->cardToken ?? $args['id'],
            'cardholder_name' => $card?->cardholderName ?? '',
            'last_four'       => $card?->last4 ?? '',
            'network'         => $card?->network->value ?? '',
            'status'          => 'frozen',
            'expires_at'      => $card?->expiresAt?->format('Y-m-d'),
            'created_at'      => now()->toDateTimeString(),
        ];
    }
}
```

- [ ] **Step 3: Create UnfreezeCardMutation**

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\CardIssuance;

use App\Domain\CardIssuance\Services\CardProvisioningService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class UnfreezeCardMutation
{
    public function __construct(
        private readonly CardProvisioningService $cardProvisioningService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $success = $this->cardProvisioningService->unfreezeCard($args['id']);

        if (! $success) {
            throw new RuntimeException('Failed to unfreeze card.');
        }

        $card = $this->cardProvisioningService->getCard($args['id']);

        return [
            'id'              => $args['id'],
            'card_token'      => $card?->cardToken ?? $args['id'],
            'cardholder_name' => $card?->cardholderName ?? '',
            'last_four'       => $card?->last4 ?? '',
            'network'         => $card?->network->value ?? '',
            'status'          => 'active',
            'expires_at'      => $card?->expiresAt?->format('Y-m-d'),
            'created_at'      => now()->toDateTimeString(),
        ];
    }
}
```

- [ ] **Step 4: Create CancelCardMutation**

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\CardIssuance;

use App\Domain\CardIssuance\Services\CardProvisioningService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CancelCardMutation
{
    public function __construct(
        private readonly CardProvisioningService $cardProvisioningService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $card = $this->cardProvisioningService->getCard($args['id']);
        $success = $this->cardProvisioningService->cancelCard($args['id'], 'User requested cancellation');

        if (! $success) {
            throw new RuntimeException('Failed to cancel card.');
        }

        return [
            'id'              => $args['id'],
            'card_token'      => $card?->cardToken ?? $args['id'],
            'cardholder_name' => $card?->cardholderName ?? '',
            'last_four'       => $card?->last4 ?? '',
            'network'         => $card?->network->value ?? '',
            'status'          => 'cancelled',
            'expires_at'      => $card?->expiresAt?->format('Y-m-d'),
            'created_at'      => now()->toDateTimeString(),
        ];
    }
}
```

- [ ] **Step 5: Create CreateCardholderMutation**

```php
<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\CardIssuance;

use App\Domain\CardIssuance\Models\Cardholder;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CreateCardholderMutation
{
    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $cardholder = Cardholder::create([
            'user_id'                 => $user->id,
            'first_name'              => $args['first_name'],
            'last_name'               => $args['last_name'],
            'email'                   => $args['email'] ?? $user->email,
            'phone'                   => $args['phone'] ?? null,
            'kyc_status'              => 'pending',
            'shipping_address_line1'  => $args['shipping_address_line1'] ?? null,
            'shipping_city'           => $args['shipping_city'] ?? null,
            'shipping_country'        => $args['shipping_country'] ?? null,
        ]);

        return [
            'id'          => $cardholder->id,
            'first_name'  => $cardholder->first_name,
            'last_name'   => $cardholder->last_name,
            'full_name'   => $cardholder->first_name . ' ' . $cardholder->last_name,
            'email'       => $cardholder->email,
            'kyc_status'  => $cardholder->kyc_status,
            'is_verified' => false,
            'card_count'  => 0,
            'created_at'  => $cardholder->created_at?->toDateTimeString(),
        ];
    }
}
```

- [ ] **Step 6: Add GraphQL mutation tests**

Append to `tests/Integration/GraphQL/CardIssuanceGraphQLTest.php`:

```php
it('creates a card via mutation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/graphql', [
            'query' => '
                mutation ($input: CreateCardInput!) {
                    createCard(input: $input) { id status network }
                }
            ',
            'variables' => [
                'input' => ['cardholder_id' => 'ch-123', 'network' => 'VISA'],
            ],
        ]);

    $response->assertOk();
    expect($response->json())->toHaveKey('data.createCard');
});

it('freezes a card via mutation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/graphql', [
            'query' => 'mutation { freezeCard(id: "test-card") { id status } }',
        ]);

    $response->assertOk();
    expect($response->json())->toHaveKey('data');
});

it('unfreezes a card via mutation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/graphql', [
            'query' => 'mutation { unfreezeCard(id: "test-card") { id status } }',
        ]);

    $response->assertOk();
    expect($response->json())->toHaveKey('data');
});

it('cancels a card via mutation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/graphql', [
            'query' => 'mutation { cancelCard(id: "test-card") { id status } }',
        ]);

    $response->assertOk();
    expect($response->json())->toHaveKey('data');
});

it('creates a cardholder via mutation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/graphql', [
            'query' => '
                mutation ($input: CreateCardholderInput!) {
                    createCardholder(input: $input) { id first_name last_name kyc_status }
                }
            ',
            'variables' => [
                'input' => [
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'email' => 'jane@example.com',
                ],
            ],
        ]);

    $response->assertOk();
    expect($response->json())->not->toHaveKey('errors');
});
```

- [ ] **Step 7: Run tests and fix**

Run: `./vendor/bin/pest tests/Integration/GraphQL/CardIssuanceGraphQLTest.php --stop-on-failure`

- [ ] **Step 8: Commit**

```bash
git add app/GraphQL/Mutations/CardIssuance/ tests/Integration/GraphQL/CardIssuanceGraphQLTest.php
git commit -m "feat(card-issuance): add 5 missing GraphQL mutation resolvers"
```

---

### Task 3: Add SpendLimitEnforcementService to JIT funding

**Files:**
- Create: `app/Domain/CardIssuance/Services/SpendLimitEnforcementService.php`
- Modify: `app/Domain/CardIssuance/Services/JitFundingService.php`
- Create: `tests/Unit/Domain/CardIssuance/Services/SpendLimitEnforcementServiceTest.php`

- [ ] **Step 1: Create SpendLimitEnforcementService**

```php
<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\Services;

use App\Domain\CardIssuance\Models\Card;
use Illuminate\Support\Facades\Cache;

class SpendLimitEnforcementService
{
    private const CACHE_PREFIX = 'card_spend:';
    private const DAILY_TTL = 86400;
    private const MONTHLY_TTL = 2678400; // 31 days

    public function checkLimit(string $cardToken, float $amount): bool
    {
        $card = Card::where('issuer_card_token', $cardToken)->first();

        if ($card === null || $card->spend_limit_cents === null) {
            return true; // No limit configured
        }

        $limitCents = $card->spend_limit_cents;
        $interval = $card->spend_limit_interval ?? 'daily';
        $currentSpendCents = $this->getCurrentSpend($cardToken, $interval);
        $requestedCents = (int) round($amount * 100);

        return ($currentSpendCents + $requestedCents) <= $limitCents;
    }

    public function recordSpend(string $cardToken, float $amount): void
    {
        $amountCents = (int) round($amount * 100);

        $dailyKey = self::CACHE_PREFIX . "daily:{$cardToken}:" . date('Y-m-d');
        $monthlyKey = self::CACHE_PREFIX . "monthly:{$cardToken}:" . date('Y-m');

        /** @var int $dailyCurrent */
        $dailyCurrent = Cache::get($dailyKey, 0);
        Cache::put($dailyKey, $dailyCurrent + $amountCents, self::DAILY_TTL);

        /** @var int $monthlyCurrent */
        $monthlyCurrent = Cache::get($monthlyKey, 0);
        Cache::put($monthlyKey, $monthlyCurrent + $amountCents, self::MONTHLY_TTL);
    }

    private function getCurrentSpend(string $cardToken, string $interval): int
    {
        if ($interval === 'monthly') {
            $key = self::CACHE_PREFIX . "monthly:{$cardToken}:" . date('Y-m');
        } else {
            $key = self::CACHE_PREFIX . "daily:{$cardToken}:" . date('Y-m-d');
        }

        /** @var int $spend */
        $spend = Cache::get($key, 0);

        return $spend;
    }
}
```

- [ ] **Step 2: Integrate into JitFundingService**

Add spend limit check to `authorize()` method in `app/Domain/CardIssuance/Services/JitFundingService.php` after the balance check (after line 72):

```php
// Between "if ($balance < $requiredAmount)" and "// 3. Create hold on funds":

// 2b. Check spend limits
$spendLimitService = app(SpendLimitEnforcementService::class);
if (! $spendLimitService->checkLimit($request->cardToken, $requiredAmount)) {
    return $this->decline($request, AuthorizationDecision::DECLINED_LIMIT_EXCEEDED);
}
```

And after the approval (after line 108, before the return):

```php
// Record the spend after approval
$spendLimitService = app(SpendLimitEnforcementService::class);
$spendLimitService->recordSpend($request->cardToken, $requiredAmount);
```

- [ ] **Step 3: Write tests for SpendLimitEnforcementService**

```php
<?php

declare(strict_types=1);

use App\Domain\CardIssuance\Models\Card;
use App\Domain\CardIssuance\Services\SpendLimitEnforcementService;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    config(['cache.default' => 'array']);
});

it('allows spend within daily limit', function () {
    Card::factory()->create([
        'issuer_card_token'    => 'card-123',
        'spend_limit_cents'    => 100000, // $1000
        'spend_limit_interval' => 'daily',
    ]);

    $service = new SpendLimitEnforcementService();
    expect($service->checkLimit('card-123', 500.00))->toBeTrue();
});

it('rejects spend exceeding daily limit', function () {
    Card::factory()->create([
        'issuer_card_token'    => 'card-456',
        'spend_limit_cents'    => 10000, // $100
        'spend_limit_interval' => 'daily',
    ]);

    $service = new SpendLimitEnforcementService();
    $service->recordSpend('card-456', 80.00);
    expect($service->checkLimit('card-456', 30.00))->toBeFalse();
});

it('allows spend when no limit configured', function () {
    $service = new SpendLimitEnforcementService();
    expect($service->checkLimit('no-card', 9999.00))->toBeTrue();
});

it('tracks monthly spend separately from daily', function () {
    Card::factory()->create([
        'issuer_card_token'    => 'card-789',
        'spend_limit_cents'    => 500000, // $5000 monthly
        'spend_limit_interval' => 'monthly',
    ]);

    $service = new SpendLimitEnforcementService();
    $service->recordSpend('card-789', 2000.00);
    expect($service->checkLimit('card-789', 2500.00))->toBeTrue();
    $service->recordSpend('card-789', 2500.00);
    expect($service->checkLimit('card-789', 600.00))->toBeFalse();
});
```

- [ ] **Step 4: Run tests**

Run: `./vendor/bin/pest tests/Unit/Domain/CardIssuance/Services/SpendLimitEnforcementServiceTest.php --stop-on-failure`

- [ ] **Step 5: Run PHPStan**

Run: `XDEBUG_MODE=off vendor/bin/phpstan analyse app/Domain/CardIssuance/Services/ --memory-limit=2G`

- [ ] **Step 6: Commit**

```bash
git add app/Domain/CardIssuance/Services/SpendLimitEnforcementService.php app/Domain/CardIssuance/Services/JitFundingService.php tests/Unit/Domain/CardIssuance/Services/SpendLimitEnforcementServiceTest.php
git commit -m "feat(card-issuance): add spend limit enforcement to JIT funding"
```

---

### Task 4: Code quality, version bump, and PR for v6.8.0

- [ ] **Step 1: Run full quality pipeline**

```bash
./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php
XDEBUG_MODE=off vendor/bin/phpstan analyse --memory-limit=2G
./vendor/bin/pest --parallel --stop-on-failure
```

- [ ] **Step 2: Update VERSION_ROADMAP.md** — Add v6.8.0 entry after v6.7.0

- [ ] **Step 3: Update feature page version badge** — Update the card issuance section in features index

- [ ] **Step 4: Create PR for v6.8.0**

---

## Phase 2: v6.9.0 — Banking Integration Hardening

### Task 5: Create BankingController with core REST endpoints

**Files:**
- Create: `app/Http/Controllers/Api/Banking/BankingController.php`
- Modify: `app/Domain/Banking/Routes/api.php`
- Create: `tests/Feature/Api/Banking/BankingControllerTest.php`

- [ ] **Step 1: Create BankingController**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Banking;

use App\Domain\Banking\Services\BankIntegrationService;
use App\Domain\Banking\Services\BankTransferService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankingController extends Controller
{
    public function __construct(
        private readonly BankIntegrationService $bankService,
        private readonly BankTransferService $transferService,
    ) {
    }

    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bank_code'   => 'required|string|max:50',
            'credentials' => 'required|array',
        ]);

        $connection = $this->bankService->connectUserToBank(
            (string) $request->user()?->id,
            $validated['bank_code'],
            $validated['credentials']
        );

        return response()->json(['data' => $connection], 201);
    }

    public function disconnect(string $connectionId): JsonResponse
    {
        $this->bankService->disconnectUserFromBank($connectionId);

        return response()->json(['message' => 'Disconnected']);
    }

    public function connections(Request $request): JsonResponse
    {
        $connections = $this->bankService->getUserBankConnections(
            (string) $request->user()?->id
        );

        return response()->json(['data' => $connections]);
    }

    public function accounts(Request $request): JsonResponse
    {
        $accounts = $this->bankService->getUserBankAccounts(
            (string) $request->user()?->id
        );

        return response()->json(['data' => $accounts]);
    }

    public function syncAccounts(string $connectionId): JsonResponse
    {
        $synced = $this->bankService->syncBankAccounts($connectionId);

        return response()->json(['data' => $synced]);
    }

    public function initiateTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_account_id' => 'required|string',
            'to_account_id'   => 'required|string',
            'amount'          => 'required|numeric|min:0.01',
            'currency'        => 'required|string|size:3',
            'reference'       => 'nullable|string|max:255',
        ]);

        $transfer = $this->transferService->initiate($validated);

        return response()->json(['data' => $transfer], 201);
    }

    public function transferStatus(string $transferId): JsonResponse
    {
        $status = $this->transferService->getStatus($transferId);

        return response()->json(['data' => $status]);
    }

    public function bankHealth(string $bankCode): JsonResponse
    {
        $health = $this->bankService->checkBankHealth($bankCode);

        return response()->json(['data' => $health]);
    }
}
```

- [ ] **Step 2: Add routes to Banking routes file**

Add to `app/Domain/Banking/Routes/api.php` within the existing `auth:sanctum` group:

```php
// Core Banking Endpoints
Route::prefix('v2/banks')->group(function () {
    Route::post('/connect', [BankingController::class, 'connect']);
    Route::delete('/disconnect/{connectionId}', [BankingController::class, 'disconnect']);
    Route::get('/connections', [BankingController::class, 'connections']);
    Route::get('/accounts', [BankingController::class, 'accounts']);
    Route::post('/accounts/sync/{connectionId}', [BankingController::class, 'syncAccounts']);
    Route::post('/transfer', [BankingController::class, 'initiateTransfer']);
    Route::get('/transfer/{id}/status', [BankingController::class, 'transferStatus']);
    Route::get('/health/{bankCode}', [BankingController::class, 'bankHealth']);
});
```

- [ ] **Step 3: Write controller tests**

Create `tests/Feature/Api/Banking/BankingControllerTest.php` with tests for each endpoint (connect, disconnect, connections, accounts, sync, transfer, status, health). Use `Sanctum::actingAs($user, ['read', 'write', 'delete'])`.

- [ ] **Step 4: Run tests and commit**

---

### Task 6: Create AccountVerificationController

**Files:**
- Create: `app/Http/Controllers/Api/Banking/AccountVerificationController.php`
- Modify: `app/Domain/Banking/Routes/api.php`
- Create: `tests/Feature/Api/Banking/AccountVerificationControllerTest.php`

- [ ] **Step 1: Create controller with 3 endpoints**: initiateMicroDeposit, confirmMicroDeposit, instantVerify
- [ ] **Step 2: Add routes**
- [ ] **Step 3: Write tests**
- [ ] **Step 4: Run tests and commit**

---

### Task 7: Create BankWebhookController

**Files:**
- Create: `app/Http/Controllers/Api/Banking/BankWebhookController.php`
- Modify: `app/Domain/Banking/Routes/api.php`
- Create: `tests/Feature/Api/Banking/BankWebhookControllerTest.php`

- [ ] **Step 1: Create webhook controller** with HMAC signature verification, transfer-update and account-update handlers
- [ ] **Step 2: Add webhook routes** (outside auth middleware, with rate limiting)
- [ ] **Step 3: Write tests**
- [ ] **Step 4: Run tests and commit**

---

### Task 8: Fix GraphQL AggregatedBalanceQuery and add missing operations

**Files:**
- Modify: `app/GraphQL/Queries/Banking/AggregatedBalanceQuery.php` (fix 0.0 return)
- Create: `app/GraphQL/Queries/Banking/BankTransfersQuery.php`
- Create: `app/GraphQL/Mutations/Banking/CancelTransferMutation.php`
- Modify: `graphql/banking.graphql`

- [ ] **Step 1: Fix AggregatedBalanceQuery** to use BankIntegrationService::getAggregatedBalance()
- [ ] **Step 2: Add BankTransfersQuery resolver**
- [ ] **Step 3: Add CancelTransferMutation resolver**
- [ ] **Step 4: Update schema with new operations**
- [ ] **Step 5: Write tests and commit**

---

### Task 9: Add BankIntegrationService and transfer tests

**Files:**
- Create: `tests/Unit/Domain/Banking/Services/BankIntegrationServiceTest.php`

- [ ] **Step 1: Write comprehensive tests** for registerConnector, connectUserToBank, syncBankAccounts, getAggregatedBalance
- [ ] **Step 2: Run tests, fix issues, commit**

---

### Task 10: Code quality, version bump, and PR for v6.9.0

- [ ] **Step 1: Run full quality pipeline**
- [ ] **Step 2: Update VERSION_ROADMAP.md with v6.9.0 entry**
- [ ] **Step 3: Create PR**

---

## Phase 3: v6.10.0 — Multi-Tenancy Hardening

### Task 11: Create tenant_audit_logs migration and model

**Files:**
- Create: `database/migrations/YYYY_MM_DD_000001_create_tenant_audit_logs_table.php`
- Create: `app/Models/TenantAuditLog.php`
- Create: `tests/Unit/Models/TenantAuditLogTest.php`

- [ ] **Step 1: Create migration** with columns: id, tenant_id, user_id (nullable), action (enum: created, suspended, reactivated, deleted, plan_changed, config_updated), before_data (JSON nullable), after_data (JSON nullable), ip_address, user_agent, created_at
- [ ] **Step 2: Create TenantAuditLog model**
- [ ] **Step 3: Write model test**
- [ ] **Step 4: Commit**

---

### Task 12: Create TenantAuditService and integrate with provisioning

**Files:**
- Create: `app/Services/MultiTenancy/TenantAuditService.php`
- Modify: `app/Services/MultiTenancy/TenantProvisioningService.php`
- Create: `tests/Unit/Services/MultiTenancy/TenantAuditServiceTest.php`

- [ ] **Step 1: Create TenantAuditService** that logs to tenant_audit_logs table
- [ ] **Step 2: Integrate** into TenantProvisioningService's createTenant, suspendTenant, reactivateTenant, deleteTenant, setTenantPlan, updateTenantConfig methods
- [ ] **Step 3: Write tests**
- [ ] **Step 4: Commit**

---

### Task 13: Create EnforceTenantPlanLimits middleware

**Files:**
- Create: `app/Http/Middleware/EnforceTenantPlanLimits.php`
- Create: `tests/Unit/MultiTenancy/EnforceTenantPlanLimitsTest.php`

- [ ] **Step 1: Create middleware** that checks TenantUsageMeteringService limits, returns 429 when exceeded
- [ ] **Step 2: Write tests**
- [ ] **Step 3: Commit**

---

### Task 14: Implement soft-delete for tenants

**Files:**
- Create: `database/migrations/YYYY_MM_DD_000001_add_soft_delete_to_tenants.php`
- Modify: `app/Services/MultiTenancy/TenantProvisioningService.php`
- Modify: `app/Models/Tenant.php`
- Create: `tests/Unit/Services/MultiTenancy/TenantSoftDeleteTest.php`

- [ ] **Step 1: Create migration** adding deleted_at and deletion_scheduled_at columns
- [ ] **Step 2: Update deleteTenant()** to schedule deletion (14-day grace) instead of immediate delete
- [ ] **Step 3: Add restoreTenant()** method to cancel scheduled deletion
- [ ] **Step 4: Add purgeTenant()** method for actual deletion after grace period
- [ ] **Step 5: Write tests**
- [ ] **Step 6: Commit**

---

### Task 15: Add functional cross-tenant data isolation tests

**Files:**
- Create: `tests/Integration/MultiTenancy/FunctionalCrossTenantIsolationTest.php`

- [ ] **Step 1: Write integration tests** that create 2 tenants, insert data in Tenant A, verify Tenant B sees nothing. Test cache isolation, model scoping, and GraphQL directive blocking.
- [ ] **Step 2: Run tests, commit**

---

### Task 16: Harden TenantDataMigrationService

**Files:**
- Modify: `app/Services/MultiTenancy/TenantDataMigrationService.php`

- [ ] **Step 1: Add table name whitelist** — only allow migration of explicitly listed tables, reject any dynamic/user-provided table names
- [ ] **Step 2: Commit**

---

### Task 17: Code quality, version bump, and PR for v6.10.0

- [ ] **Step 1: Run full quality pipeline**
- [ ] **Step 2: Update VERSION_ROADMAP.md with v6.10.0 entry**
- [ ] **Step 3: Create PR**

---

## Phase 4: v6.11.0 — CrossChain/DeFi Production Adapters

### Task 18: Create EthRpcClient service

**Files:**
- Create: `app/Infrastructure/Web3/EthRpcClient.php`
- Create: `app/Infrastructure/Web3/AbiEncoder.php`
- Create: `tests/Unit/Infrastructure/Web3/EthRpcClientTest.php`
- Create: `tests/Unit/Infrastructure/Web3/AbiEncoderTest.php`

- [ ] **Step 1: Create AbiEncoder** — Encode/decode Solidity function calls using ABI specs. Support: address, uint256, bytes32, bytes, bool, string, arrays. Use PHP bcmath for 256-bit integers. Methods: `encodeFunctionCall(string $signature, array $params): string`, `decodeResponse(string $data, array $types): array`.

- [ ] **Step 2: Create EthRpcClient** — JSON-RPC client for Ethereum-compatible chains. Methods: `ethCall(string $to, string $data, string $chain): string`, `getTransactionReceipt(string $txHash, string $chain): ?array`. Uses Laravel Http client, configurable RPC URLs per chain, retry with backoff, circuit breaker via Cache.

- [ ] **Step 3: Write unit tests** — Test ABI encoding/decoding for all supported types. Test RPC client with mocked HTTP responses.
- [ ] **Step 4: Commit**

---

### Task 19: Complete Wormhole production mode

**Files:**
- Modify: `app/Domain/CrossChain/Services/Adapters/WormholeBridgeAdapter.php`
- Create: `tests/Unit/Domain/CrossChain/Services/Adapters/WormholeProductionTest.php`

- [ ] **Step 1: Add production initiateBridge()** — Encode TokenBridge.transferTokens() call via AbiEncoder, submit via EthRpcClient, return real tx_hash
- [ ] **Step 2: Add production getBridgeStatus()** — Poll Guardian RPC for VAA, check destination chain for completion
- [ ] **Step 3: Write tests** with mocked RPC responses
- [ ] **Step 4: Commit**

---

### Task 20: Complete Circle CCTP production mode

**Files:**
- Modify: `app/Domain/CrossChain/Services/Adapters/CircleCctpBridgeAdapter.php`
- Create: `tests/Unit/Domain/CrossChain/Services/Adapters/CircleCctpProductionTest.php`

- [ ] **Step 1: Add production initiateBridge()** — Encode TokenMessenger.depositForBurn(), validate USDC-only
- [ ] **Step 2: Add attestation polling** — GET Circle attestation service, parse attestation + message
- [ ] **Step 3: Add production completion** — Encode MessageTransmitter.receiveMessage() on destination
- [ ] **Step 4: Write tests and commit**

---

### Task 21: Complete Uniswap V3 production mode

**Files:**
- Modify: `app/Domain/DeFi/Services/Connectors/UniswapV3Connector.php`
- Create: `tests/Unit/Domain/DeFi/Services/Connectors/UniswapV3ProductionTest.php`

- [ ] **Step 1: Fix getOnChainQuote()** — Proper ABI encoding for Quoter2.quoteExactInputSingle()
- [ ] **Step 2: Fix executeSwapViaRpc()** — Proper ABI encoding for SwapRouter02.exactInputSingle() with slippage: amountOutMinimum = outputAmount * (1 - slippage)
- [ ] **Step 3: Write tests and commit**

---

### Task 22: Complete Aave V3 production mode

**Files:**
- Modify: `app/Domain/DeFi/Services/Connectors/AaveV3Connector.php`
- Create: `tests/Unit/Domain/DeFi/Services/Connectors/AaveV3ProductionTest.php`

- [ ] **Step 1: Fix getUserPositions()** — ABI decode UiPoolDataProvider.getUserReservesData() response
- [ ] **Step 2: Add production supply/borrow/repay/withdraw** — ABI encode Pool contract calls
- [ ] **Step 3: Write tests and commit**

---

### Task 23: Code quality, version bump, and PR for v6.11.0

- [ ] **Step 1: Run full quality pipeline**
- [ ] **Step 2: Update VERSION_ROADMAP.md with v6.11.0 entry**
- [ ] **Step 3: Create PR**

---

## Phase 5: v6.12.0 — Privacy ZK Production Prover

### Task 24: Create Circom circuit source files

**Files:**
- Create: `storage/app/circuits/age_check.circom`
- Create: `storage/app/circuits/residency_check.circom`
- Create: `storage/app/circuits/kyc_tier_check.circom`
- Create: `storage/app/circuits/sanctions_check.circom`
- Create: `storage/app/circuits/income_range_check.circom`

- [ ] **Step 1: Create age_check.circom** — Private inputs: birthYear, birthMonth, birthDay. Public inputs: currentYear, minAge. Constraint: currentYear - birthYear >= minAge (with month/day check).

- [ ] **Step 2: Create residency_check.circom** — Private inputs: regionCode. Public inputs: allowedRegionsMerkleRoot. Constraint: Merkle proof that regionCode is in the allowed set.

- [ ] **Step 3: Create kyc_tier_check.circom** — Private inputs: kycTier. Public inputs: minimumTier. Constraint: kycTier >= minimumTier.

- [ ] **Step 4: Create sanctions_check.circom** — Private inputs: identityHash. Public inputs: sanctionsListMerkleRoot. Constraint: Merkle exclusion proof (identity NOT in sanctions list).

- [ ] **Step 5: Create income_range_check.circom** — Private inputs: income. Public inputs: lowerBound, upperBound. Constraint: lowerBound <= income <= upperBound.

- [ ] **Step 6: Commit**

---

### Task 25: Create TrustedSetupService

**Files:**
- Create: `app/Domain/Privacy/Services/TrustedSetupService.php`
- Create: `app/Console/Commands/ZkSetupCommand.php`
- Create: `tests/Unit/Domain/Privacy/Services/TrustedSetupServiceTest.php`

- [ ] **Step 1: Create TrustedSetupService** with methods:
  - `downloadPowersOfTau(int $power): string` — Downloads .ptau from trusted source
  - `setupCircuit(string $circuitName): array` — Runs snarkjs groth16 setup, returns paths to zkey + vkey
  - `exportVerificationKey(string $circuitName): string` — Extracts vkey.json
  - `exportSolidityVerifier(string $circuitName): string` — Generates Verifier.sol
  - `getCircuitArtifacts(string $circuitName): array` — Returns paths to all artifacts

- [ ] **Step 2: Create ZkSetupCommand** — `php artisan zk:setup --circuit=age_check` that orchestrates the full ceremony

- [ ] **Step 3: Write tests** (mocking snarkjs binary calls)
- [ ] **Step 4: Commit**

---

### Task 26: Create CircuitCompilationService

**Files:**
- Create: `app/Domain/Privacy/Services/CircuitCompilationService.php`
- Create: `tests/Unit/Domain/Privacy/Services/CircuitCompilationServiceTest.php`

- [ ] **Step 1: Create CircuitCompilationService** — Wraps `circom` binary to compile .circom to .r1cs + .wasm. Methods: `compile(string $circuitName): array`, `getConstraintCount(string $circuitName): int`, `artifactsExist(string $circuitName): bool`.

- [ ] **Step 2: Write tests** (mock circom binary)
- [ ] **Step 3: Commit**

---

### Task 27: Enhance SnarkjsProverService with artifact verification

**Files:**
- Modify: `app/Domain/Privacy/Services/SnarkjsProverService.php`
- Create: `tests/Unit/Domain/Privacy/Services/SnarkjsArtifactVerificationTest.php`

- [ ] **Step 1: Add artifact verification** — Before generating a proof, verify that .zkey and .wasm files exist for the requested circuit. Throw `CircuitNotFoundException` if missing.
- [ ] **Step 2: Add constraint count reporting** — Log constraint count and proving time
- [ ] **Step 3: Write tests and commit**

---

### Task 28: Generate Solidity verifier contracts

**Files:**
- Create: `storage/app/circuits/verifiers/AgeCheckVerifier.sol`
- Create: `storage/app/circuits/verifiers/ResidencyCheckVerifier.sol`
- Create: `storage/app/circuits/verifiers/KycTierCheckVerifier.sol`
- Create: `storage/app/circuits/verifiers/SanctionsCheckVerifier.sol`
- Create: `storage/app/circuits/verifiers/IncomeRangeCheckVerifier.sol`

- [ ] **Step 1: Generate verifier templates** — Standard Groth16 Solidity verifier with proof type-specific naming and BN254 curve parameters. Each verifier implements `verifyProof(uint[2] a, uint[2][2] b, uint[2] c, uint[] input) returns (bool)`.

- [ ] **Step 2: Update config/privacy.php** — Add verifier contract addresses per proof type per chain
- [ ] **Step 3: Commit**

---

### Task 29: Add proof roundtrip integration test

**Files:**
- Create: `tests/Integration/Privacy/ZkProofRoundtripTest.php`

- [ ] **Step 1: Write integration test** — If snarkjs is available, test full roundtrip: generate proof with DemoZkProver, verify locally, test OnChainVerifierService encoding. Skip if snarkjs not installed.
- [ ] **Step 2: Commit**

---

### Task 30: Code quality, version bump, and PR for v6.12.0

- [ ] **Step 1: Run full quality pipeline**
- [ ] **Step 2: Update VERSION_ROADMAP.md with v6.12.0 entry**
- [ ] **Step 3: Create PR**
