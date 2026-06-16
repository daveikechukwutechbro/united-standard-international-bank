# Alchemy Solana Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Consolidate Solana monitoring from Helius to Alchemy — one provider, one dashboard, one API key for all chains.

**Architecture:** Extend the existing AlchemyWebhookController to handle Solana activity (it already maps `sol-mainnet` → `solana` but currently filters for ERC-20 only). Create AlchemyWebhookSyncService mirroring HeliusWebhookSyncService but using Alchemy's PATCH API. Update the observer, config, and CLI commands. Keep Helius code intact but gated behind a config flag for rollback safety.

**Tech Stack:** PHP 8.4 / Laravel 12, Alchemy Notify API (PATCH `dashboard.alchemy.com/api/update-webhook-addresses`), HMAC-SHA256 webhook auth, HeliusTransactionProcessor (reused — Alchemy Solana payload maps cleanly to same structure)

---

## File Structure

| Action | File | Responsibility |
|--------|------|---------------|
| Create | `app/Domain/Wallet/Services/AlchemyWebhookSyncService.php` | Address registration/removal via Alchemy Notify API |
| Modify | `app/Http/Controllers/Api/Webhook/AlchemyWebhookController.php` | Add Solana activity processing alongside EVM |
| Modify | `app/Domain/Wallet/Observers/BlockchainAddressObserver.php` | Dispatch to Alchemy instead of Helius (config-gated) |
| Modify | `app/Console/Commands/HeliusSyncCommand.php` | Rename to `SolanaAddressSyncCommand`, support both providers |
| Modify | `config/services.php` | Add `alchemy.solana_webhook_id` and `alchemy.notify_token` |
| Modify | `config/relayer.php` | Add `ALCHEMY_WEBHOOK_SIGNING_KEY_SOLANA` |
| Modify | `.env.example` | Add new Alchemy Solana env vars |
| Modify | `.env.production.example` | Same |
| Create | `tests/Unit/Domain/Wallet/Services/AlchemyWebhookSyncServiceTest.php` | Unit tests for address sync |
| Create | `tests/Unit/Http/Controllers/Api/Webhook/AlchemyWebhookSolanaTest.php` | Webhook processing tests for Solana payloads |

---

### Task 1: Create AlchemyWebhookSyncService

**Files:**
- Create: `app/Domain/Wallet/Services/AlchemyWebhookSyncService.php`
- Test: `tests/Unit/Domain/Wallet/Services/AlchemyWebhookSyncServiceTest.php`

- [ ] **Step 1: Write the test file**

```php
<?php

declare(strict_types=1);

use App\Domain\Wallet\Services\AlchemyWebhookSyncService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'services.alchemy.notify_token' => 'test-token',
        'services.alchemy.solana_webhook_id' => 'wh_test123',
    ]);
});

it('adds an address via Alchemy PATCH API', function (): void {
    Http::fake(['dashboard.alchemy.com/*' => Http::response([], 200)]);

    $service = new AlchemyWebhookSyncService();
    $result = $service->addAddress('TestSolanaAddr123');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && str_contains($request->url(), 'update-webhook-addresses')
            && $request->header('X-Alchemy-Token')[0] === 'test-token'
            && $request['webhook_id'] === 'wh_test123'
            && in_array('TestSolanaAddr123', $request['addresses_to_add'], true)
            && $request['addresses_to_remove'] === [];
    });
});

it('removes an address via Alchemy PATCH API', function (): void {
    Http::fake(['dashboard.alchemy.com/*' => Http::response([], 200)]);

    $service = new AlchemyWebhookSyncService();
    $result = $service->removeAddress('TestSolanaAddr123');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request['addresses_to_remove'] === ['TestSolanaAddr123']
            && $request['addresses_to_add'] === [];
    });
});

it('rejects reserved Solana addresses', function (): void {
    Http::fake();

    $service = new AlchemyWebhookSyncService();
    $result = $service->addAddress('11111111111111111111111111111111');

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

it('returns false when not configured', function (): void {
    config(['services.alchemy.notify_token' => '']);
    Http::fake();

    $service = new AlchemyWebhookSyncService();
    $result = $service->addAddress('SomeAddr');

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

it('syncs all addresses in one PATCH call', function (): void {
    Http::fake(['dashboard.alchemy.com/*' => Http::response([], 200)]);

    // Need blockchain_addresses table for this test
    \Illuminate\Support\Facades\Schema::create('blockchain_addresses', function ($table): void {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('user_uuid')->index();
        $table->string('chain');
        $table->string('address');
        $table->text('public_key');
        $table->string('derivation_path')->nullable();
        $table->string('label')->nullable();
        $table->boolean('is_active')->default(true);
        $table->json('metadata')->nullable();
        $table->timestamps();
        $table->unique(['chain', 'address']);
    });

    \App\Domain\Account\Models\BlockchainAddress::create([
        'user_uuid' => 'u1', 'chain' => 'solana', 'address' => 'Addr1', 'public_key' => 'Addr1',
    ]);
    \App\Domain\Account\Models\BlockchainAddress::create([
        'user_uuid' => 'u2', 'chain' => 'solana', 'address' => 'Addr2', 'public_key' => 'Addr2',
    ]);

    $service = new AlchemyWebhookSyncService();
    $count = $service->syncAllAddresses();

    expect($count)->toBe(2);

    Http::assertSent(function ($request) {
        return count($request['addresses_to_add']) === 2;
    });

    \Illuminate\Support\Facades\Schema::dropIfExists('blockchain_addresses');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/Unit/Domain/Wallet/Services/AlchemyWebhookSyncServiceTest.php -v`
Expected: FAIL — class not found

- [ ] **Step 3: Create the service**

```php
<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Services;

use App\Domain\Account\Models\BlockchainAddress;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Syncs Solana user addresses with Alchemy Address Activity webhook.
 *
 * Uses Alchemy Notify API to add/remove addresses from a Solana webhook.
 * API: PATCH https://dashboard.alchemy.com/api/update-webhook-addresses
 * Auth: X-Alchemy-Token header with the Notify API auth token.
 */
class AlchemyWebhookSyncService
{
    private const API_URL = 'https://dashboard.alchemy.com/api/update-webhook-addresses';

    /** @var array<string> Solana system addresses that must never be monitored */
    private const RESERVED_ADDRESSES = [
        '11111111111111111111111111111111',
        '11111111111111111111111111111112',
        'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA',
        'ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL',
        'SysvarC1ock11111111111111111111111111111111',
    ];

    public function addAddress(string $address): bool
    {
        if ($this->isReservedAddress($address)) {
            Log::warning('Alchemy Solana: Rejected reserved address', ['address' => $address]);

            return false;
        }

        return $this->patchAddresses([$address], []);
    }

    public function removeAddress(string $address): bool
    {
        return $this->patchAddresses([], [$address]);
    }

    /**
     * Sync all active Solana addresses from DB to Alchemy webhook.
     */
    public function syncAllAddresses(): int
    {
        $token = $this->getNotifyToken();
        $webhookId = $this->getWebhookId();

        if ($token === '' || $webhookId === '') {
            Log::warning('Alchemy Solana: Cannot sync — not configured');

            return 0;
        }

        $addresses = BlockchainAddress::where('chain', 'solana')
            ->where('is_active', true)
            ->pluck('address')
            ->unique()
            ->reject(fn (string $addr): bool => $this->isReservedAddress($addr))
            ->values()
            ->all();

        if (empty($addresses)) {
            return 0;
        }

        $success = $this->patchAddresses($addresses, []);

        if ($success) {
            Log::info('Alchemy Solana: Synced all addresses', ['count' => count($addresses)]);
        }

        return $success ? count($addresses) : 0;
    }

    /**
     * @param array<string> $toAdd
     * @param array<string> $toRemove
     */
    private function patchAddresses(array $toAdd, array $toRemove): bool
    {
        $token = $this->getNotifyToken();
        $webhookId = $this->getWebhookId();

        if ($token === '' || $webhookId === '') {
            Log::debug('Alchemy Solana: Webhook sync skipped — not configured');

            return false;
        }

        $response = Http::timeout(15)
            ->withHeaders(['X-Alchemy-Token' => $token])
            ->patch(self::API_URL, [
                'webhook_id'         => $webhookId,
                'addresses_to_add'   => $toAdd,
                'addresses_to_remove' => $toRemove,
            ]);

        if (! $response->successful()) {
            Log::error('Alchemy Solana: Failed to update webhook addresses', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    private function isReservedAddress(string $address): bool
    {
        return in_array($address, self::RESERVED_ADDRESSES, true);
    }

    private function getNotifyToken(): string
    {
        return (string) config('services.alchemy.notify_token', '');
    }

    private function getWebhookId(): string
    {
        return (string) config('services.alchemy.solana_webhook_id', '');
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/Unit/Domain/Wallet/Services/AlchemyWebhookSyncServiceTest.php -v`
Expected: 5 passed

- [ ] **Step 5: PHPStan + code style**

Run: `./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php app/Domain/Wallet/Services/AlchemyWebhookSyncService.php tests/Unit/Domain/Wallet/Services/AlchemyWebhookSyncServiceTest.php && XDEBUG_MODE=off vendor/bin/phpstan analyse --memory-limit=2G app/Domain/Wallet/Services/AlchemyWebhookSyncService.php`

- [ ] **Step 6: Commit**

```bash
git add app/Domain/Wallet/Services/AlchemyWebhookSyncService.php tests/Unit/Domain/Wallet/Services/AlchemyWebhookSyncServiceTest.php
git commit -m "feat: add AlchemyWebhookSyncService for Solana address management"
```

---

### Task 2: Add Config and Env Vars

**Files:**
- Modify: `config/services.php`
- Modify: `config/relayer.php`
- Modify: `.env.example`
- Modify: `.env.production.example`
- Modify: `.env.zelta.example`

- [ ] **Step 1: Add Alchemy Solana config to `config/services.php`**

Add inside the existing `'alchemy'` array (or create it if it doesn't exist):

```php
'alchemy' => [
    'notify_token'       => env('ALCHEMY_NOTIFY_TOKEN'),
    'solana_webhook_id'  => env('ALCHEMY_SOLANA_WEBHOOK_ID'),
],
```

- [ ] **Step 2: Add Solana signing key to `config/relayer.php`**

In the `alchemy_webhook_signing_keys` array, add:

```php
env('ALCHEMY_WEBHOOK_SIGNING_KEY_SOLANA'),
```

- [ ] **Step 3: Add env vars to `.env.example`, `.env.production.example`, `.env.zelta.example`**

```
ALCHEMY_NOTIFY_TOKEN=
ALCHEMY_SOLANA_WEBHOOK_ID=
ALCHEMY_WEBHOOK_SIGNING_KEY_SOLANA=
```

- [ ] **Step 4: Commit**

```bash
git add config/services.php config/relayer.php .env.example .env.production.example .env.zelta.example
git commit -m "feat: add Alchemy Solana webhook config and env vars"
```

---

### Task 3: Extend AlchemyWebhookController for Solana

**Files:**
- Modify: `app/Http/Controllers/Api/Webhook/AlchemyWebhookController.php`
- Create: `tests/Unit/Http/Controllers/Api/Webhook/AlchemyWebhookSolanaTest.php`

- [ ] **Step 1: Write the Solana webhook test**

Create `tests/Unit/Http/Controllers/Api/Webhook/AlchemyWebhookSolanaTest.php` with tests for:
- Incoming USDC transfer on Solana creates BlockchainTransaction + ActivityFeedItem
- Native SOL transfer is processed correctly
- Direction detection (incoming vs outgoing)
- Deduplication across token + native transfers
- Unknown Solana address is ignored

The tests should construct Alchemy-format payloads with `network: 'sol-mainnet'` and `category: 'token'` for SPL transfers, `category: 'external'` for native SOL.

- [ ] **Step 2: Run tests to verify they fail**

- [ ] **Step 3: Modify AlchemyWebhookController**

Key changes to `handle()`:
1. After resolving the network, check if it's `'solana'`
2. If Solana: delegate to a new `processSolanaActivities()` method that:
   - Iterates activities (accepts `token` AND `external` categories for Solana)
   - Converts Alchemy payload to Helius-compatible format for `HeliusTransactionProcessor`
   - Calls `$this->processor->processTransaction()` for persistence
   - Sends FCM push notifications via `PushNotificationService`
   - Broadcasts `WalletBalanceUpdated`
   - Invalidates Solana balance caches
3. If not Solana: existing EVM processing (unchanged)

Add constructor injection for `HeliusTransactionProcessor` and `PushNotificationService`.

The Alchemy→Helius payload adapter maps:
```
Alchemy fromAddress → Helius fromUserAccount
Alchemy toAddress → Helius toUserAccount  
Alchemy value → Helius tokenAmount (for SPL) or amount in lamports (for native)
Alchemy hash → Helius signature
Alchemy asset → mint lookup for resolveToken
Alchemy rawContract.address → Helius mint
```

- [ ] **Step 4: Run tests to verify they pass**

- [ ] **Step 5: PHPStan + code style**

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/Webhook/AlchemyWebhookController.php tests/Unit/Http/Controllers/Api/Webhook/AlchemyWebhookSolanaTest.php
git commit -m "feat: extend AlchemyWebhookController to process Solana transactions"
```

---

### Task 4: Update BlockchainAddressObserver

**Files:**
- Modify: `app/Domain/Wallet/Observers/BlockchainAddressObserver.php`

- [ ] **Step 1: Update observer to support both providers**

The observer should check a config flag to decide which provider to use:

```php
// config('services.solana_webhook_provider', 'helius') — 'helius' or 'alchemy'
```

When `'alchemy'`: inject and use `AlchemyWebhookSyncService`
When `'helius'`: use existing `HeliusWebhookSyncService` (default, backward compatible)

- [ ] **Step 2: Run existing tests**

Run: `./vendor/bin/pest tests/Unit --parallel --stop-on-failure`

- [ ] **Step 3: Commit**

```bash
git add app/Domain/Wallet/Observers/BlockchainAddressObserver.php
git commit -m "feat: observer supports Alchemy or Helius for Solana address sync"
```

---

### Task 5: Update CLI Commands

**Files:**
- Modify: `app/Console/Commands/HeliusSyncCommand.php`

- [ ] **Step 1: Update HeliusSyncCommand to support both providers**

Add `--provider` option (`helius` or `alchemy`, default from config).
When `alchemy`: resolve `AlchemyWebhookSyncService` and call `syncAllAddresses()`.
When `helius`: existing behavior.

Rename the command signature from `helius:sync` to `solana:sync` (keep `helius:sync` as hidden alias for backward compat).

- [ ] **Step 2: Run PHPStan + tests**

- [ ] **Step 3: Commit**

```bash
git add app/Console/Commands/HeliusSyncCommand.php
git commit -m "feat: solana:sync command supports --provider alchemy|helius"
```

---

### Task 6: Update SolanaConnector RPC URL

**Files:**
- Modify: `config/blockchain.php`

- [ ] **Step 1: Update default RPC URL config**

The `SOLANA_RPC_URL` env var already exists. User just needs to set it to Alchemy:
```
SOLANA_RPC_URL=https://solana-mainnet.g.alchemy.com/v2/{ALCHEMY_API_KEY}
```

Document this in the .env.example files alongside the existing var.

- [ ] **Step 2: Commit**

```bash
git add config/blockchain.php .env.example .env.production.example
git commit -m "docs: document Alchemy Solana RPC URL in env examples"
```

---

### Task 7: Integration Test & Final Verification

- [ ] **Step 1: Run full quality suite**

```bash
./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php
XDEBUG_MODE=off vendor/bin/phpstan analyse --memory-limit=2G
./vendor/bin/pest --parallel --stop-on-failure
```

- [ ] **Step 2: Verify backward compatibility**

With `ALCHEMY_SOLANA_WEBHOOK_ID` unset, confirm:
- `solana:sync` falls back to Helius
- Observer falls back to Helius
- Helius webhook endpoint still works

- [ ] **Step 3: Create PR**

```bash
git push -u origin feature/alchemy-solana-migration
gh pr create --title "feat: migrate Solana monitoring from Helius to Alchemy"
```

---

## Migration Runbook (Production)

After merge:

```bash
# 1. Set env vars
ALCHEMY_NOTIFY_TOKEN=<from Alchemy dashboard>
ALCHEMY_SOLANA_WEBHOOK_ID=<create in Alchemy dashboard>
ALCHEMY_WEBHOOK_SIGNING_KEY_SOLANA=<from webhook creation>
SOLANA_RPC_URL=https://solana-mainnet.g.alchemy.com/v2/<ALCHEMY_API_KEY>

# 2. Switch provider
SOLANA_WEBHOOK_PROVIDER=alchemy

# 3. Deploy + sync
php artisan config:cache
php artisan solana:sync --provider=alchemy

# 4. Verify webhooks arriving from Alchemy
tail -f storage/logs/laravel.log | grep "Alchemy"

# 5. After 24h verification, disable Helius
# Remove HELIUS_* env vars (code stays for rollback)
```
