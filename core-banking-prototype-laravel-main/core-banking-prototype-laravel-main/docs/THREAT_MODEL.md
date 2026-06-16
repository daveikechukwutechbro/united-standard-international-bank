# Threat Model — FinAegis/Zelta v7.9.0

**Methodology**: STRIDE (Spoofing, Tampering, Repudiation, Information Disclosure, Denial of Service, Elevation of Privilege)
**Prepared**: 2026-03-28
**Scope**: Top 5 critical data flows

---

## 1. Payment Authorization (JIT Funding)

**Flow**: Card network sends authorization request -> `JitFundingService` -> balance check -> spend limit check -> hold creation -> approve/decline response. Latency budget: 2000ms.

**Entry points**: Card issuer webhook endpoint (Marqeta/Rain adapter), internal API calls.

### Spoofing
- **Risk**: Attacker impersonates card issuer by sending fake authorization requests.
- **Mitigation**: `ValidateWebhookSignature` middleware validates Marqeta webhooks via Basic Auth + optional HMAC-SHA256. Stripe-style timestamp tolerance (5 minutes) prevents delayed replays. All validation uses `hash_equals()` for constant-time comparison.
- **Gap**: Marqeta HMAC signature is optional (`if ($secret !== null && $secret !== '' && $signature !== null)`). If HMAC secret is not configured, Basic Auth alone protects the endpoint. Recommend making HMAC mandatory in production.

### Tampering
- **Risk**: Modification of authorization amount between balance check and hold creation. Amount field manipulation in transit.
- **Mitigation**: `AuthorizationRequest` is a value object with immutable properties. Amount is passed through as `getAmountDecimal()`. Card token is validated against issuer records.
- **Gap**: Balance check and hold creation are not wrapped in a database transaction with `lockForUpdate()`. Under concurrent requests for the same card/user, a TOCTOU race condition could approve two authorizations against the same balance. This is the highest-severity finding in this flow.

### Repudiation
- **Risk**: Disputed transactions without audit trail.
- **Mitigation**: All authorization decisions (approved/declined) dispatch domain events (`AuthorizationApproved`, `AuthorizationDeclined`). Structured logging captures authorization_id, amount, merchant, latency. Event sourcing provides immutable audit trail.
- **Gap**: None identified. Event sourcing and structured logging provide comprehensive non-repudiation.

### Information Disclosure
- **Risk**: Leakage of card tokens, user balances, or hold IDs in logs or error responses.
- **Mitigation**: Log entries include authorization_id and hold_id but not raw card numbers. Error responses return generic decline reasons without internal state. `JitFundingService` catches exceptions and returns 0.0 balance on failure (fail-closed).
- **Gap**: `card.metadata['user_id']` is accessed without null-safe operator — if metadata is malformed, the empty string fallback propagates silently. Not a direct disclosure risk but could mask issues.

### Denial of Service
- **Risk**: Flood of authorization requests exhausting balance lookup or hold creation capacity, causing legitimate transactions to time out beyond 2000ms budget.
- **Mitigation**: `ApiRateLimitMiddleware` on webhook routes. Card issuer IP allowlisting can be configured via `IpBlocking` middleware. Metrics tracking via `MetricsService` enables alerting on latency spikes.
- **Gap**: No per-card-token rate limiting. A compromised card could generate unlimited authorization requests. Consider implementing card-level throttling.

### Elevation of Privilege
- **Risk**: Bypassing spend limits or using another user's balance for authorization.
- **Mitigation**: `SpendLimitEnforcementService.checkLimit()` validates per-card-token limits before approval. Balance is looked up by `user_uuid` from the card's metadata, not from the request payload.
- **Gap**: The `user_id` used for balance lookup comes from `$card->metadata['user_id']`. If card metadata can be tampered at the issuer level, an attacker could reference another user's balance. This trust boundary should be documented.

---

## 2. Cross-Tenant Isolation

**Flow**: Authenticated user -> `InitializeTenancyByTeam` middleware -> team membership verification -> tenant context initialization -> tenant-scoped data access throughout request lifecycle. Terminated after response.

**Entry points**: All API routes behind `auth:sanctum` + `tenant` middleware, GraphQL endpoint, WebSocket channels.

### Spoofing
- **Risk**: User spoofs team membership to access another tenant's data.
- **Mitigation**: `InitializeTenancyByTeam` explicitly verifies team ownership (`ownsTeam()`) and membership (`belongsToTeam()`) before initializing tenant context. Uses Laravel Jetstream's relationship checks backed by `team_user` pivot table.
- **Gap**: The middleware allows pass-through for unauthenticated users (`if (!$user) return $next($request)`). Routes behind this middleware must also require `auth:sanctum` — if middleware ordering is incorrect, unauthenticated requests could bypass tenant checks.

### Tampering
- **Risk**: Manipulating the `currentTeam` relationship to switch tenant context mid-session.
- **Mitigation**: `currentTeam` is resolved from the user's `current_team_id` column, which requires an API call to switch. The middleware runs on every request, re-verifying membership. Tenant context is terminated in `terminate()` after each response.
- **Gap**: If `current_team_id` can be mass-assigned on the User model, an attacker could update it via profile update endpoints. Verify that `current_team_id` is not in `$fillable` or is protected by Jetstream's team switching logic.

### Repudiation
- **Risk**: Cross-tenant actions performed without attribution.
- **Mitigation**: `logTenancyEvent()` logs all tenancy events with user_id, team_id, IP, user_agent, and full URL. Unauthorized access attempts are logged at WARNING level. Rate-limited events are also logged.
- **Gap**: None identified. Comprehensive audit logging is in place.

### Information Disclosure
- **Risk**: Data from one tenant leaking into another tenant's queries due to missing tenant scope.
- **Mitigation**: Stancl/Tenancy provides database-level isolation (separate databases or scoped queries). `UsesTenantConnection` trait forces models to use tenant-scoped database connections.
- **Gap**: All 56 domain modules must correctly use tenant-scoped connections. Any model that accidentally uses the central database connection could leak cross-tenant data. This should be verified systematically during the audit — query all domain models for `UsesTenantConnection` trait usage versus direct `Model` extension.

### Denial of Service
- **Risk**: Excessive tenant lookup requests causing database pressure or cache exhaustion.
- **Mitigation**: Per-user rate limiting: 60 attempts per minute on tenant lookups with `RateLimiter::tooManyAttempts()`. Returns 429 when exceeded.
- **Gap**: The rate limit key is `tenant_lookup:{user_id}`. This is per-user, not per-IP, so it requires authentication first. Unauthenticated flood attempts would not be rate-limited by this middleware (but would be caught by `IpBlocking` in the API middleware group).

### Elevation of Privilege
- **Risk**: Regular user accessing admin-only features within a tenant, or team member accessing data beyond their team role.
- **Mitigation**: Admin endpoints use `require.2fa.admin` middleware. Team roles are enforced by Jetstream. The tenant middleware only checks membership, not role — role enforcement happens at the controller/policy level.
- **Gap**: The `$allowWithoutTenant` static flag defaults to `false` but is publicly writable. If any code sets this to `true`, the security check is globally bypassed for all subsequent requests in that process. This should be made private or enforced via config.

---

## 3. Cross-Chain Bridge Initiation

**Flow**: User requests bridge quote -> `BridgeOrchestratorService` queries adapters (Wormhole, Circle CCTP) -> user selects quote -> `initiateBridge()` validates quote expiry -> adapter executes bridge -> `BridgeTransactionInitiated` event -> `BridgeTransactionTracker` monitors completion.

**Entry points**: REST API cross-chain endpoints, GraphQL crosschain schema.

### Spoofing
- **Risk**: Attacker submits bridge request with spoofed sender/recipient addresses.
- **Mitigation**: `auth:sanctum` required on all cross-chain endpoints. Sender address should be verified against user's registered wallet addresses.
- **Gap**: `initiateBridge()` accepts `senderAddress` and `recipientAddress` as plain strings. There is no on-chain ownership verification that the `senderAddress` belongs to the authenticated user. An attacker could initiate a bridge from someone else's address if they know the address (which are public on-chain). The adapter layer must enforce ownership.

### Tampering
- **Risk**: Quote manipulation between quote retrieval and bridge initiation — changing amount, fee, or destination chain.
- **Mitigation**: `BridgeQuote` is a value object. Quote expiry is checked via `$quote->isExpired()` before initiation. Quote ID ties the execution to the original quote parameters.
- **Gap**: The quote object is reconstructed from the client's request, not fetched from a server-side cache by quote ID. If the client sends a modified quote object, the adapter might accept tampered parameters. Quotes should be stored server-side and referenced by ID only.

### Repudiation
- **Risk**: Disputed bridge transactions (funds stuck in limbo between chains).
- **Mitigation**: `BridgeTransactionInitiated`, `BridgeTransactionCompleted`, and `BridgeTransactionFailed` events are dispatched. Transaction ID is logged with full details (provider, chains, token, amount, addresses). `BridgeTransactionTracker` monitors status.
- **Gap**: None identified for logging. However, the recovery process for failed bridges (funds locked on source chain but not released on destination) should be documented as a manual procedure for the security team.

### Information Disclosure
- **Risk**: Leakage of private keys, bridge adapter credentials, or internal RPC endpoints.
- **Mitigation**: Adapter credentials stored in config (not in code). Logs include transaction details but not private keys. `EthRpcClient` uses circuit breaker pattern, which does not leak RPC URLs in error responses.
- **Gap**: Bridge quote responses may include internal adapter details (provider names, fee structures) that could aid an attacker in selecting more favorable manipulation vectors.

### Denial of Service
- **Risk**: Flooding the bridge quote endpoint to exhaust adapter rate limits, causing legitimate bridge requests to fail.
- **Mitigation**: `ApiRateLimitMiddleware` on API routes. Each adapter is queried in sequence with try-catch (failed adapters are skipped).
- **Gap**: No per-user bridge operation throttling. An attacker could exhaust Wormhole/CCTP API rate limits for all users by requesting many quotes. Consider per-user bridge operation rate limiting.

### Elevation of Privilege
- **Risk**: Unauthorized bridge initiation or manipulation of bridge parameters to redirect funds.
- **Mitigation**: Authentication required. Bridge amount comes from the quote, which was computed by the adapter.
- **Gap**: No separate authorization check verifying the user has permission to bridge the requested amount. Spend limits apply to cards but not to bridge operations. Consider implementing bridge-specific value limits.

---

## 4. ZK Proof Generation and Verification

**Flow**: Client submits private/public inputs -> `SnarkjsProverService` resolves circuit -> writes inputs to temp file -> runs snarkjs CLI as subprocess -> reads proof/public signals -> verifies proof -> returns `ZkProof` value object. Also: `OnChainVerifierService` for Solidity verification, `ZkKycService` for privacy-preserving KYC.

**Entry points**: Privacy API endpoints, GraphQL privacy schema, internal service calls.

### Spoofing
- **Risk**: Attacker submits proof generation request impersonating another user to generate proofs for their data.
- **Mitigation**: `auth:sanctum` on privacy endpoints. Private inputs should be derived from authenticated user's data, not from request payload.
- **Gap**: The `generateProof()` method accepts `$privateInputs` and `$publicInputs` as generic arrays. If private inputs are passed directly from the HTTP request, an attacker could forge proofs for arbitrary data. Verify that private inputs are server-derived, not client-supplied.

### Tampering
- **Risk**: Manipulation of circuit files or trusted setup parameters (toxic waste) to create a backdoor allowing proof forgery.
- **Mitigation**: `TrustedSetupService` manages ceremony. Circuit files stored in `storage/app/circuits/`. `validateCircuitFiles()` checks file existence. `SrsManifestService` tracks circuit manifest integrity.
- **Gap**: Circuit files on the filesystem could be tampered if storage is compromised. Consider adding hash verification of circuit files (zkey, wasm, vkey) against a known-good manifest before each proof generation. The current `validateCircuitFiles()` checks existence only, not integrity.

### Repudiation
- **Risk**: Denial of having generated a specific proof, or claiming a valid proof was not accepted.
- **Mitigation**: Proof generation is logged with circuit name, constraint count, and timing. `ZkProof` value objects include proof ID, type, and metadata.
- **Gap**: Proofs should be stored with cryptographic binding to the generating user. Currently, the proof object does not include a user identifier or timestamp signature.

### Information Disclosure
- **Risk**: Leakage of private inputs (the entire purpose of ZK proofs is to keep these secret).
- **Mitigation**: Private inputs are written to temporary JSON files, used for proof generation, then presumably cleaned up. Proof of Innocence service uses `random_bytes()` for nonces.
- **Gap**: Temporary files containing private inputs (`input.json`) must be securely deleted after proof generation. Verify that `SnarkjsProverService` cleans up temp files in a `finally` block. If the snarkjs process crashes, temp files with private inputs may persist on disk. Use `tmpfile()` or secure deletion.

### Denial of Service
- **Risk**: Proof generation is CPU-intensive (snarkjs runs as subprocess with 120-second timeout). An attacker could exhaust server resources by requesting many proofs simultaneously.
- **Mitigation**: Configurable timeout via `privacy.zk.snarkjs_timeout_seconds` (default 120s). Symfony Process is used for subprocess management.
- **Gap**: No concurrency limit on proof generation. Each proof spawns a Node.js subprocess. Without a semaphore or queue-based throttling, parallel requests could exhaust CPU/memory. Consider routing proof generation through a dedicated queue with bounded workers.

### Elevation of Privilege
- **Risk**: Using proof generation to execute arbitrary commands via the snarkjs CLI.
- **Mitigation**: Circuit names are resolved through `$circuitMapping` config, not from user input. The snarkjs binary path is config-driven.
- **Gap**: If `$circuitMapping` or `$circuitDirectory` contain user-influenced values, path traversal could lead to arbitrary file read/write via the snarkjs process. Verify that circuit resolution is strictly config-based with no user input in file paths.

---

## 5. Webhook Processing (Outbound and Inbound)

**Flow (Outbound)**: Domain event -> `WebhookService::dispatch()` -> find active webhooks -> create `WebhookDelivery` record -> queue `ProcessWebhookDelivery` job -> HTTP POST with HMAC signature -> retry on failure (exponential backoff: 1m, 5m, 15m) -> mark delivered/failed.

**Flow (Inbound)**: External service sends webhook -> `ValidateWebhookSignature` middleware -> provider-specific validation (Stripe, Coinbase, Paysera, Santander, Open Banking, Marqeta) -> controller processes payload.

**Entry points**: Inbound webhook routes (6+ providers), outbound delivery to customer-configured URLs.

### Spoofing
- **Risk (Inbound)**: Attacker sends fake webhooks impersonating Stripe/Coinbase/etc. to trigger unauthorized actions (fake payment confirmations, fake card events).
- **Mitigation**: Provider-specific signature validation. Stripe: timestamp + HMAC-SHA256 with `v1` signature. Coinbase: HMAC-SHA256 on payload. Santander: timestamp + HMAC-SHA512. Marqeta: Basic Auth + optional HMAC. All use `hash_equals()`.
- **Gap**: Open Banking webhook validation uses session-based `state` parameter comparison (`session('openbanking_state')`). This is fragile — sessions may expire between redirect and callback, and session fixation attacks could bypass this. Recommend switching to a database-persisted state with expiry.

- **Risk (Outbound)**: Customer configures webhook URL pointing to internal services (SSRF via webhook delivery).
- **Mitigation**: `Http::withHeaders()->timeout($webhook->timeout_seconds)->post($webhook->url, ...)` — standard Laravel HTTP client.
- **Gap**: No URL validation against internal/private IP ranges. A customer could set `webhook.url` to `http://169.254.169.254/` (AWS metadata), `http://localhost:6379/` (Redis), or internal service endpoints. This is a critical SSRF vector. Implement URL allowlisting or block private IP ranges.

### Tampering
- **Risk (Inbound)**: Modification of webhook payload in transit to change payment amounts or transaction status.
- **Mitigation**: HMAC signatures cover the entire payload body. Timestamp tolerance (5 minutes) prevents replay of old payloads. Signatures are validated before any business logic processes the payload.
- **Gap**: Coinbase and Paysera signatures do not include a timestamp component — they sign only the payload. A captured valid signature+payload pair can be replayed indefinitely. Implement per-provider replay protection using delivery ID tracking.

- **Risk (Outbound)**: Man-in-the-middle modification of outbound webhook delivery.
- **Mitigation**: `X-Webhook-Signature` header generated via `WebhookService::generateSignature()` using the webhook's secret. Customers can verify signatures on their end.
- **Gap**: No enforcement that webhook URLs use HTTPS. Outbound webhooks over HTTP would expose payload and signature in transit. Enforce HTTPS-only webhook URLs.

### Repudiation
- **Risk**: Disputes over whether a webhook was delivered or what it contained.
- **Mitigation**: `WebhookDelivery` model records event_type, payload, status, status_code, response_body, response_headers, duration_ms, and attempt_number. Delivery lifecycle: pending -> delivered/failed. Structured logging at INFO/ERROR level.
- **Gap**: None identified. The delivery record system provides comprehensive non-repudiation.

### Information Disclosure
- **Risk (Outbound)**: Webhook payloads may contain sensitive financial data sent to customer-controlled URLs over insecure channels.
- **Mitigation**: Webhook secret enables customers to verify authenticity. `User-Agent` header identifies the service.
- **Gap**: Payload construction in `WebhookService::dispatch()` uses `array_merge($payload, ...)` without filtering sensitive fields. If upstream services include PII, card numbers, or internal IDs in the payload, these are forwarded to the webhook URL. Implement a payload sanitization layer.

- **Risk (Inbound)**: Webhook error logs may contain sensitive payload data.
- **Mitigation**: Logs include webhook_id and delivery_id, not full payloads.
- **Gap**: The `$request->getContent()` used in signature validation contains the full webhook body. If logging level is DEBUG, framework middleware could log the raw request body. Ensure log levels in production do not include raw webhook payloads.

### Denial of Service
- **Risk (Inbound)**: Flood of webhook requests exhausting processing capacity.
- **Mitigation**: `api.rate_limit:webhook` middleware on all inbound webhook routes.
- **Gap**: Webhook processing may trigger expensive downstream operations (database writes, external API calls). Rate limiting at the HTTP layer may not prevent queue exhaustion if many valid webhooks arrive in a burst. Consider per-provider queue isolation.

- **Risk (Outbound)**: Customer webhook endpoint is slow or unresponsive, causing queue backlog.
- **Mitigation**: Configurable timeout per webhook (`$webhook->timeout_seconds`). Exponential backoff (1m, 5m, 15m). Job retries allowed for 24 hours then marked as permanently failed.
- **Gap**: No circuit breaker for persistently failing webhook endpoints. A misconfigured webhook could generate retries indefinitely for 24 hours, consuming queue capacity. Implement automatic webhook deactivation after N consecutive failures.

### Elevation of Privilege
- **Risk (Inbound)**: Crafted webhook payload triggers unintended business logic (e.g., fake payment confirmation approving a card issuance).
- **Mitigation**: Signature validation ensures payload authenticity. Controllers should verify the event type and cross-reference with internal state (e.g., confirm a pending payment exists before marking it complete).
- **Gap**: Verify that all webhook controllers perform state validation (checking that the referenced entity exists and is in the expected state) rather than blindly trusting the webhook payload content. A valid-signature webhook with a crafted payment ID could reference a different user's payment if entity-level authorization is missing.

- **Risk (Outbound)**: Webhook subscriber escalates privileges by manipulating the webhook registration to receive events from other tenants.
- **Mitigation**: Webhook model should be tenant-scoped, ensuring subscribers only receive events for their tenant.
- **Gap**: Verify that `Webhook::active()->forEvent()` query is tenant-scoped. If the Webhook model does not use `UsesTenantConnection` or a global scope, a webhook registered by Tenant A could receive events from Tenant B.

---

## Summary of Critical Findings

| # | Finding | Severity | Flow |
|---|---------|----------|------|
| 1 | JIT funding balance check + hold lacks `lockForUpdate()` — TOCTOU race condition | **Critical** | Payment Auth |
| 2 | Outbound webhook SSRF — no URL validation against private IP ranges | **Critical** | Webhook |
| 3 | Bridge quote not stored server-side — client can submit tampered quote object | **High** | Cross-Chain |
| 4 | ZK private inputs in temp files not guaranteed to be securely deleted | **High** | ZK Proofs |
| 5 | Open Banking webhook uses session-based state (fragile, session fixation risk) | **High** | Webhook |
| 6 | No concurrency limit on ZK proof generation (CPU exhaustion via subprocess spawning) | **High** | ZK Proofs |
| 7 | `$allowWithoutTenant` is publicly writable static, could be globally toggled | **Medium** | Tenant Isolation |
| 8 | Coinbase/Paysera webhooks lack timestamp — replay attacks possible | **Medium** | Webhook |
| 9 | No per-card-token rate limiting on JIT authorization requests | **Medium** | Payment Auth |
| 10 | Bridge sender address not verified against user's registered wallets | **Medium** | Cross-Chain |
| 11 | No HTTPS enforcement on outbound webhook URLs | **Medium** | Webhook |
| 12 | Outbound webhook payloads not sanitized for sensitive fields | **Medium** | Webhook |
| 13 | Circuit file integrity not cryptographically verified before proof generation | **Medium** | ZK Proofs |
| 14 | No bridge-specific value/frequency limits (card spend limits don't apply) | **Low** | Cross-Chain |
| 15 | Marqeta HMAC signature is optional when secret not configured | **Low** | Payment Auth |
