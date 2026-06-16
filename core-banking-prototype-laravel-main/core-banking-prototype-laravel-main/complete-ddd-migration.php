<?php

// Complete DDD migration script

// Models that were already moved (need to fix backward compatibility proxies)
$alreadyMovedModels = [
    'CgoInvestment',
    'CgoPricingRound',
    'CgoRefund',
    'FraudCase',
    'FraudRule',
    'FraudScore',
    'Loan',
    'LoanApplication',
    'LoanCollateral',
    'LoanRepayment',
    'PaymentDeposit',
    'PaymentWithdrawal',
];

// Batch domain models
$batchModels = [
    'BatchEvent' => 'app/Domain/Batch/Models/BatchEvent.php',
    'BatchJobItem' => 'app/Domain/Batch/Models/BatchJobItem.php',
];

// Cgo domain models
$cgoModels = [
    'CgoEvent' => 'app/Domain/Cgo/Models/CgoEvent.php',
    'CgoNotification' => 'app/Domain/Cgo/Models/CgoNotification.php',
];

// Additional models to move
$additionalMigrations = [
    // Webhook domain (new)
    'app/Models/Webhook.php' => 'app/Domain/Webhook/Models/Webhook.php',
    'app/Models/WebhookDelivery.php' => 'app/Domain/Webhook/Models/WebhookDelivery.php',
];

// Console commands that need subdirectories
$liquidityPoolCommands = [
    'app/Console/Commands/LiquidityPool/DistributeRewardsCommand.php' => 'app/Domain/Exchange/Console/Commands/LiquidityPool/DistributeRewardsCommand.php',
    'app/Console/Commands/LiquidityPool/RebalancePoolsCommand.php' => 'app/Domain/Exchange/Console/Commands/LiquidityPool/RebalancePoolsCommand.php',
    'app/Console/Commands/LiquidityPool/UpdateMarketMakingCommand.php' => 'app/Domain/Exchange/Console/Commands/LiquidityPool/UpdateMarketMakingCommand.php',
];

// Function to fix backward compatibility proxies
function fixBackwardCompatibilityProxy($modelName, $oldNamespace, $newNamespace)
{
    $filePath = "app/Models/$modelName.php";
    $content = "<?php\n\nnamespace $oldNamespace;\n\n/**\n * @deprecated Use $newNamespace\\$modelName instead\n */\nclass $modelName extends \\$newNamespace\\$modelName\n{\n}";
    file_put_contents($filePath, $content);
    echo "Fixed backward compatibility proxy for $modelName\n";
}

// Function to move file and create proxy
function moveFileAndCreateProxy($source, $destination)
{
    if (! file_exists($source)) {
        echo "Source file not found: $source\n";

        return false;
    }

    // Extract namespaces
    $oldNamespace = 'App\\'.str_replace(['app/', '.php', '/'], ['', '', '\\'], dirname($source));
    $newNamespace = 'App\\'.str_replace(['app/', '.php', '/'], ['', '', '\\'], dirname($destination));
    $className = basename($source, '.php');

    // Ensure destination directory exists
    $destDir = dirname($destination);
    if (! is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    // Read source file
    $content = file_get_contents($source);

    // Update namespace
    $content = preg_replace(
        "/namespace\s+$oldNamespace;/",
        "namespace $newNamespace;",
        $content
    );

    // Write to destination
    file_put_contents($destination, $content);
    echo "Moved: $source -> $destination\n";

    // Create backward compatibility proxy
    $proxyContent = "<?php\n\nnamespace $oldNamespace;\n\n/**\n * @deprecated Use $newNamespace\\$className instead\n */\nclass $className extends \\$newNamespace\\$className\n{\n}";

    file_put_contents($source, $proxyContent);
    echo "Created proxy for: $className\n";

    return true;
}

echo "Starting complete DDD migration...\n\n";

// Fix already moved models
echo "Fixing backward compatibility proxies for already moved models...\n";
foreach ($alreadyMovedModels as $model) {
    $domainMap = [
        'CgoInvestment' => 'Cgo',
        'CgoPricingRound' => 'Cgo',
        'CgoRefund' => 'Cgo',
        'FraudCase' => 'Fraud',
        'FraudRule' => 'Fraud',
        'FraudScore' => 'Fraud',
        'Loan' => 'Lending',
        'LoanApplication' => 'Lending',
        'LoanCollateral' => 'Lending',
        'LoanRepayment' => 'Lending',
        'PaymentDeposit' => 'Payment',
        'PaymentWithdrawal' => 'Payment',
    ];

    $domain = $domainMap[$model];
    fixBackwardCompatibilityProxy($model, 'App\\Models', "App\\Domain\\$domain\\Models");
}

// Move Batch models
echo "\nMoving Batch domain models...\n";
foreach ($batchModels as $model => $destination) {
    moveFileAndCreateProxy("app/Models/$model.php", $destination);
}

// Move Cgo models
echo "\nMoving Cgo domain models...\n";
foreach ($cgoModels as $model => $destination) {
    moveFileAndCreateProxy("app/Models/$model.php", $destination);
}

// Move additional models
echo "\nMoving additional models...\n";
foreach ($additionalMigrations as $source => $destination) {
    moveFileAndCreateProxy($source, $destination);
}

// Move liquidity pool commands
echo "\nMoving liquidity pool commands...\n";
foreach ($liquidityPoolCommands as $source => $destination) {
    moveFileAndCreateProxy($source, $destination);
}

// Create missing directories for domains that need them
$domainsNeedingDirectories = [
    'app/Domain/Stablecoin/Models',
    'app/Domain/Stablecoin/Console/Commands',
    'app/Domain/Webhook/Models',
    'app/Domain/Webhook/Services',
    'app/Domain/Webhook/Console/Commands',
    'app/Domain/Regulatory/Models',
    'app/Domain/Regulatory/Console/Commands',
];

echo "\nCreating missing domain directories...\n";
foreach ($domainsNeedingDirectories as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

echo "\nMigration completed!\n";
echo "\nNext steps:\n";
echo "1. Run: php update-imports.php\n";
echo "2. Run tests to ensure everything works\n";
echo "3. Run PHPStan and PHPCS\n";
