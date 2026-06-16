<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Verifies all compiled ZK circuit files against the SHA-256 manifest.
 *
 * Usage:
 *   php artisan zk:verify-circuits
 *   php artisan zk:verify-circuits --circuit=age_check
 *   php artisan zk:verify-circuits --generate  # Generate/update manifest from current files
 */
class ZkVerifyCircuitsCommand extends Command
{
    /** @var string */
    protected $signature = 'zk:verify-circuits
        {--circuit= : Verify a specific circuit (e.g. age_check)}
        {--generate : Generate or update the manifest from current circuit files}';

    /** @var string */
    protected $description = 'Verify ZK circuit file integrity against the SHA-256 manifest';

    /**
     * Known circuit artifact extensions to include in manifest.
     *
     * @var array<string>
     */
    private const ARTIFACT_EXTENSIONS = ['wasm', 'zkey', 'vkey.json'];

    public function handle(): int
    {
        $circuitDirectory = (string) config('privacy.zk.circuit_directory', storage_path('app/circuits'));
        $manifestPath = $circuitDirectory . '/circuit_manifest.json';

        if ($this->option('generate')) {
            return $this->generateManifest($circuitDirectory, $manifestPath);
        }

        return $this->verifyManifest($circuitDirectory, $manifestPath, (string) ($this->option('circuit') ?? ''));
    }

    /**
     * Generate or update the manifest from current circuit files.
     */
    private function generateManifest(string $circuitDirectory, string $manifestPath): int
    {
        if (! is_dir($circuitDirectory)) {
            $this->error("Circuit directory not found: {$circuitDirectory}");

            return self::FAILURE;
        }

        $circuitMapping = (array) config('privacy.zk.circuit_mapping', []);
        $circuitNames = array_values($circuitMapping);

        if (empty($circuitNames)) {
            $this->warn('No circuits configured in privacy.zk.circuit_mapping');

            return self::FAILURE;
        }

        $manifest = [];

        foreach ($circuitNames as $circuitName) {
            $circuitFiles = [];

            foreach (self::ARTIFACT_EXTENSIONS as $ext) {
                $filename = "{$circuitName}.{$ext}";
                $filePath = $circuitDirectory . '/' . $filename;

                if (file_exists($filePath)) {
                    $circuitFiles[$filename] = hash_file('sha256', $filePath);
                    $this->line("  Hashed: {$filename}");
                }
            }

            if (! empty($circuitFiles)) {
                $manifest[$circuitName] = $circuitFiles;
            }
        }

        if (empty($manifest)) {
            $this->warn('No circuit artifact files found — manifest not written');

            return self::FAILURE;
        }

        file_put_contents($manifestPath, (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        $this->info("Manifest written to: {$manifestPath}");
        $this->info(sprintf('Manifest covers %d circuit(s)', count($manifest)));

        return self::SUCCESS;
    }

    /**
     * Verify existing circuit files against the manifest.
     */
    private function verifyManifest(string $circuitDirectory, string $manifestPath, string $circuitFilter): int
    {
        if (! file_exists($manifestPath)) {
            $this->warn('Circuit manifest not found. Run with --generate to create one.');
            $this->line("Expected at: {$manifestPath}");

            return self::FAILURE;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest) || empty($manifest)) {
            $this->error('Manifest is empty or malformed');

            return self::FAILURE;
        }

        $circuits = empty($circuitFilter) ? $manifest : array_intersect_key($manifest, [$circuitFilter => true]);

        if (empty($circuits)) {
            $this->error("Circuit '{$circuitFilter}' not found in manifest");

            return self::FAILURE;
        }

        $passCount = 0;
        $failCount = 0;

        foreach ($circuits as $circuitName => $files) {
            $this->line("Checking circuit: <info>{$circuitName}</info>");

            foreach ($files as $filename => $expectedHash) {
                $filePath = $circuitDirectory . '/' . $filename;

                if (! file_exists($filePath)) {
                    $this->error("  MISSING: {$filename}");
                    $failCount++;

                    continue;
                }

                $actualHash = hash_file('sha256', $filePath);

                if ($actualHash === $expectedHash) {
                    $this->line("  <info>OK</info>: {$filename}");
                    $passCount++;
                } else {
                    $this->error("  MISMATCH: {$filename}");
                    $this->line("    expected: {$expectedHash}");
                    $this->line("    actual:   {$actualHash}");
                    $failCount++;
                }
            }
        }

        $this->newLine();

        if ($failCount === 0) {
            $this->info("All {$passCount} circuit file(s) verified successfully.");

            return self::SUCCESS;
        }

        $this->error("{$failCount} file(s) failed integrity check ({$passCount} passed).");

        return self::FAILURE;
    }
}
