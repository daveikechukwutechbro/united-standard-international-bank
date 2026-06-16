# FinAegis Core Banking Prototype - Architecture Overview

## Domain-Driven Design Structure

### Core Domains
```
app/Domain/
├── Account/              # Account management with event sourcing
│   ├── Aggregates/      # TransactionAggregate for balance tracking
│   ├── Models/          # Account, Transaction, TransactionProjection
│   └── Workflows/       # Account lifecycle workflows
├── Exchange/            # Trading & exchange engine
│   ├── Activities/      # Trading activities
│   ├── Services/        # OrderMatchingService, LiquidityService
│   └── Workflows/       # Order matching, liquidity management
├── Stablecoin/          # Stablecoin framework
│   ├── Aggregates/      # Stablecoin lifecycle aggregates
│   ├── Services/        # Minting, burning, collateral management
│   └── Workflows/       # Mint/burn workflows with saga pattern
├── Lending/             # P2P lending platform
│   ├── Activities/      # Loan processing activities
│   ├── Models/          # Loan, LoanApplication, Collateral
│   └── Workflows/       # Loan application workflow with compensation
├── Wallet/              # Blockchain wallet management
│   ├── Connectors/      # Blockchain-specific connectors
│   ├── Factories/       # BlockchainConnectorFactory
│   └── Services/        # Wallet management services
├── Payment/             # Payment processing
│   ├── Services/        # Interface-based payment services
│   ├── Activities/      # Payment processing activities
│   └── Workflows/       # Payment workflows
├── CGO/                 # Continuous Growth Offering
├── Governance/          # Voting & governance system
├── Compliance/          # KYC/AML & regulatory compliance
├── Mobile/             # Mobile wallet backend (v2.2.0)
├── KeyManagement/      # Shamir's Secret Sharing, HSM (v2.4.0)
├── Privacy/            # ZK-KYC, Merkle Trees, Delegated Proofs (v2.4.0+v2.6.0)
├── Commerce/           # SBT, Merchants, Attestations (v2.4.0)
├── TrustCert/          # W3C VCs, Certificate Authority (v2.4.0)
├── Relayer/            # ERC-4337 Gas Abstraction, Smart Accounts (v2.6.0)
├── CrossChain/         # Bridge protocols (Wormhole/LayerZero/Axelar), cross-chain swaps (v3.0.0)
├── DeFi/               # DEX aggregation (Uniswap/Aave/Curve/Lido), flash loans (v3.0.0)
├── FinancialInstitution/ # BaaS: Partner APIs, SDKs, Widgets, Billing, Marketplace (v2.9.0)
├── AgentProtocol/      # AI agent commerce (AP2 & A2A)
├── AI/                 # AI Framework, MCP tools, ML Anomaly Detection
├── Monitoring/         # Distributed tracing, metrics
└── ... (41 domains total)
```

## Key Architectural Patterns

### 1. Event Sourcing (Event Store v2 — v4.0.0+)
- All major domains use event sourcing with dedicated event tables
- Event projections for read models
- Aggregates for business logic encapsulation
- Example: `TransactionAggregate`, `StablecoinAggregate`
- **Event Store v2**: EventRouter for namespace-based domain table routing (21 domains)
- **Migration Tooling**: Batch migration with validation (event:migrate, event:migrate:rollback)
- **Schema Evolution**: Chained upcasters (EventUpcastingService, EventVersionRegistry)

### 2. Workflow & Saga Pattern
- Laravel Workflow with Waterline for complex operations
- Saga pattern for distributed transactions with compensation
- Human task integration for approvals
- Example workflows:
  - `OrderMatchingWorkflow`
  - `LoanApplicationWorkflow`
  - `ProcessOpenBankingDepositWorkflow`

### 3. Service Layer Pattern
- Interface-based dependency injection
- Environment-specific implementations (Demo/Sandbox/Production)
- Service providers for automatic binding
- Example: `PaymentServiceInterface` with three implementations

### 4. Factory Pattern
- Used for creating blockchain connectors
- Supports multiple blockchains dynamically
- Example: `BlockchainConnectorFactory`

### 5. GraphQL API (v4.0.0+)
- **Lighthouse-PHP** foundation with custom @tenant directive
- 33 domains: Account, AgentProtocol, AI, Asset, Banking, Basket, Batch, CardIssuance, Cgo, Commerce, Compliance, CrossChain, Custodian, DeFi, Exchange, FinancialInstitution, Fraud, Governance, KeyManagement, Lending, Mobile, MobilePayment, Payment, Privacy, Product, RegTech, Regulatory, Relayer, Stablecoin, Treasury, TrustCert, User, Wallet
- DataLoaders for N+1 prevention, real-time subscriptions
- GraphQL security middleware (depth limiting, complexity analysis)

### 6. Event Streaming (v5.0.0+)
- **Redis Streams** publisher/consumer for real-time event distribution
- Live Dashboard with 5 metrics endpoints for system monitoring
- Notification System with 5 channels for alerts

### 7. Plugin Marketplace (v4.0.0+)
- PluginManager with semver dependency resolver
- Permission sandbox, security scanner
- Marketplace REST API, Filament admin panel, 6 Artisan commands

### 8. API Gateway Middleware (v5.0.0+)
- Centralized request routing and authentication
- Rate limiting, request transformation
- Unified API entry point

## Technology Stack

### Backend
- **PHP 8.4+** with strict typing
- **Laravel 12** framework
- **MySQL 8.0** for primary database
- **Redis** for caching and queues

### Event & Workflow
- **Spatie Event Sourcing** for event-driven architecture
- **Laravel Workflow** with Waterline for orchestration
- **Laravel Horizon** for queue management

### Admin & API
- **Filament 3.0** for admin panel
- **L5-Swagger** for OpenAPI documentation
- **Laravel Sanctum** for API authentication

### Testing
- **Pest PHP** with parallel testing support
- **PHPStan Level 8** for static analysis (upgraded in v1.1.0)
- **PHP-CS-Fixer** for code standards
- **Mockery** for mocking

### CI/CD
- **GitHub Actions** for continuous integration
- **Docker** support for containerization
- **Environment-based deployment** (demo/staging/production)

## Multi-Asset Support
- Primary currencies: USD, EUR, GBP
- Custom token: GCU (Governance Currency Unit)
- GCU Basket composition for stability
- Real-time exchange rate management

## Security Features
- Defense in depth architecture
- Zero trust security model
- Comprehensive audit logging
- Role-based access control (RBAC)
- KYC/AML compliance built-in

## Performance Optimizations
- Database query optimization with eager loading
- Redis caching for frequently accessed data
- Queue-based processing for heavy operations
- Horizontal scaling support

## Current Development Focus (v5.0.0)
- **Event Streaming**: Redis Streams publisher/consumer for real-time event distribution
- **Live Dashboard**: 5 metrics endpoints for real-time system monitoring
- **Notification System**: 5 channels for alerts and notifications
- **API Gateway Middleware**: Centralized request routing, rate limiting, auth

### Recently Completed
- v5.0.0: Event Streaming (Redis Streams), Live Dashboard, Notification System, API Gateway Middleware (MAJOR)
- v4.3.0: GraphQL Fraud/Banking/Mobile/TrustCert domains, CLI commands, GraphQL security
- v4.2.0: Real-time subscriptions, plugin hooks, webhook/audit plugins
- v4.1.0: GraphQL expansion to 10 domains, projector health
- v4.0.0: Event Store v2, GraphQL API (Lighthouse-PHP), Plugin Marketplace
- v3.5.0: Compliance Certification (SOC 2, PCI DSS, Multi-Region, GDPR Enhanced)
- v3.3.0: Event Store Optimization & Observability
- v3.2.0: Production Readiness & Plugin Architecture
- v3.1.0: Consolidation, Documentation & UI Completeness
- v3.0.0: Cross-Chain & DeFi (Wormhole, LayerZero, Axelar, Uniswap, Aave, Curve, Lido)

### Historical Milestones
- v1.1.0: PHPStan Level 8 compliance, 22 Behat E2E features
- v2.0.0: Multi-tenancy with stancl/tenancy (9 phases)
- v2.2.0: Mobile backend (device mgmt, biometrics, push)
- v2.4.0: Privacy & Identity (Shamir's Secret Sharing, ZK-KYC, Commerce, TrustCert)
- v2.6.0: Privacy Layer & ERC-4337 (Merkle Trees, Smart Accounts, Gas Station)
- v2.7.0: Mobile Payment API (Payment Intents, Passkeys, P2P Transfers)
- v2.8.0: AI Query & RegTech (MiFID II, MiCA, Travel Rule)
- v3.0.0: Cross-Chain & DeFi (Wormhole, LayerZero, Axelar, Uniswap, Aave, Curve, Lido)
- v3.5.0: Compliance Certification (SOC 2 Type II, PCI DSS, Multi-Region, GDPR Enhanced)
- v4.0.0: Architecture Evolution (Event Store v2, GraphQL API, Plugin Marketplace)
- v4.1.0: GraphQL expansion to 10 domains, projector health
- v4.2.0: Real-time subscriptions, plugin hooks, webhook/audit plugins
- v4.3.0: GraphQL Fraud/Banking/Mobile/TrustCert domains, CLI commands, GraphQL security
- v5.0.0: Event Streaming (Redis Streams), Live Dashboard, Notification System, API Gateway Middleware (MAJOR)