# Security Audit Scope — FinAegis/Zelta v7.10.0

**Prepared**: 2026-03-29
**Platform**: PHP 8.4 / Laravel 12 / MySQL 8 / Redis
**PHPStan Level**: 8 (strict)
**Domain Modules**: 56 bounded contexts (DDD)
**Route Count**: ~1,400 registered routes
**GraphQL Schemas**: 45 schema files

---

## Target Overview

Core banking platform with 56 domain modules, payment processing (x402/MPP/AP2), card issuance, cross-chain DeFi bridging, zero-knowledge proofs, post-quantum cryptography, and multi-tenant team isolation. Handles stablecoin reserves, inter-bank transfers, lending, compliance/AML screening, and fraud detection.

---

## In-Scope

### Authentication & Authorization
- Sanctum token auth (API) with method-based scope enforcement (GET->read, POST/PUT/PATCH->write, DELETE->delete)
- Session auth (web) with CSRF protection (`VerifyCsrfToken` middleware)
- WebAuthn/FIDO2 passkeys with throttling (5 attempts/minute)
- Two-factor authentication (required for admin via `RequireTwoFactorForAdmin`)
- Team-based multi-tenancy isolation (`InitializeTenancyByTeam` with membership verification)
- Role-based access control (admin/user) via `is_admin` middleware
- API key authentication (`AuthenticateApiKey`, `AuthenticateApiOrSanctum`)
- Agent DID authentication (`AuthenticateAgentDID`, agent scopes, agent capabilities)
- Partner authentication (`PartnerAuthMiddleware`) for BaaS integrations
- IP blocking (`IpBlocking`, `CheckBlockedIp`)
- Token expiration checks (`CheckTokenExpiration`)
- Rate limiting on auth endpoints (`api.rate_limit:auth`)

### Payment Processing (CRITICAL)
- **x402 USDC payment flow**: Facilitator settlement, Solana HSM signing (Ed25519), EIP-712 signing, 8 EVM+Solana networks
- **MPP multi-rail payments**: Stripe, Tempo, Lightning, Card, x402 fallback
- **AP2 payment protocol**: Google-compatible payment mandates
- **JIT funding** for card authorization (2000ms latency budget) — balance check + hold creation
- **Spend limit enforcement** per card token
- **Payment intent service** for mobile payments
- **Webhook signature verification** (HMAC-SHA256/SHA512) for Stripe, Coinbase, Paysera, Santander, Open Banking, Marqeta
- **Protocol subdomains**: `x402.api.*` / `mpp.api.*` auto-apply payment middleware
- **WebSocket paid channels**: `ws.payment` middleware for subscription-based channel access
- **Idempotency middleware** with cache-based deduplication and atomic locking

### Financial Operations
- **Card issuance** via Rain/Marqeta adapters with JIT funding, spend limits, card provisioning
- **Bank account connections** via Open Banking/PSD2 (Santander, generic Open Banking)
- **Inter-bank transfers** with state machine transitions
- **Cross-chain bridging**: Wormhole and Circle CCTP adapters with quote comparison
- **DeFi operations**: Uniswap V3 swaps, Aave V3 lending/borrowing
- **Stablecoin reserve management** with event sourcing
- **Treasury management** with portfolio event sourcing
- **Lending** with loan aggregates, repayment schedules
- **On/off ramp**: Stripe Bridge integration with async webhook processing
- **Batch processing** with event sourcing
- **CGO operations** (Chief Growth Officer analytics)

### New Domains (v7.2.0–v7.5.0) — Added in v7.7.0 Audit Scope
- **ISO 20022** (`app/Domain/ISO20022/`): SEPA/SWIFT message generation and parsing (pain, pacs, camt families). XML schema validation, namespace handling, XSD conformance. Input: raw message XML; risk surface: XXE injection, schema validation bypass.
- **Open Banking PSD2** (`app/Domain/OpenBanking/`): Berlin Group standard account/payment APIs. OAuth2 PKCE flow, consent management, session nonce (cache-based, single-use). Risk: OAuth state fixation, consent scope escalation, account enumeration.
- **ISO 8583** (`app/Domain/ISO8583/`): ATM/POS message codec (encode/decode), authorization/reversal/settlement handlers. Risk: bitmap overflow, field-length manipulation, response code spoofing.
- **US Payment Rails** (`app/Domain/PaymentRails/`): ACH (NACHA), Fedwire, RTP, FedNow file generation and routing. Risk: routing number validation bypass, NACHA file injection, duplicate payment via idempotency gaps.
- **Interledger Protocol** (`app/Domain/Interledger/`): ILP packet routing and streaming payments. Risk: packet amount manipulation, connector trust misconfiguration.
- **Double-Entry Ledger** (`app/Domain/Ledger/`): GL posting with double-entry invariant, TigerBeetle and Eloquent drivers, reconciliation. Risk: unbalanced entry bypass, reconciliation discrepancy suppression, driver fallback exploitation.
- **Microfinance Suite** (`app/Domain/Microfinance/`): Group lending, loan provisioning (IFRS), share accounts, teller vault, field officer collections. Risk: group liability bypass, provisioning classification manipulation, vault imbalance.

### Cryptography
- **Post-quantum encryption**: ML-KEM-768 (key encapsulation), ML-DSA-65 (digital signatures)
- **Hybrid encryption**: PQ + classical combined scheme with AAD
- **Quantum-safe key rotation**: Re-encryption service for key lifecycle
- **Zero-knowledge proofs**: Groth16/BN254 via snarkjs CLI, Circom circuits, trusted setup
- **ZK services**: Proof of Innocence, selective disclosure, ZK-KYC, delegated proofs
- **Merkle trees**: Railgun-compatible, Poseidon hashing
- **HSM integration**: Solana HSM signer for x402 payments
- **Key management**: Shamir secret sharing, shard distribution, key reconstruction
- **Webhook HMAC signatures**: SHA-256 with constant-time comparison (`hash_equals`)
- **Key material zeroing**: `sodium_memzero()` for sensitive data

### API Surface
- **REST API v1**: Primary API with Sanctum auth
- **REST API v2**: Next-gen API with `ensure.json` enforcement
- **BIAN-compliant API**: Banking Industry Architecture Network endpoints
- **GraphQL** (Lighthouse PHP): 45 schema files with query cost analysis and rate limiting
- **WebSocket**: Private broadcast channels with Sanctum auth
- **.well-known discovery endpoints**: `x402-configuration`, `mpp-configuration`, `ap2-configuration`, `agent.json`, `apple-app-site-association`, `assetlinks.json`
- **Swagger/OpenAPI**: L5-Swagger generated documentation at `/api/documentation`
- **Subdomain routing**: `api.*`, `x402.*`, `mpp.*` subdomains

### Infrastructure
- **Multi-tenant data isolation** via Stancl/Tenancy with team-based resolution
- **Redis Streams** event bus with DLQ + backpressure + schema registry
- **Queue job processing** via Laravel Horizon
- **Event sourcing**: Spatie v7.7+ with domain-specific tables and snapshots
- **CQRS**: Command/Query Bus in `app/Infrastructure/`
- **Structured logging** with request correlation IDs
- **Distributed tracing** middleware
- **Metrics collection** middleware
- **Circuit breaker pattern** in EthRpcClient

### Compliance & Fraud
- **AML screening** service
- **KYC/Enhanced KYC** with biometric verification and Ondato integration
- **Enhanced Due Diligence** service
- **Transaction monitoring** service
- **Suspicious Activity Reports** (SAR)
- **Regulatory reporting** service
- **GDPR data export** (encrypted)
- **Fraud detection**: ML-based, rule engine, behavioral analysis, device fingerprinting, geo-analysis, anomaly detection
- **Compliance case management** with DB transactions

---

## Out of Scope
- Third-party service internals (Stripe, Rain, Circle, Coinbase, Marqeta, Ondato APIs)
- Mobile app (separate repository)
- CDN/WAF configuration
- Physical security
- Social engineering
- Network-level attacks (DDoS at infrastructure layer)
- Circom circuit cryptographic soundness (requires specialized ZK audit)

---

## Known Security Controls

### Middleware Stack (43 middleware classes)
| Control | Implementation |
|---------|---------------|
| Security headers | `SecurityHeaders` — CSP, HSTS (preload), X-Frame-Options DENY, X-Content-Type-Options, Permissions-Policy |
| CSRF protection | `VerifyCsrfToken` on all web routes |
| CORS | `HandleCors` prepended globally |
| API rate limiting | `ApiRateLimitMiddleware` (per-route configurable) |
| Transaction rate limiting | `TransactionRateLimitMiddleware` |
| Method scope enforcement | `EnforceMethodScope` — maps HTTP method to token ability |
| IP blocking | `IpBlocking` + `CheckBlockedIp` on API group |
| Idempotency | `IdempotencyMiddleware` with atomic locking |
| Webhook validation | `ValidateWebhookSignature` — HMAC with timestamp tolerance |
| GraphQL cost analysis | `GraphQLQueryCostMiddleware` — max cost 500, depth penalty |
| GraphQL rate limiting | `GraphQLRateLimitMiddleware` |
| Tenant isolation | `InitializeTenancyByTeam` with membership verification + audit logging |
| Data residency | `DataResidencyMiddleware` |
| 2FA for admin | `RequireTwoFactorForAdmin` |
| Token expiration | `CheckTokenExpiration` |
| JSON enforcement | `EnsureJsonRequest` on v2 API |
| Structured logging | `StructuredLoggingMiddleware` on all API routes |
| Distributed tracing | `TracingMiddleware` on all API routes |

### Cryptographic Controls
- `hash_equals()` used for all signature comparisons (constant-time)
- `random_bytes()` for nonce/token generation
- `hash_hmac()` with config-based secrets for webhook signatures
- `sodium_memzero()` for sensitive key material
- Encrypted model casts for sensitive fields (`encrypted:array`)
- Timestamp tolerance on webhook signatures (5-minute window)
- Post-quantum hybrid encryption with AAD

### Application Security
- No `dd()` or `dump()` calls in production code
- No `env()` calls outside config files
- Event sourcing provides full audit trail
- DB transactions with `lockForUpdate()` for financial mutations
- Demo mode checks (`app()->environment('production')`) on sensitive services
- Plugin security scanner (`PluginSecurityScanner`) checks for raw SQL patterns

---

## Test Accounts
- Demo user: `php artisan user:create`
- Admin user: `php artisan user:create --admin`
- Promote/demote: `php artisan user:promote` / `php artisan user:demote`
- Test environment: `APP_ENV=demo` with `SHOW_PROMO_PAGES=true`
- Demo SMS setup: `php artisan sms:setup-demo`

---

## Automated Scan Results

### Dependency Vulnerabilities
```
composer audit: No security vulnerability advisories found.
```
**Result**: PASS — Zero known CVEs in Composer dependencies.

### env() Usage Outside Config
```
grep -rn "env(" app/ --include="*.php" | grep -v "config/" | grep -v "environment(": No matches
```
**Result**: PASS — All environment variable access goes through `config()`, safe for `config:cache`.

### Debug Functions in Production Code
```
grep -rn "\bdd(\|\bdump(" app/ --include="*.php": No matches
```
**Result**: PASS — No debug dump functions in application code.

### Raw SQL Usage (Potential Injection Vectors)
```
grep -rn "DB::raw|whereRaw|selectRaw|havingRaw" app/ --include="*.php": 25+ matches
```
**Result**: REVIEW NEEDED — Found raw SQL in:
- `Console/Commands/DomainStatusCommand.php` — `DB::raw('COUNT(*)')` (static, no user input)
- `Console/Commands/CreateSnapshot.php` — `DB::raw('COUNT(*)')` (static aggregation)
- `Console/Commands/RunLoadTests.php` — `DB::raw('SUM(...)')` (test tooling)
- `Console/Commands/EventCompactCommand.php` — `havingRaw('count(*) > ?', [$keepLatest])` (parameterized)
- `Http/Controllers/ExchangeRateViewController.php` — `DB::raw('COUNT/AVG/MAX')` (static aggregation)
- `Domain/VirtualsAgent/Services/AgdpReportingService.php` — `DB::raw('COALESCE/SUM/COUNT')` (static)
- `Http/Controllers/Api/WorkflowMonitoringController.php` — `selectRaw`, `whereRaw` with parameterized values
- `Infrastructure/Plugins/PluginSecurityScanner.php` — Pattern for detecting raw SQL (meta, not execution)

**Assessment**: All raw SQL uses static aggregate functions or parameterized bindings. No user-controlled input concatenated into raw queries. Low risk but should be verified during pentest.

### Mass Assignment Protection
```
Models without $fillable or $guarded: 31 files
```
**Result**: REVIEW NEEDED — 31 models lack explicit `$fillable` or `$guarded`:
- **Event sourcing models** (14): `*Event.php`, `*Snapshot.php` — These are internal event store tables, typically not exposed to user input via controllers. Low risk.
- **Banking models** (7): `BankTransfer`, `BankTransaction`, `BankCapabilities`, `BankConnection`, `BankAccount`, `BankStatement`, `BankBalance` — Should be verified that these are populated only from validated/trusted sources.
- **Financial models** (6): `Transfer`, `Ledger`, `PaymentWithdrawal`, `PaymentDeposit` — Critical models that handle financial data.
- **Core models** (4): `Role`, `Tenant`, `Membership`, `TestTransaction` — `Role`/`Tenant`/`Membership` managed by Jetstream/Tenancy packages.

**Assessment**: Event sourcing models are low risk (internal). Banking and financial models warrant verification that all create/update paths use validated data.

### Unescaped Blade Output
```
{!! ... !!} usage: 37 occurrences across 22 blade files
```
**Result**: REVIEW NEEDED — Unescaped output found in:
- SEO/schema markup (`seo.blade.php`, `seo-schema.blade.php`) — JSON-LD, typically safe
- Blog content (`blog/show.blade.php`, `blog/index.blade.php`) — Potential XSS if content is user-generated
- Form components (`input.blade.php`, `checkbox.blade.php`) — HTML attributes
- Swagger UI (`l5-swagger/index.blade.php`) — Vendor template
- Code blocks (`code-block.blade.php`) — Pre-formatted code display
- Static content pages (`terms.blade.php`, `policy.blade.php`)
- Fraud alerts (`fraud/alerts/index.blade.php` — 4 occurrences) — Should verify data source

---

## Priority Areas for Penetration Testing

### P0 — Critical
1. **Cross-tenant data leakage**: Verify `InitializeTenancyByTeam` membership checks cannot be bypassed. Test switching teams via API manipulation. Verify tenant-scoped queries in all 56 domain modules.
2. **Payment amount manipulation**: Test x402/MPP payment flows for amount tampering between quote and settlement. Verify JIT funding balance check + hold creation atomicity.
3. **JIT funding race conditions**: Test concurrent authorization requests for the same card/balance. Verify balance check and hold creation are atomic (currently no `lockForUpdate()` in `JitFundingService`).
4. **Webhook replay attacks**: Verify timestamp tolerance enforcement across all 6 webhook providers. Test replay with valid signatures but expired timestamps.

### P1 — High
5. **GraphQL injection/DoS**: Test nested query attacks against cost estimator bypass. Verify batch query limits. Test introspection access in production.
6. **Post-quantum key material exposure**: Verify `sodium_memzero()` is called on all key material paths. Test key rotation re-encryption for data leakage.
7. **ZK proof forgery**: Test proof verification with malformed inputs. Verify circuit constraint enforcement. Test delegated proof service authorization.
8. **Cross-chain bridge manipulation**: Test bridge quote manipulation between quote and execution. Verify transaction tracking for double-spend scenarios.
9. **Agent DID spoofing**: Test DID authentication bypass. Verify agent capability enforcement across all agent protocol endpoints.

### P2 — Medium
10. **API scope escalation**: Test `EnforceMethodScope` bypass via HTTP method override headers. Verify TransientToken handling in production.
11. **Partner auth bypass**: Test BaaS partner authentication middleware for credential stuffing.
12. **Idempotency key abuse**: Test cache poisoning via idempotency keys. Verify lock contention handling under load.
13. **Event sourcing integrity**: Test for event store tampering. Verify snapshot consistency with event replay.
14. **Unescaped Blade output**: Verify blog content and fraud alert XSS vectors with user-controlled data.

### P3 — Low
15. **Raw SQL review**: Verify all `DB::raw` / `selectRaw` / `whereRaw` uses remain free from injection.
16. **Mass assignment on banking models**: Verify all model creation paths use validated data only.
17. **CSP bypass**: Test Content Security Policy effectiveness, especially `unsafe-inline` on script-src.
18. **Demo mode security**: Verify demo environment cannot be activated in production.

### P1 — High (New Domains)
19. **ISO 20022 XXE**: Test XML parsing in ISO 20022 parser for XXE injection via crafted message payloads.
20. **Open Banking consent scope**: Verify PSD2 consent scope cannot be widened post-issuance. Test PKCE state parameter handling.
21. **ISO 8583 bitmap overflow**: Test oversized bitmap fields for buffer overflow or field confusion in MessageCodec.
22. **ACH routing validation**: Verify NACHA routing numbers are validated before ACH file submission. Test duplicate prevention.

### P2 — Medium (New Domains)
23. **Ledger double-entry bypass**: Verify `LedgerService::post()` invariant cannot be bypassed via concurrent posts or driver swap.
24. **MFI vault imbalance**: Verify teller vault cash-in/cash-out prevents negative balance via race conditions.
25. **ILP packet relay trust**: Verify Interledger connector only routes to trusted next-hop connectors.
26. **TigerBeetle fallback**: Verify graceful fallback to Eloquent driver on TigerBeetle unreachability doesn't lose entries.

*Document Version: 7.9.0*
*Created: January 11, 2026*
*Updated: April 4, 2026 (v7.9.0 Solana Balances, Helius Webhooks & SEO Overhaul)*
