# Feature Completeness & Production Readiness — Design Spec

**Date**: 2026-03-27
**Versions**: v6.8.0 through v6.12.0
**Goal**: Close the gap between advertised features and implementation across 5 domains

---

## Release Plan

| Version | Phase | Domain | Focus |
|---------|-------|--------|-------|
| v6.8.0  | 2 | Card Issuance | Rain adapter, missing GraphQL resolvers, spend controls |
| v6.9.0  | 3 | Banking | REST controllers, test coverage, webhooks, GraphQL completion |
| v6.10.0 | 4 | Multi-Tenancy | Audit log, plan enforcement, soft-delete, functional isolation tests |
| v6.11.0 | 5 | CrossChain/DeFi | Production contract integration for Wormhole, CCTP, Uniswap, Aave |
| v6.12.0 | 6 | Privacy/ZK | Circom circuits, trusted setup, circuit artifacts, on-chain verifiers |

---

## v6.8.0 — Card Issuance Completion

### Scope

Complete the Rain card issuer adapter to production quality. Fill all 8 missing GraphQL resolvers. Add spend limit enforcement to JIT funding. Add missing tests.

### Deliverables

1. **RainCardIssuerAdapter** — Complete all 9 interface methods (createCard, freezeCard, unfreezeCard, cancelCard, getCard, listUserCards, getTransactions, getProvisioningData, getName) with proper HTTP client calls to Rain API endpoints, response mapping, error handling, and production environment guards.

2. **GraphQL Resolvers** (8 missing):
   - `CardTransactionsQuery` — paginated transaction list by card_id
   - `CardholdersQuery` — list cardholders for user
   - `CardholderQuery` — single cardholder by ID
   - `CreateCardMutation` — create card via CardIssuerInterface
   - `FreezeCardMutation` — freeze card
   - `UnfreezeCardMutation` — unfreeze card
   - `CancelCardMutation` — cancel card with status validation
   - `CreateCardholderMutation` — create cardholder with validation

3. **SpendLimitEnforcementService** — Checks daily/monthly spend totals against card limits before authorization. Integrated into JitFundingService. Uses cache for running totals with daily/monthly reset.

4. **Tests**: Unit tests for Rain adapter, GraphQL integration tests for all new resolvers, spend limit enforcement tests.

### Architecture

Follow existing patterns: resolvers in `app/GraphQL/{Queries,Mutations}/CardIssuance/`, services in `app/Domain/CardIssuance/Services/`, tests in `tests/Unit/Domain/CardIssuance/` and `tests/Integration/GraphQL/`.

---

## v6.9.0 — Banking Integration Hardening

### Scope

Add REST API controllers for core banking operations. Complete GraphQL gaps. Add webhook endpoints for bank notifications. Expand test coverage from 40% to 70%+.

### Deliverables

1. **BankingController** — REST endpoints:
   - `POST /api/v2/banks/connect` — connect user to bank
   - `DELETE /api/v2/banks/disconnect/{connectionId}` — disconnect
   - `GET /api/v2/banks/connections` — list user connections
   - `GET /api/v2/banks/accounts` — list user bank accounts
   - `POST /api/v2/banks/accounts/sync/{connectionId}` — trigger sync
   - `POST /api/v2/banks/transfer` — initiate transfer
   - `GET /api/v2/banks/transfer/{id}/status` — transfer status
   - `GET /api/v2/banks/health/{bankCode}` — bank health check

2. **AccountVerificationController** — REST endpoints:
   - `POST /api/v2/banks/verify/micro-deposit/initiate` — start micro-deposit
   - `POST /api/v2/banks/verify/micro-deposit/confirm` — confirm amounts
   - `POST /api/v2/banks/verify/instant` — Open Banking instant verify

3. **BankWebhookController** — Webhook receiver:
   - `POST /webhooks/bank/{provider}/transfer-update` — transfer status updates
   - `POST /webhooks/bank/{provider}/account-update` — account changes
   - HMAC signature verification middleware

4. **GraphQL Completion**:
   - Fix `AggregatedBalanceQuery` (currently returns 0.0)
   - Add `bankTransfers` query
   - Add `bankTransferStatus` query
   - Add `cancelTransfer` mutation
   - Add `verifyAccount` mutation

5. **Tests**: BankIntegrationServiceTest, AccountVerificationServiceTest, OpenBankingConnectorTest, BankTransferServiceTest, webhook handler tests.

### Architecture

Controllers in `app/Http/Controllers/Api/Banking/`, routes in `app/Domain/Banking/Routes/api.php`. Follow existing route group structure with sanctum auth + rate limiting.

---

## v6.10.0 — Multi-Tenancy Hardening

### Scope

Add functional cross-tenant data isolation tests. Create persistent audit log. Build plan enforcement middleware. Implement soft-delete with grace period. Harden data migration security.

### Deliverables

1. **tenant_audit_logs migration + TenantAuditLog model** — Persistent audit trail for sensitive operations (create, suspend, delete, plan change, config update). Fields: id, tenant_id, user_id, action, before_data, after_data, ip_address, user_agent, created_at.

2. **TenantAuditService** — Logs all TenantProvisioningService operations to audit table. Replaces/supplements current Log:: calls.

3. **EnforceTenantPlanLimits middleware** — Checks API call count, storage usage, and user count against plan limits on every tenant-scoped request. Returns 429 when limits exceeded.

4. **Soft-delete for tenants** — Add `deleted_at` + `deletion_scheduled_at` columns. `deleteTenant()` now schedules deletion (14-day grace). `restoreTenant()` cancels scheduled deletion. `purgeTenant()` performs actual deletion after grace period (drops tenant database).

5. **Functional cross-tenant isolation tests** — Integration tests that:
   - Create 2 tenants with real databases
   - Insert data in Tenant A
   - Switch to Tenant B and verify zero data leakage
   - Test cache key isolation
   - Test event sourcing isolation
   - Test GraphQL directive blocking

6. **TenantDataMigrationService hardening** — Whitelist table names explicitly, add prepared statement enforcement for dynamic queries.

### Architecture

Middleware in `app/Http/Middleware/`, audit service in `app/Services/MultiTenancy/`, tests in `tests/Integration/MultiTenancy/`.

---

## v6.11.0 — CrossChain/DeFi Production Adapters

### Scope

Complete production-grade contract interaction for Wormhole, Circle CCTP, Uniswap V3, and Aave V3. Add Web3 integration layer. Implement slippage protection.

### Deliverables

1. **EthRpcClient** — Shared Web3 RPC client service:
   - `eth_call` for read operations
   - ABI encoding/decoding for Solidity function calls
   - Multi-chain RPC URL configuration
   - Retry with exponential backoff
   - Circuit breaker for failing RPCs

2. **WormholeBridgeAdapter production mode**:
   - Token Bridge `transferTokens()` via eth_call
   - Guardian RPC VAA polling with retry
   - `completeTransfer()` on destination chain
   - Real fee estimation from Guardian network

3. **CircleCctpBridgeAdapter production mode**:
   - `TokenMessenger.depositForBurn()` encoding
   - Attestation Service polling (GET /attestations/{messageHash})
   - `MessageTransmitter.receiveMessage()` on destination
   - USDC-only validation

4. **UniswapV3Connector production mode**:
   - `Quoter2.quoteExactInputSingle()` ABI encoding
   - `SwapRouter02.exactInputSingle()` for execution
   - Slippage protection: `amountOutMinimum` = quote * (1 - slippage%)
   - Fee tier auto-selection based on token pair

5. **AaveV3Connector production mode**:
   - `UiPoolDataProvider.getUserReservesData()` for position reading
   - `Pool.supply()` / `Pool.borrow()` / `Pool.repay()` / `Pool.withdraw()` encoding
   - Health factor monitoring with liquidation threshold alerts
   - Flash loan: `Pool.flashLoanSimple()` encoding

6. **Tests**: Production adapter tests with mocked RPC responses, ABI encoding/decoding tests, slippage calculation tests.

### Architecture

EthRpcClient in `app/Infrastructure/Web3/`, adapter production methods in existing adapter files. Follow existing pattern of demo-mode fallback when config not set.

---

## v6.12.0 — Privacy ZK Production Prover

### Scope

Create Circom circuit source files for all 5 proof types. Implement trusted setup ceremony tooling. Generate circuit artifacts (zkey, vkey, wasm). Deploy Solidity verifier contracts. Complete the production proving pipeline.

### Deliverables

1. **Circom circuit sources** (5 circuits in `storage/app/circuits/`):
   - `age_check.circom` — Prove age >= threshold without revealing birthdate
   - `residency_check.circom` — Prove region membership without address
   - `kyc_tier_check.circom` — Prove KYC level without documents
   - `sanctions_check.circom` — Prove not on sanctions list via Merkle exclusion
   - `income_range_check.circom` — Prove income in range without exact amount

2. **TrustedSetupService** — Manages ceremony artifacts:
   - Download/verify Powers of Tau (.ptau) files
   - Per-circuit zkey generation via `snarkjs groth16 setup`
   - Verification key extraction via `snarkjs zkey export verificationkey`
   - Solidity verifier generation via `snarkjs zkey export solidityverifier`
   - Artisan command: `php artisan zk:setup --circuit=age_check`

3. **CircuitCompilationService** — Compiles Circom to artifacts:
   - `circom` binary wrapper (compile .circom to .r1cs + .wasm)
   - Constraint count validation
   - Artifact versioning

4. **Solidity verifier contracts** — Generated per circuit:
   - `AgeCheckVerifier.sol`, `ResidencyCheckVerifier.sol`, etc.
   - Multi-chain deployment addresses in config
   - Proxy upgrade pattern support

5. **SnarkjsProverService enhancements**:
   - Verify circuit artifacts exist before proving
   - Report constraint count and proving time metrics
   - Memory limit enforcement

6. **Tests**: Circuit compilation tests (circom installed check), trusted setup integration test, proof generation + verification roundtrip test, on-chain verification test with mocked RPC.

### Architecture

Circuits in `storage/app/circuits/`, services in `app/Domain/Privacy/Services/`, commands in `app/Console/Commands/`. Follow existing SnarkJS integration pattern.

---

## Cross-Cutting Concerns

- **PHPStan Level 8**: All new code must pass static analysis
- **Code Style**: php-cs-fixer with project config
- **Tests**: Pest with parallel execution, Sanctum actingAs with abilities
- **Feature Pages**: Update version badge + features index for each release
- **Roadmap**: Update VERSION_ROADMAP.md after each release
