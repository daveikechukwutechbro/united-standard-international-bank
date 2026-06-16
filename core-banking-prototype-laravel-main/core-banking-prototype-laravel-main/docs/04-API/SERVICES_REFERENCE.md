# FinAegis Services Reference

This document provides comprehensive documentation for all backend services in the FinAegis platform.

## Table of Contents
- [Account Services](#account-services)
- [Compliance Services](#compliance-services)
- [Custodian Services](#custodian-services)
- [Governance Services](#governance-services)
- [Performance Services](#performance-services)
- [Banking Services](#banking-services)
- [Asset Management Services](#asset-management-services)
- [Transaction Services](#transaction-services)

## Account Services

### AccountService
**Location**: `app/Domain/Account/Services/AccountService.php`

Handles core account operations including creation, balance management, and account lifecycle.

**Key Methods**:
- `createAccount()` - Creates new user accounts
- `getAccountBalance()` - Retrieves multi-asset balances
- `updateAccountStatus()` - Manages account status changes
- `linkCustodianAccounts()` - Links external custodian accounts

**Events Emitted**:
- `AccountCreated`
- `AccountStatusChanged`
- `BalanceUpdated`

### BankAllocationService  
**Location**: `app/Domain/Account/Services/BankAllocationService.php`

Manages user bank allocation preferences and distribution strategies.

**Key Methods**:
- `getUserAllocation()` - Gets user's bank allocation preferences
- `updateAllocation()` - Updates allocation percentages
- `calculateOptimalAllocation()` - Suggests optimal bank distribution
- `validateAllocation()` - Ensures allocations sum to 100%

**API Endpoints**: `/api/users/{uuid}/bank-allocation/*`

## Compliance Services

### RegulatoryReportingService
**Location**: `app/Domain/Compliance/Services/RegulatoryReportingService.php`

Handles regulatory compliance reporting including CTR, SAR, and KYC reports.

**Key Methods**:
- `generateCTR()` - Generates Currency Transaction Reports
- `generateSAR()` - Creates Suspicious Activity Reports  
- `getComplianceSummary()` - Provides compliance dashboard data
- `generateKycReport()` - Creates KYC status reports

**Reporting Thresholds**:
- CTR: $10,000+ transactions
- SAR: Flagged suspicious patterns
- Enhanced Due Diligence: PEPs and high-risk jurisdictions

**API Endpoints**: `/api/regulatory-reporting/*`

### KycService
**Location**: `app/Domain/Compliance/Services/KycService.php`

Manages Know Your Customer verification processes and document handling.

**Key Methods**:
- `submitDocuments()` - Handles KYC document uploads
- `verifyIdentity()` - Processes identity verification
- `getKycStatus()` - Returns current KYC level and status
- `checkRequirements()` - Validates required documents

**KYC Levels**:
- Basic: Email + phone verification
- Standard: Government ID + address proof
- Enhanced: Additional documentation for high-value accounts

### GdprService
**Location**: `app/Domain/Compliance/Services/GdprService.php`

Handles GDPR compliance including data export, deletion, and consent management.

**Key Methods**:
- `exportUserData()` - Generates complete data export
- `deleteUserData()` - Processes account deletion requests
- `updateConsent()` - Manages user privacy preferences
- `trackDataProcessing()` - Logs data processing activities

## Custodian Services

### SettlementService
**Location**: `app/Domain/Custodian/Services/SettlementService.php`

Manages transaction settlement across multiple custodian banks.

**Key Methods**:
- `settleTransaction()` - Processes transaction settlement
- `reconcileSettlements()` - Validates settlement accuracy
- `handleSettlementFailure()` - Manages failed settlements
- `getSettlementStatus()` - Tracks settlement progress

### MultiCustodianTransferService
**Location**: `app/Domain/Custodian/Services/MultiCustodianTransferService.php`

Coordinates transfers across multiple custodian banks with failover capabilities.

**Key Methods**:
- `executeTransfer()` - Routes transfers to optimal banks
- `handleBankFailover()` - Reroutes during bank outages
- `validateBankHealth()` - Monitors bank availability
- `optimizeTransferRouting()` - Selects best execution path

### BankAlertingService
**Location**: `app/Domain/Custodian/Services/BankAlertingService.php`

Monitors bank health and sends alerts for operational issues.

**Key Methods**:
- `monitorBankHealth()` - Continuous health monitoring
- `sendAlert()` - Dispatches alert notifications
- `createAlertRule()` - Configures monitoring rules
- `acknowledgeAlert()` - Manages alert acknowledgment

**Alert Types**:
- Balance thresholds
- Response time degradation
- API failure rates
- Circuit breaker activation

## Governance Services

### GovernanceService
**Location**: `app/Domain/Governance/Services/GovernanceService.php`

Manages platform governance including polls, voting, and proposal execution.

**Key Methods**:
- `createPoll()` - Creates governance polls
- `executeProposal()` - Implements approved proposals
- `calculateVotingPower()` - Determines user voting weight
- `validateProposal()` - Ensures proposal validity

### VotingTemplateService
**Location**: `app/Domain/Governance/Services/VotingTemplateService.php`

Provides templated voting mechanisms for common governance actions.

**Key Methods**:
- `createBasketCompositionPoll()` - Creates asset allocation votes
- `createConfigurationPoll()` - System configuration changes
- `createFeatureTogglePoll()` - Feature enablement votes
- `applyVoteResults()` - Implements voting outcomes

**Template Types**:
- Basket composition changes
- Fee structure updates
- Feature toggles
- Asset addition/removal

## Performance Services

### TransferOptimizationService
**Location**: `app/Domain/Performance/Services/TransferOptimizationService.php`

Optimizes transfer routing and execution for best performance and cost.

**Key Methods**:
- `optimizeTransferRoute()` - Selects optimal transfer path
- `calculateTransferCost()` - Estimates transfer fees
- `predictTransferTime()` - Estimates completion time
- `analyzeTransferPatterns()` - Identifies optimization opportunities

**Optimization Factors**:
- Transaction costs
- Settlement time
- Bank availability
- Regulatory requirements

## Banking Services

### DailyReconciliationService
**Location**: `app/Domain/Banking/Services/DailyReconciliationService.php`

Performs daily reconciliation of balances across all custodian banks.

**Key Methods**:
- `performDailyReconciliation()` - Executes daily balance checks
- `identifyDiscrepancies()` - Flags balance mismatches
- `generateReconciliationReport()` - Creates reconciliation summaries
- `resolveDiscrepancy()` - Handles balance corrections

**Reconciliation Process**:
1. Fetch balances from all custodians
2. Compare with internal records
3. Flag discrepancies for review
4. Generate detailed reports
5. Execute corrective actions

### CircuitBreakerService
**Location**: `app/Domain/Banking/Services/CircuitBreakerService.php`

Implements circuit breaker patterns for bank API reliability.

**Key Methods**:
- `checkCircuitState()` - Monitors circuit status
- `recordFailure()` - Logs API failures
- `openCircuit()` - Activates circuit breaker
- `attemptReset()` - Tests circuit recovery

**Circuit States**:
- Closed: Normal operation
- Open: All traffic blocked
- Half-Open: Testing recovery

## Asset Management Services

### BasketManagementService
**Location**: `app/Domain/Asset/Services/BasketManagementService.php`

Manages basket composition, rebalancing, and performance tracking.

**Key Methods**:
- `rebalanceBasket()` - Adjusts asset weights
- `calculateBasketValue()` - Computes current basket value
- `trackPerformance()` - Monitors basket performance
- `validateComposition()` - Ensures valid asset weights

### StablecoinService
**Location**: `app/Domain/Asset/Services/StablecoinService.php`

Handles stablecoin operations including minting, burning, and collateral management.

**Key Methods**:
- `mintStablecoin()` - Creates new stablecoin tokens
- `burnStablecoin()` - Destroys stablecoin tokens
- `manageCollateral()` - Handles collateral positions
- `checkLiquidation()` - Monitors liquidation risks

## Transaction Services

### TransactionReversalService
**Location**: `app/Domain/Transaction/Services/TransactionReversalService.php`

Manages transaction reversal requests and compensation workflows.

**Key Methods**:
- `requestReversal()` - Initiates reversal request
- `validateReversalEligibility()` - Checks if reversal is allowed
- `executeReversal()` - Performs transaction reversal
- `trackReversalStatus()` - Monitors reversal progress

**Reversal Criteria**:
- Time limits (typically 24-48 hours)
- Transaction type restrictions
- Approval requirements
- Compliance considerations

### BatchProcessingService
**Location**: `app/Domain/Transaction/Services/BatchProcessingService.php`

Handles batch transaction processing for high-volume operations.

**Key Methods**:
- `createBatch()` - Initializes batch transaction
- `processBatch()` - Executes batch operations
- `validateBatchIntegrity()` - Ensures batch consistency
- `handleBatchFailure()` - Manages partial failures

**Batch Features**:
- Atomic processing
- Partial failure handling
- Progress tracking
- Rollback capabilities

## Cache Services

### TransactionCacheService
**Location**: `app/Domain/Account/Services/Cache/TransactionCacheService.php`

Optimizes transaction data access through intelligent caching.

### AccountCacheService
**Location**: `app/Domain/Account/Services/Cache/AccountCacheService.php`

Manages account data caching for improved performance.

### PollCacheService
**Location**: `app/Domain/Governance/Services/Cache/PollCacheService.php`

Caches governance poll data and voting results.

## Service Integration Patterns

### Event Sourcing Integration
All services emit domain events for audit trails and workflow coordination:

```php
// Example service event emission
$this->eventStore->store(new TransactionCompleted(
    $transaction->uuid,
    $transaction->amount,
    $transaction->asset_code
));
```

### Workflow Integration
Services integrate with Laravel Workflow for complex business processes:

```php
// Example workflow integration
$workflow = new TransferWorkflow($transferData);
$workflow->addStep(new ValidateTransferStep())
         ->addStep(new ExecuteTransferStep())
         ->addCompensation(new ReverseTransferStep());
```

### API Authentication
All services respect API authentication and authorization:

```php
// Services check permissions
if (!$this->authService->canPerformAction($user, 'create_transfer')) {
    throw new UnauthorizedException();
}
```

## Performance Considerations

### Caching Strategy
- Redis for session and temporary data
- Database query result caching
- API response caching for stable data

### Async Processing
- Queue-based processing for heavy operations
- Event-driven architecture for loose coupling
- Background job processing for reports

### Monitoring
- Service health endpoints
- Performance metrics collection
- Error rate monitoring
- Circuit breaker metrics

## Error Handling

### Standard Error Responses
All services follow consistent error handling:

```json
{
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "Account balance insufficient for transaction",
    "details": {
      "required_amount": 10000,
      "available_balance": 5000
    }
  }
}
```

### Compensation Patterns
Services implement compensation for failed operations:

- Transaction reversals
- Balance corrections
- State rollbacks
- Notification cleanup

## Testing Strategy

### Unit Tests
Each service has comprehensive unit test coverage:
- Method-level testing
- Edge case validation
- Error condition testing
- Mock external dependencies

### Integration Tests
Services are tested in realistic scenarios:
- Multi-service workflows
- Database interactions
- External API integration
- Event emission verification

### Performance Tests
Critical services undergo performance testing:
- Load testing for high-volume operations
- Memory usage optimization
- Database query optimization
- Cache effectiveness measurement

## Security Considerations

### Data Protection
- Sensitive data encryption
- PII anonymization
- Secure key management
- Access logging

### API Security
- Authentication token validation
- Rate limiting
- Input sanitization
- SQL injection prevention

### Compliance Security
- GDPR data handling
- PCI DSS for payment data
- SOX compliance for financial records
- Audit trail maintenance