<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Privacy\Services\CircuitCompilationService;
use App\Domain\Privacy\Services\TrustedSetupService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Artisan command to orchestrate full ZK circuit setup.
 *
 * Runs: compile circuit -> download PoT -> groth16 setup -> export vkey -> export Solidity verifier.
 *
 * Usage:
 *   php artisan zk:setup --circuit=age_check
 *   php artisan zk:setup --all
 */
class ZkSetupCommand extends Command
{
    /** @var string */
    protected $signature = 'zk:setup
        {--circuit= : Circuit name to set up (e.g. age_check)}
        {--all : Set up all configured circuits}
        {--skip-compile : Skip circuit compilation (use existing .r1cs/.wasm)}
        {--ptau-power=14 : Powers of Tau power parameter}';

    /** @var string */
    protected $description = 'Compile Circom circuits and run Groth16 trusted setup';

    /**
     * Available circuit names.
     *
     * @var array<string>
     */
    private const CIRCUITS = [
        'age_check',
        'residency_check',
        'kyc_tier_check',
        'sanctions_check',
        'income_range_check',
    ];

    public function handle(): int
    {
        $circuitName = $this->option('circuit');
        $all = (bool) $this->option('all');
        $skipCompile = (bool) $this->option('skip-compile');
        $ptauPower = (int) $this->option('ptau-power');

        if (! $all && empty($circuitName)) {
            $this->error('Please specify --circuit=<name> or --all');
            $this->info('Available circuits: ' . implode(', ', self::CIRCUITS));

            return self::FAILURE;
        }

        $circuits = $all ? self::CIRCUITS : [(string) $circuitName];

        $compiler = new CircuitCompilationService();
        $setup = new TrustedSetupService();

        // Step 1: Download Powers of Tau
        $this->info('Step 1: Downloading Powers of Tau...');

        try {
            $ptauPath = $setup->downloadPowersOfTau($ptauPower);
            $this->info("  Powers of Tau ready: {$ptauPath}");
        } catch (RuntimeException $e) {
            $this->error("  Failed to download Powers of Tau: {$e->getMessage()}");

            return self::FAILURE;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($circuits as $circuit) {
            $this->newLine();
            $this->info("Setting up circuit: {$circuit}");
            $this->line(str_repeat('-', 50));

            try {
                // Step 2: Compile circuit
                if (! $skipCompile) {
                    $this->info('  Step 2: Compiling Circom circuit...');
                    $compileResult = $compiler->compile($circuit);
                    $this->info("  R1CS: {$compileResult['r1cs_path']}");
                    $this->info("  WASM: {$compileResult['wasm_path']}");

                    // Show constraint count
                    $constraints = $compiler->getConstraintCount($circuit);
                    if ($constraints > 0) {
                        $this->info("  Constraints: {$constraints}");
                    }
                } else {
                    $this->info('  Step 2: Skipping compilation (--skip-compile)');
                    if (! $compiler->artifactsExist($circuit)) {
                        $this->error("  Compiled artifacts not found for: {$circuit}");
                        $failCount++;

                        continue;
                    }
                }

                // Step 3: Groth16 setup
                $this->info('  Step 3: Running Groth16 trusted setup...');
                $setupResult = $setup->setupCircuit($circuit);
                $this->info("  Zkey: {$setupResult['zkey_path']}");
                $this->info("  Vkey: {$setupResult['vkey_path']}");

                // Step 4: Export Solidity verifier
                $this->info('  Step 4: Exporting Solidity verifier...');
                $solPath = $setup->exportSolidityVerifier($circuit);
                $this->info("  Verifier: {$solPath}");

                $this->info("  Circuit '{$circuit}' setup complete.");
                $successCount++;
            } catch (RuntimeException $e) {
                $this->error("  Setup failed for '{$circuit}': {$e->getMessage()}");
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("Setup complete: {$successCount} succeeded, {$failCount} failed.");

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
