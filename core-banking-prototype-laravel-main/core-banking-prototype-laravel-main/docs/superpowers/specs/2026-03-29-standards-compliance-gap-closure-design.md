# Standards & Compliance Gap Closure — Design Spec

**Date:** 2026-03-29
**Version:** v7.2.0 through v7.5.0
**Status:** Approved

## Overview

Close 10 identified gaps between FinAegis and worldwide open-source core banking platforms. Based on competitive analysis of Apache Fineract, Moov, Open Bank Project, Hyperswitch, Rafiki, Mojaloop, Galoy, and commercial platforms (Temenos, Vault, 10x Banking).

FinAegis leads in Web3/DeFi/ZK/PQC — the gaps are in **traditional banking infrastructure**: ISO standards, payment rails, Open Banking compliance, and accounting primitives.

## Design Decisions

- **Configurable per deployment** — not EU-first or US-first; region-specific rails enabled via config
- **Standards-first approach** — ISO 20022 as internal message lingua franca; compliance layers built on top
- **PHP-native default, optional high-perf backends** — Eloquent-based ledger with TigerBeetle driver swap
- **Developer experience via Option A+** — enhanced sandbox + webhook testing + CLI; no full self-service portal (institutional product, not developer-first API company)
- **Phased minor releases** — 4 releases, each with its own PR

## Release Schedule

| Phase | Version | Scope |
|-------|---------|-------|
| 1 | v7.2.0 | Standards & Compliance Foundation (ISO 20022, Open Banking PSD2, ISO 8583) |
| 2 | v7.3.0 | Payment Rails (US rails, SEPA DD, smart routing, Interledger) |
| 3 | v7.4.0 | Accounting & Infrastructure (double-entry ledger, TigerBeetle driver) |
| 4 | v7.5.0 | Market Expansion & DX (MFI suite, developer experience A+) |

---

## Phase 1: v7.2.0 — Standards & Compliance Foundation

### 1.1 ISO 20022 Message Engine

**New domain:** `app/Domain/ISO20022/`

**Purpose:** Parse, generate, and validate ISO 20022 financial messages. Serves as the internal message format for all payment rails.

**Services:**

| Service | Responsibility |
|---------|---------------|
| `MessageParser` | Parse XML/JSON ISO 20022 messages into typed DTOs |
| `MessageGenerator` | Generate valid ISO 20022 XML from DTOs |
| `MessageValidator` | XSD schema validation against official ISO 20022 schemas |
| `MessageRegistry` | Registry of supported message types and their DTO mappings |

**Value Objects (DTOs):**

| VO | ISO 20022 Message | Use Case |
|----|-------------------|----------|
| `Pain001` | pain.001 | Customer Credit Transfer Initiation |
| `Pain008` | pain.008 | Customer Direct Debit Initiation |
| `Pacs008` | pacs.008 | FI-to-FI Customer Credit Transfer |
| `Pacs002` | pacs.002 | Payment Status Report |
| `Pacs003` | pacs.003 | FI-to-FI Customer Direct Debit |
| `Pacs004` | pacs.004 | Payment Return |
| `Camt053` | camt.053 | Bank-to-Customer Statement |
| `Camt054` | camt.054 | Bank-to-Customer Debit/Credit Notification |

**Enums:**
- `MessageFamily` — pain, pacs, camt, etc.
- `TransactionStatus` — ACCP, ACSP, ACSC, RJCT, PDNG, etc.
- `PaymentMethod` — TRF, DD, CHK, etc.

**Config:** `config/iso20022.php`
```php
return [
    'enabled_families' => ['pain', 'pacs', 'camt'],
    'validation_strictness' => env('ISO20022_STRICT', true),
    'schema_path' => storage_path('app/iso20022/schemas'),
    'default_currency' => env('ISO20022_DEFAULT_CURRENCY', 'EUR'),
    'uetr_enabled' => true, // Unique End-to-End Transaction Reference
];
```

**Key design constraints:**
- All messages carry a `BusinessMessageIdentifier` (BAH) for routing
- UETR (UUID v4) for cross-border payment tracking
- XML namespace handling must be exact — ISO 20022 schemas are namespace-sensitive
- Support both XML (standard) and JSON (modern APIs) serialization

**GraphQL:** `graphql/iso20022.graphql` — queries for message validation, status lookup

**Tests:**
- Unit tests for each DTO parser/generator with sample ISO 20022 XML
- Validation tests with intentionally malformed messages
- Round-trip tests: generate → parse → compare

### 1.2 Open Banking Compliance Layer

**New domain:** `app/Domain/OpenBanking/`

**Purpose:** PSD2-compliant consent management, AISP/PISP services, with pluggable adapters for Berlin Group and UK Open Banking standards.

**Services:**

| Service | Responsibility |
|---------|---------------|
| `ConsentService` | Create, authorize, revoke, expire consents (PSD2 consent lifecycle) |
| `AispService` | Account Information Service Provider — read accounts/transactions with consent |
| `PispService` | Payment Initiation Service Provider — initiate payments with consent |
| `ConsentEnforcementService` | Verify consent validity, permissions, and expiry before data access |
| `BerlinGroupAdapter` | Format requests/responses per NextGenPSD2 spec |
| `UkObAdapter` | Format requests/responses per UK Open Banking spec |
| `TppRegistrationService` | Register and manage Third-Party Providers (client_id, certs, redirect URIs) |

**Models:**

| Model | Key Fields |
|-------|-----------|
| `Consent` | consent_id, tpp_id, status (awaiting_authorization/authorized/rejected/revoked/expired), permissions (array), account_ids, expiry_at, frequency_per_day, recurring_indicator |
| `TppRegistration` | tpp_id, name, client_id, client_secret_hash, eidas_certificate, redirect_uris, roles (AISP/PISP/CBPII), status |
| `ConsentAccessLog` | consent_id, tpp_id, endpoint, timestamp, ip_address |

**Consent lifecycle:**
```
Created → AwaitingAuthorization → Authorized → [Active use] → Expired/Revoked
                                 → Rejected
```

**Middleware:**
- `ValidateTppCertificate` — Verify eIDAS/QWAC certificate on incoming TPP requests
- `EnforceConsent` — Check valid consent exists for the requested scope before data access

**Routes:**
```
POST   /api/v1/open-banking/consents              — Create consent request
GET    /api/v1/open-banking/consents/{id}          — Get consent status
DELETE /api/v1/open-banking/consents/{id}           — Revoke consent
GET    /api/v1/open-banking/accounts               — List accounts (AISP, consent-gated)
GET    /api/v1/open-banking/accounts/{id}           — Account detail
GET    /api/v1/open-banking/accounts/{id}/balances  — Account balances
GET    /api/v1/open-banking/accounts/{id}/transactions — Transaction history
POST   /api/v1/open-banking/payments               — Initiate payment (PISP, consent-gated)
GET    /api/v1/open-banking/payments/{id}           — Payment status
```

**Config:** `config/openbanking.php`
```php
return [
    'enabled' => env('OPEN_BANKING_ENABLED', false),
    'standard' => env('OPEN_BANKING_STANDARD', 'berlin_group'), // berlin_group|uk_ob
    'consent_max_days' => 90,
    'frequency_per_day' => 4,
    'require_sca' => true, // Strong Customer Authentication
    'tpp_certificate_validation' => env('OPEN_BANKING_VALIDATE_CERTS', true),
    'supported_permissions' => [
        'ReadAccountsBasic', 'ReadAccountsDetail',
        'ReadBalances', 'ReadTransactionsBasic',
        'ReadTransactionsDetail', 'ReadTransactionsCredits',
        'ReadTransactionsDebits',
    ],
];
```

**GraphQL:** `graphql/open-banking.graphql`

**Tests:**
- Consent lifecycle tests (create → authorize → use → expire)
- Unauthorized access rejection when consent missing/expired/wrong scope
- Berlin Group vs UK OB format adapter tests
- TPP certificate validation tests

### 1.3 ISO 8583 Card Network Message Processor

**New domain:** `app/Domain/ISO8583/`

**Purpose:** Encode/decode ISO 8583 messages for direct card network integration (Visa, Mastercard authorization, reversal, settlement).

**Services:**

| Service | Responsibility |
|---------|---------------|
| `MessageCodec` | Encode/decode ISO 8583 bitmap-based messages (1987/1993/2003 versions) |
| `FieldDefinitions` | Standard field map — MTI, PAN, processing code, amount, STAN, etc. |
| `AuthorizationHandler` | Process 0100 (auth request) / 0110 (auth response) messages |
| `ReversalHandler` | Process 0400 (reversal request) / 0410 (reversal response) |
| `SettlementHandler` | Process 0500 (settlement) / 0510 (settlement response) |
| `NetworkRouter` | Route messages to correct card network (Visa/MC) based on BIN range |

**Value Objects:**
- `Iso8583Message` — typed container with MTI, bitmap, data elements
- `DataElement` — individual field with type, length, value
- `Bitmap` — primary + secondary bitmap handling (64/128 fields)

**Enums:**
- `MessageTypeIndicator` — 0100, 0110, 0200, 0210, 0400, 0410, 0500, 0510, etc.
- `ProcessingCode` — purchase, cash_advance, refund, balance_inquiry
- `ResponseCode` — 00 (approved), 05 (declined), 51 (insufficient_funds), etc.

**Config:** `config/iso8583.php`
```php
return [
    'version' => env('ISO8583_VERSION', '1993'), // 1987|1993|2003
    'header_length' => 2,
    'encoding' => 'ascii', // ascii|ebcdic
    'networks' => [
        'visa' => [
            'host' => env('VISA_ISO8583_HOST'),
            'port' => env('VISA_ISO8583_PORT', 9100),
            'timeout' => 30,
        ],
        'mastercard' => [
            'host' => env('MC_ISO8583_HOST'),
            'port' => env('MC_ISO8583_PORT', 9200),
            'timeout' => 30,
        ],
    ],
];
```

**Integration:** Wires into existing `CardIssuance` domain:
- `JitFundingService` sends 0100 auth responses
- `SpendLimitEnforcementService` referenced during auth processing
- Card provisioning generates PAN ranges for BIN routing

**Tests:**
- Encode/decode round-trip for each MTI
- Bitmap parsing (primary + secondary)
- Field validation (PAN Luhn check, amount formatting, date fields)
- Auth flow: 0100 → SpendLimitCheck → 0110 response

---

## Phase 2: v7.3.0 — Payment Rails

### 2.1 US Payment Rails

**New domain:** `app/Domain/PaymentRails/`

**Services:**

| Service | Responsibility |
|---------|---------------|
| `AchService` | ACH origination — credits (direct deposit), debits (bill pay), same-day/next-day |
| `NachaFileGenerator` | Generate NACHA-compliant ACH batch files |
| `NachaFileParser` | Parse ACH return files (R01-R99) and NOC (Notification of Change) files |
| `FedwireService` | Fedwire funds transfer — real-time gross settlement, acknowledgments |
| `RtpService` | RTP via The Clearing House — request-for-payment, instant credit transfers |
| `FedNowService` | FedNow instant payments — ISO 20022 native (pacs.008/pacs.002) |
| `PaymentRailRouter` | Select optimal rail based on amount, speed, cost, time-of-day |

**NACHA file format handling:**
- File Header/Control records
- Batch Header/Control records
- Entry Detail + Addenda records
- SEC codes: PPD (payroll), CCD (corporate), WEB (internet), TEL (telephone)
- Same-Day ACH flag support
- Return codes mapping (R01 insufficient funds, R02 closed account, etc.)

**FedNow integration:**
- Uses ISO 20022 message engine from Phase 1 (pacs.008, pacs.002)
- Real-time settlement confirmation
- Request for Payment (pain.013) support

**Models:**
- `AchBatch` — batch_id, status, sec_code, entry_count, total_debit, total_credit, settlement_date
- `AchEntry` — trace_number, routing_number, account_number, amount, transaction_code
- `PaymentRailTransaction` — polymorphic — tracks any rail transaction with status

**Config:** `config/payment_rails.php`
```php
return [
    'ach' => [
        'enabled' => env('ACH_ENABLED', false),
        'originator_id' => env('ACH_ORIGINATOR_ID'),
        'originating_dfi' => env('ACH_ORIGINATING_DFI'),
        'company_name' => env('ACH_COMPANY_NAME', 'FinAegis'),
        'same_day_enabled' => env('ACH_SAME_DAY', true),
        'cutoff_time' => env('ACH_CUTOFF_TIME', '16:30'), // ET
    ],
    'fedwire' => [
        'enabled' => env('FEDWIRE_ENABLED', false),
        'sender_aba' => env('FEDWIRE_SENDER_ABA'),
        'endpoint' => env('FEDWIRE_ENDPOINT'),
    ],
    'rtp' => [
        'enabled' => env('RTP_ENABLED', false),
        'participant_id' => env('RTP_PARTICIPANT_ID'),
        'endpoint' => env('RTP_ENDPOINT'),
    ],
    'fednow' => [
        'enabled' => env('FEDNOW_ENABLED', false),
        'participant_id' => env('FEDNOW_PARTICIPANT_ID'),
        'endpoint' => env('FEDNOW_ENDPOINT'),
    ],
];
```

**GraphQL:** `graphql/payment-rails.graphql`

### 2.2 SEPA Direct Debit (extend Banking domain)

**New services in `app/Domain/Banking/Services/`:**

| Service | Responsibility |
|---------|---------------|
| `SepaDirectDebitService` | Mandate management (create, amend, cancel), DD collection submission |
| `SepaCreditTransferService` | SCT and SCT Inst (SEPA Instant Credit Transfer) |
| `SepaMandateService` | SEPA mandate lifecycle — B2B and CORE schemes |

**ISO 20022 integration:**
- pain.008 (Customer Direct Debit Initiation)
- pacs.003 (FI-to-FI Direct Debit)
- pain.002 (Payment Status Report for DD)

**Leverages existing custodian connectors:** Paysera, Deutsche Bank for SEPA submission.

**Models:**
- `SepaMandate` — mandate_id, creditor_id, debtor_iban, scheme (CORE/B2B), status, signed_at

### 2.3 Smart Payment Routing Enhancement

**Extend `app/Domain/Banking/Services/BankRoutingService.php`:**

New service: `IntelligentRoutingService`

Enhances existing `BankRoutingService` scoring with:
- **Historical success rates** per rail/provider (rolling 7-day window)
- **Latency percentiles** (p50, p95, p99) per provider
- **Cost optimization** — compare fees across rails for given amount/currency
- **Failover chains** — ordered list of fallback rails if primary fails
- **Time-of-day routing** — ACH cutoff times, SEPA batch windows
- **Decision logging** — audit trail for every routing decision (rail chosen, score, alternatives)
- **A/B testing** — split traffic between routing strategies for optimization

### 2.4 Interledger Protocol

**New domain:** `app/Domain/Interledger/`

**Services:**

| Service | Responsibility |
|---------|---------------|
| `IlpConnectorService` | STREAM protocol implementation, ILP packet forwarding |
| `OpenPaymentsService` | GNAP-based authorization for incoming/outgoing payments |
| `QuoteService` | Cross-currency rate quotes via ILP |
| `IlpAddressResolver` | Map FinAegis accounts to ILP addresses |

**Integration points:**
- Bridge between fiat rails (ACH/SEPA from 2.1/2.2) and crypto (existing CrossChain domain)
- Open Payments API endpoints for wallet-to-wallet interop
- STREAM protocol for streaming micropayments (complements x402)

**Config:** `config/interledger.php`

---

## Phase 3: v7.4.0 — Accounting & Infrastructure

### 3.1 Double-Entry Ledger Engine

**New domain:** `app/Domain/Ledger/`

**Services:**

| Service | Responsibility |
|---------|---------------|
| `LedgerService` | Post journal entries, enforce double-entry invariant (sum debits = sum credits) |
| `ChartOfAccountsService` | Account hierarchy — assets, liabilities, equity, revenue, expenses |
| `TrialBalanceService` | Generate trial balance, income statement, balance sheet |
| `PostingRuleEngine` | Auto-posting rules (fee collection, interest accrual, settlement) |
| `ReconciliationService` | Compare GL balances vs domain balances, flag discrepancies |

**Models:**

| Model | Key Fields |
|-------|-----------|
| `LedgerAccount` | code, name, type (asset/liability/equity/revenue/expense), parent_id, currency, is_active |
| `JournalEntry` | entry_id, description, posted_at, source_domain, source_event_id, status |
| `JournalLine` | entry_id, account_code, debit_amount, credit_amount, currency, narrative |
| `PostingRule` | name, trigger_event, debit_account, credit_account, amount_expression, is_active |
| `ReconciliationReport` | period, domain, gl_balance, domain_balance, variance, status |

**Double-entry invariant:**
```
For every JournalEntry: SUM(debit_amounts) == SUM(credit_amounts)
```
Enforced at the service layer — `LedgerService::post()` throws if unbalanced.

**Chart of Accounts (default seed):**
```
1000  Assets
  1100  Cash & Bank
    1110  Operating Account
    1120  Settlement Account
  1200  Loans Receivable
  1300  Card Receivables
  1400  DeFi Positions
2000  Liabilities
  2100  Customer Deposits
  2200  Customer Wallets
  2300  Pending Settlements
3000  Equity
  3100  Retained Earnings
4000  Revenue
  4100  Transaction Fees
  4200  Interest Income
  4300  Exchange Revenue
5000  Expenses
  5100  Payment Processing Fees
  5200  Network Fees
  5300  Operational Costs
```

### 3.2 Ledger Driver Interface

**Interface:** `app/Domain/Ledger/Contracts/LedgerDriverInterface.php`
```php
interface LedgerDriverInterface
{
    public function post(JournalEntry $entry): void;
    public function balance(string $accountCode, ?Carbon $asOf = null): Money;
    public function trialBalance(?Carbon $asOf = null): TrialBalanceReport;
    public function accountHistory(string $accountCode, Carbon $from, Carbon $to): Collection;
}
```

**Drivers:**

| Driver | Implementation |
|--------|---------------|
| `EloquentDriver` | MySQL-backed, row-level locking, default |
| `TigerBeetleDriver` | TigerBeetle client via HTTP API, optional high-throughput |

**Config:** `config/ledger.php`
```php
return [
    'driver' => env('LEDGER_DRIVER', 'eloquent'), // eloquent|tigerbeetle
    'tigerbeetle' => [
        'addresses' => env('TIGERBEETLE_ADDRESSES', '127.0.0.1:3001'),
        'cluster_id' => env('TIGERBEETLE_CLUSTER_ID', 0),
    ],
    'auto_posting' => true,
    'reconciliation_schedule' => 'daily',
];
```

### 3.3 Domain Integration

Wire existing domains to post GL entries on every financial transaction:

| Domain | Events → GL Entries |
|--------|-------------------|
| Account | Deposit → DR Cash, CR Customer Deposits |
| Account | Withdrawal → DR Customer Deposits, CR Cash |
| Banking | Transfer Out → DR Customer Deposits, CR Settlement Account |
| CardIssuance | Card Purchase → DR Card Receivables, CR Settlement Account |
| Lending | Loan Disbursement → DR Loans Receivable, CR Cash |
| Lending | Loan Repayment → DR Cash, CR Loans Receivable + CR Interest Income |
| Exchange | FX Trade → DR/CR based on currency pair |
| DeFi | Position Open → DR DeFi Positions, CR Customer Wallets |
| Payment | Fee Collection → DR Customer Deposits, CR Transaction Fees |

Each integration is an event listener — no domain code changes needed. GL posting is async via queued jobs.

---

## Phase 4: v7.5.0 — Market Expansion & Developer Experience

### 4.1 Full MFI Suite

**New domain:** `app/Domain/Microfinance/`

**Services:**

| Service | Responsibility |
|---------|---------------|
| `GroupLendingService` | Joint liability groups, group meetings, attendance, center hierarchy |
| `LoanProvisioningService` | Provisioning categories (standard, substandard, doubtful, loss), write-offs, rescheduling |
| `ShareAccountService` | Cooperative share accounts, dividend calculation, dividend distribution |
| `TellerService` | Cashier operations, vault management, denomination tracking, cash-in/cash-out |
| `FieldOfficerService` | Territory assignment, client portfolio, collection sheets, daily sync |
| `SavingsProductService` | Interest compounding schedules, dormancy tracking, withdrawal limits, minimum balance |
| `MeetingService` | Schedule recurring group meetings, track attendance, generate minutes |
| `CollectionSheetService` | Generate daily collection sheets per officer, record bulk payments |

**Models:**

| Model | Purpose |
|-------|---------|
| `Group` | Group name, center, office, activation date, meeting schedule |
| `GroupMember` | User → Group membership with role (leader, member) |
| `ShareAccount` | Account tied to cooperative shares, nominal value, shares purchased |
| `TellerCashier` | Staff member assigned as teller, vault balance, denomination breakdown |
| `FieldOfficer` | Staff member with territory, client portfolio, collection responsibilities |
| `CollectionSheet` | Daily sheet with expected vs actual collections per client/group |
| `LoanProvision` | Loan → provision category mapping, provision amount, review date |
| `GroupMeeting` | Scheduled meeting with attendance records and notes |

**Integration with existing domains:**
- Uses `Lending` domain for loan origination/servicing
- Uses `Account` domain for savings/deposit accounts
- Uses `Ledger` domain (Phase 3) for GL postings
- Uses `Compliance` domain for KYC on group members

**GraphQL:** `graphql/microfinance.graphql`

**Console commands:**
- `php artisan mfi:create-group` — Create lending group
- `php artisan mfi:generate-collection-sheets` — Daily collection sheet generation
- `php artisan mfi:run-provisioning` — Run loan provisioning classification

### 4.2 Developer Experience (Option A+)

**Enhanced sandbox (extend FinancialInstitution domain):**

New services in `app/Domain/FinancialInstitution/Services/`:

| Service | Responsibility |
|---------|---------------|
| `SandboxProvisioningService` | Create isolated sandbox tenant with seeded test data |
| `SandboxResetService` | Reset sandbox to clean state, re-seed |

Seed profiles:
- `basic` — 5 users, 10 accounts, 20 transactions
- `full` — users + accounts + transactions + loans + cards + wallets
- `payments` — focused on payment flow testing (ACH, SEPA, x402 test data)

Console commands:
- `php artisan partner:sandbox:create {partner} --profile=full`
- `php artisan partner:sandbox:reset {partner}`

**Webhook testing (extend Webhook domain):**

New services in `app/Domain/Webhook/Services/`:

| Service | Responsibility |
|---------|---------------|
| `WebhookReplayService` | Replay past deliveries to partner endpoint |
| `WebhookTestService` | Send test payloads for each event type |
| `WebhookDeliveryLogger` | Store last 100 deliveries per partner with request/response |

Routes:
```
POST /api/v1/webhooks/test/{event_type}      — Send test webhook
POST /api/v1/webhooks/replay/{delivery_id}   — Replay delivery
GET  /api/v1/webhooks/deliveries             — List recent deliveries
GET  /api/v1/webhooks/deliveries/{id}        — Delivery detail with request/response
```

**API key management (CLI + admin API):**

Console commands:
- `php artisan partner:api-key create {partner} --scopes=read,write`
- `php artisan partner:api-key rotate {partner}`
- `php artisan partner:api-key revoke {key_id}`
- `php artisan partner:api-key list {partner}`

Admin API (requires admin auth):
```
POST   /api/v1/admin/partner-api-keys          — Create key
DELETE /api/v1/admin/partner-api-keys/{id}      — Revoke key
GET    /api/v1/admin/partner-api-keys/{partner} — List keys
```

**Swagger enhancements:**
- Add sandbox-specific example values to OpenAPI annotations
- Per-environment base URL toggle in Swagger UI config
- Document all new Phase 1-4 endpoints

---

## Cross-Cutting Concerns

### Event Sourcing
All new domains emit domain events compatible with the existing Spatie v7.7+ event sourcing setup. Events stored in domain-specific tables.

### Multi-Tenancy
All new models use `UsesTenantConnection` trait where applicable. Sandbox tenants are isolated via existing stancl/tenancy.

### GraphQL
Each new domain gets a `.graphql` schema file imported in `graphql/schema.graphql`.

### Testing Strategy
- Unit tests for all services (PHPStan Level 8 compliant)
- Integration tests for cross-domain flows (e.g., ISO 20022 → ACH → GL posting)
- Structural tests for model/service existence (following TenantDataMigrationServiceTest pattern)
- Sanctum auth with `['read', 'write', 'delete']` abilities on all API tests

### Migration Strategy
- Each phase has its own set of migrations prefixed by date
- No breaking changes to existing APIs
- New domains are opt-in via config flags (e.g., `ACH_ENABLED=false` by default)

### File Count Estimate

| Phase | New Domains | New Services | New Models | New Tests |
|-------|-------------|-------------|------------|-----------|
| v7.2.0 | 3 | ~18 | ~6 | ~40 |
| v7.3.0 | 2 (+2 extensions) | ~14 | ~5 | ~35 |
| v7.4.0 | 1 | ~8 | ~5 | ~25 |
| v7.5.0 | 1 (+2 extensions) | ~12 | ~8 | ~30 |
| **Total** | **7 new** | **~52** | **~24** | **~130** |
