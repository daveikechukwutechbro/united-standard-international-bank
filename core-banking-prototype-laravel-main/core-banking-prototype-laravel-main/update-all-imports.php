<?php

// Comprehensive import update script after DDD migration

$importMappings = [
    // Models
    'App\\Models\\AccountBalance' => 'App\\Domain\\Account\\Models\\AccountBalance',
    'App\\Models\\Ledger' => 'App\\Domain\\Account\\Models\\Ledger',
    'App\\Models\\TransactionProjection' => 'App\\Domain\\Account\\Models\\TransactionProjection',
    'App\\Models\\Transfer' => 'App\\Domain\\Account\\Models\\Transfer',
    'App\\Models\\Turnover' => 'App\\Domain\\Account\\Models\\Turnover',
    
    'App\\Models\\BasketAsset' => 'App\\Domain\\Basket\\Models\\BasketAsset',
    'App\\Models\\BasketComponent' => 'App\\Domain\\Basket\\Models\\BasketComponent',
    'App\\Models\\BasketPerformance' => 'App\\Domain\\Basket\\Models\\BasketPerformance',
    'App\\Models\\BasketValue' => 'App\\Domain\\Basket\\Models\\BasketValue',
    'App\\Models\\ComponentPerformance' => 'App\\Domain\\Basket\\Models\\ComponentPerformance',
    
    'App\\Models\\BatchJob' => 'App\\Domain\\Batch\\Models\\BatchJob',
    'App\\Models\\BatchEvent' => 'App\\Domain\\Batch\\Models\\BatchEvent',
    'App\\Models\\BatchJobItem' => 'App\\Domain\\Batch\\Models\\BatchJobItem',
    
    'App\\Models\\CgoInvestment' => 'App\\Domain\\Cgo\\Models\\CgoInvestment',
    'App\\Models\\CgoPricingRound' => 'App\\Domain\\Cgo\\Models\\CgoPricingRound',
    'App\\Models\\CgoRefund' => 'App\\Domain\\Cgo\\Models\\CgoRefund',
    'App\\Models\\CgoEvent' => 'App\\Domain\\Cgo\\Models\\CgoEvent',
    'App\\Models\\CgoNotification' => 'App\\Domain\\Cgo\\Models\\CgoNotification',
    
    'App\\Models\\AmlScreening' => 'App\\Domain\\Compliance\\Models\\AmlScreening',
    'App\\Models\\AmlScreeningEvent' => 'App\\Domain\\Compliance\\Models\\AmlScreeningEvent',
    'App\\Models\\CustomerRiskProfile' => 'App\\Domain\\Compliance\\Models\\CustomerRiskProfile',
    'App\\Models\\KycDocument' => 'App\\Domain\\Compliance\\Models\\KycDocument',
    'App\\Models\\KycVerification' => 'App\\Domain\\Compliance\\Models\\KycVerification',
    'App\\Models\\SuspiciousActivityReport' => 'App\\Domain\\Compliance\\Models\\SuspiciousActivityReport',
    'App\\Models\\TransactionMonitoringRule' => 'App\\Domain\\Compliance\\Models\\TransactionMonitoringRule',
    
    'App\\Models\\CustodianAccount' => 'App\\Domain\\Custodian\\Models\\CustodianAccount',
    'App\\Models\\CustodianTransfer' => 'App\\Domain\\Custodian\\Models\\CustodianTransfer',
    'App\\Models\\CustodianWebhook' => 'App\\Domain\\Custodian\\Models\\CustodianWebhook',
    
    'App\\Models\\LiquidityPoolEvent' => 'App\\Domain\\Exchange\\Models\\LiquidityPoolEvent',
    
    'App\\Models\\FinancialInstitutionApplication' => 'App\\Domain\\FinancialInstitution\\Models\\FinancialInstitutionApplication',
    'App\\Models\\FinancialInstitutionPartner' => 'App\\Domain\\FinancialInstitution\\Models\\FinancialInstitutionPartner',
    
    'App\\Models\\FraudCase' => 'App\\Domain\\Fraud\\Models\\FraudCase',
    'App\\Models\\FraudRule' => 'App\\Domain\\Fraud\\Models\\FraudRule',
    'App\\Models\\FraudScore' => 'App\\Domain\\Fraud\\Models\\FraudScore',
    'App\\Models\\BehavioralProfile' => 'App\\Domain\\Fraud\\Models\\BehavioralProfile',
    'App\\Models\\DeviceFingerprint' => 'App\\Domain\\Fraud\\Models\\DeviceFingerprint',
    
    'App\\Models\\GcuVote' => 'App\\Domain\\Governance\\Models\\GcuVote',
    'App\\Models\\GcuVotingProposal' => 'App\\Domain\\Governance\\Models\\GcuVotingProposal',
    
    'App\\Models\\Loan' => 'App\\Domain\\Lending\\Models\\Loan',
    'App\\Models\\LoanApplication' => 'App\\Domain\\Lending\\Models\\LoanApplication',
    'App\\Models\\LoanCollateral' => 'App\\Domain\\Lending\\Models\\LoanCollateral',
    'App\\Models\\LoanRepayment' => 'App\\Domain\\Lending\\Models\\LoanRepayment',
    'App\\Models\\LendingEvent' => 'App\\Domain\\Lending\\Models\\LendingEvent',
    
    'App\\Models\\PaymentDeposit' => 'App\\Domain\\Payment\\Models\\PaymentDeposit',
    'App\\Models\\PaymentWithdrawal' => 'App\\Domain\\Payment\\Models\\PaymentWithdrawal',
    'App\\Models\\PaymentTransaction' => 'App\\Domain\\Payment\\Models\\PaymentTransaction',
    
    'App\\Models\\Stablecoin' => 'App\\Domain\\Stablecoin\\Models\\Stablecoin',
    'App\\Models\\StablecoinCollateralPosition' => 'App\\Domain\\Stablecoin\\Models\\StablecoinCollateralPosition',
    'App\\Models\\StablecoinEvent' => 'App\\Domain\\Stablecoin\\Models\\StablecoinEvent',
    'App\\Models\\StablecoinOperation' => 'App\\Domain\\Stablecoin\\Models\\StablecoinOperation',
    
    'App\\Models\\BankAccountModel' => 'App\\Domain\\Banking\\Models\\BankAccountModel',
    'App\\Models\\BankConnectionModel' => 'App\\Domain\\Banking\\Models\\BankConnectionModel',
    'App\\Models\\UserBankPreference' => 'App\\Domain\\Banking\\Models\\UserBankPreference',
    
    'App\\Models\\Webhook' => 'App\\Domain\\Webhook\\Models\\Webhook',
    'App\\Models\\WebhookDelivery' => 'App\\Domain\\Webhook\\Models\\WebhookDelivery',
    
    'App\\Models\\ContactSubmission' => 'App\\Domain\\Contact\\Models\\ContactSubmission',
    'App\\Models\\Subscriber' => 'App\\Domain\\Newsletter\\Models\\Subscriber',
    
    // Services
    'App\\Services\\Cgo\\CgoKycService' => 'App\\Domain\\Cgo\\Services\\CgoKycService',
    'App\\Services\\Cgo\\CoinbaseCommerceService' => 'App\\Domain\\Cgo\\Services\\CoinbaseCommerceService',
    'App\\Services\\Cgo\\InvestmentAgreementService' => 'App\\Domain\\Cgo\\Services\\InvestmentAgreementService',
    'App\\Services\\Cgo\\PaymentVerificationService' => 'App\\Domain\\Cgo\\Services\\PaymentVerificationService',
    'App\\Services\\Cgo\\RefundProcessingService' => 'App\\Domain\\Cgo\\Services\\RefundProcessingService',
    'App\\Services\\Cgo\\StripePaymentService' => 'App\\Domain\\Cgo\\Services\\StripePaymentService',
    'App\\Services\\PaymentGatewayService' => 'App\\Domain\\Payment\\Services\\PaymentGatewayService',
    'App\\Services\\WebhookService' => 'App\\Domain\\Webhook\\Services\\WebhookService',
    
    'App\\Services\\Lending\\DefaultCollateralManagementService' => 'App\\Domain\\Lending\\Services\\DefaultCollateralManagementService',
    'App\\Services\\Lending\\DefaultRiskAssessmentService' => 'App\\Domain\\Lending\\Services\\DefaultRiskAssessmentService',
    'App\\Services\\Lending\\LoanApplicationService' => 'App\\Domain\\Lending\\Services\\LoanApplicationService',
    'App\\Services\\Lending\\MockCreditScoringService' => 'App\\Domain\\Lending\\Services\\MockCreditScoringService',
    
    // Jobs
    'App\\Jobs\\ProcessCustodianWebhook' => 'App\\Domain\\Custodian\\Jobs\\ProcessCustodianWebhook',
    'App\\Jobs\\ProcessWebhookDelivery' => 'App\\Domain\\Webhook\\Jobs\\ProcessWebhookDelivery',
    'App\\Jobs\\VerifyCgoPayment' => 'App\\Domain\\Cgo\\Jobs\\VerifyCgoPayment',
    
    // Listeners
    'App\\Listeners\\CreateAccountForNewUser' => 'App\\Domain\\Account\\Listeners\\CreateAccountForNewUser',
    'App\\Listeners\\HandleCustodianHealthChange' => 'App\\Domain\\Custodian\\Listeners\\HandleCustodianHealthChange',
    
    // Mail
    'App\\Mail\\CgoInvestmentConfirmed' => 'App\\Domain\\Cgo\\Mail\\CgoInvestmentConfirmed',
    'App\\Mail\\CgoInvestmentReceived' => 'App\\Domain\\Cgo\\Mail\\CgoInvestmentReceived',
    'App\\Mail\\CgoNotificationReceived' => 'App\\Domain\\Cgo\\Mail\\CgoNotificationReceived',
    'App\\Mail\\ReconciliationReport' => 'App\\Domain\\Custodian\\Mail\\ReconciliationReport',
    'App\\Mail\\ContactFormSubmission' => 'App\\Domain\\Contact\\Mail\\ContactFormSubmission',
    'App\\Mail\\SubscriberNewsletter' => 'App\\Domain\\Newsletter\\Mail\\SubscriberNewsletter',
    'App\\Mail\\SubscriberWelcome' => 'App\\Domain\\Newsletter\\Mail\\SubscriberWelcome',
    
    // Workflows
    'App\\Workflows\\BlockchainDepositWorkflow' => 'App\\Domain\\Wallet\\Workflows\\BlockchainDepositWorkflow',
    'App\\Workflows\\BlockchainWithdrawalWorkflow' => 'App\\Domain\\Wallet\\Workflows\\BlockchainWithdrawalWorkflow',
    'App\\Workflows\\LoanApplicationWorkflow' => 'App\\Domain\\Lending\\Workflows\\LoanApplicationWorkflow',
    'App\\Workflows\\Activities\\BlockchainDepositActivities' => 'App\\Domain\\Wallet\\Workflows\\Activities\\BlockchainDepositActivities',
    'App\\Workflows\\Activities\\BlockchainWithdrawalActivities' => 'App\\Domain\\Wallet\\Workflows\\Activities\\BlockchainWithdrawalActivities',
    'App\\Workflows\\Activities\\LoanApplicationActivities' => 'App\\Domain\\Lending\\Workflows\\Activities\\LoanApplicationActivities',
    
    // Notifications
    'App\\Notifications\\BankHealthAlert' => 'App\\Domain\\Banking\\Notifications\\BankHealthAlert',
    
    // Console Commands
    'App\\Console\\Commands\\BasketsRebalanceCommand' => 'App\\Domain\\Basket\\Console\\Commands\\BasketsRebalanceCommand',
    'App\\Console\\Commands\\CalculateBasketPerformance' => 'App\\Domain\\Basket\\Console\\Commands\\CalculateBasketPerformance',
    'App\\Console\\Commands\\RebalanceBasketsCommand' => 'App\\Domain\\Basket\\Console\\Commands\\RebalanceBasketsCommand',
    'App\\Console\\Commands\\ShowBasketPerformanceCommand' => 'App\\Domain\\Basket\\Console\\Commands\\ShowBasketPerformanceCommand',
    'App\\Console\\Commands\\CheckBankHealthAlerts' => 'App\\Domain\\Banking\\Console\\Commands\\CheckBankHealthAlerts',
    'App\\Console\\Commands\\MonitorBankHealth' => 'App\\Domain\\Banking\\Console\\Commands\\MonitorBankHealth',
    'App\\Console\\Commands\\SynchronizeCustodianBalances' => 'App\\Domain\\Custodian\\Console\\Commands\\SynchronizeCustodianBalances',
    'App\\Console\\Commands\\PerformDailyReconciliation' => 'App\\Domain\\Custodian\\Console\\Commands\\PerformDailyReconciliation',
    'App\\Console\\Commands\\ProcessSettlements' => 'App\\Domain\\Custodian\\Console\\Commands\\ProcessSettlements',
    'App\\Console\\Commands\\GenerateRegulatoryReports' => 'App\\Domain\\Regulatory\\Console\\Commands\\GenerateRegulatoryReports',
    'App\\Console\\Commands\\RegulatoryManagement' => 'App\\Domain\\Regulatory\\Console\\Commands\\RegulatoryManagement',
    'App\\Console\\Commands\\VerifyCgoPayments' => 'App\\Domain\\Cgo\\Console\\Commands\\VerifyCgoPayments',
    'App\\Console\\Commands\\SetupVotingPolls' => 'App\\Domain\\Governance\\Console\\Commands\\SetupVotingPolls',
    'App\\Console\\Commands\\VotingSetupCommand' => 'App\\Domain\\Governance\\Console\\Commands\\VotingSetupCommand',
    'App\\Console\\Commands\\RetryFailedWebhooks' => 'App\\Domain\\Webhook\\Console\\Commands\\RetryFailedWebhooks',
    'App\\Console\\Commands\\SendNewsletter' => 'App\\Domain\\Newsletter\\Console\\Commands\\SendNewsletter',
    'App\\Console\\Commands\\VerifyTransactionHashes' => 'App\\Domain\\Account\\Console\\Commands\\VerifyTransactionHashes',
    'App\\Console\\Commands\\LiquidityPool\\DistributeRewardsCommand' => 'App\\Domain\\Exchange\\Console\\Commands\\LiquidityPool\\DistributeRewardsCommand',
    'App\\Console\\Commands\\LiquidityPool\\RebalancePoolsCommand' => 'App\\Domain\\Exchange\\Console\\Commands\\LiquidityPool\\RebalancePoolsCommand',
    'App\\Console\\Commands\\LiquidityPool\\UpdateMarketMakingCommand' => 'App\\Domain\\Exchange\\Console\\Commands\\LiquidityPool\\UpdateMarketMakingCommand',
];

// Function to recursively find and update PHP files
function updateImportsInDirectory($directory, $mappings) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $count = 0;
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            
            // Skip vendor and storage directories
            if (strpos($filePath, '/vendor/') !== false || strpos($filePath, '/storage/') !== false) {
                continue;
            }
            
            $content = file_get_contents($filePath);
            $originalContent = $content;
            
            foreach ($mappings as $old => $new) {
                // Replace use statements
                $content = preg_replace(
                    "/use\s+" . preg_quote($old, '/') . "([\s;,])/",
                    "use " . $new . "$1",
                    $content
                );
                
                // Replace in docblocks
                $content = str_replace("@param $old", "@param $new", $content);
                $content = str_replace("@return $old", "@return $new", $content);
                $content = str_replace("@var $old", "@var $new", $content);
                
                // Replace ::class references
                $oldClass = basename(str_replace('\\', '/', $old));
                $content = preg_replace(
                    "/\\\\" . preg_quote($old, '/') . "::class/",
                    "\\" . $new . "::class",
                    $content
                );
                
                // Replace in type hints (careful with this)
                $content = preg_replace(
                    "/(\s|\\(|\\|)" . preg_quote($old, '/') . "(\s|\\)|\\|)/",
                    "$1" . $new . "$2",
                    $content
                );
            }
            
            if ($content !== $originalContent) {
                file_put_contents($filePath, $content);
                $count++;
                echo "Updated: $filePath\n";
            }
        }
    }
    
    return $count;
}

echo "Updating imports after DDD migration...\n\n";

// Update imports in app directory
echo "Updating app directory...\n";
$appCount = updateImportsInDirectory('app', $importMappings);
echo "Updated $appCount files in app directory\n\n";

// Update imports in tests directory
echo "Updating tests directory...\n";
$testCount = updateImportsInDirectory('tests', $importMappings);
echo "Updated $testCount files in tests directory\n\n";

// Update imports in config directory
echo "Updating config directory...\n";
$configCount = updateImportsInDirectory('config', $importMappings);
echo "Updated $configCount files in config directory\n\n";

// Update imports in database directory
echo "Updating database directory...\n";
$databaseCount = updateImportsInDirectory('database', $importMappings);
echo "Updated $databaseCount files in database directory\n\n";

// Update imports in routes directory
echo "Updating routes directory...\n";
$routesCount = updateImportsInDirectory('routes', $importMappings);
echo "Updated $routesCount files in routes directory\n\n";

$totalCount = $appCount + $testCount + $configCount + $databaseCount + $routesCount;
echo "Total files updated: $totalCount\n\n";

echo "Import update completed!\n";