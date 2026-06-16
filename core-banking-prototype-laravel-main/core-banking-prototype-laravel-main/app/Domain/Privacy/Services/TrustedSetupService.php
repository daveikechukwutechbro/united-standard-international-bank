<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Manages Groth16 trusted setup ceremony artifacts for ZK circuits.
 *
 * Orchestrates:
 *  - Powers of Tau download (Hermez ceremony / demo stub)
 *  - Circuit-specific phase-2 setup (zkey generation)
 *  - Verification key export
 *  - Solidity verifier contract export
 */
class TrustedSetupService
{
    private readonly string $snarkjsBinary;

    private readonly string $circuitDirectory;

    private readonly int $timeoutSeconds;

    private readonly int $ptauPower;

    private readonly string $ptauUrl;

    public function __construct()
    {
        $this->snarkjsBinary = (string) config('privacy.zk.snarkjs_binary', 'snarkjs');
        $this->circuitDirectory = (string) config('privacy.zk.circuit_directory', storage_path('app/circuits'));
        $this->timeoutSeconds = (int) config('privacy.zk.snarkjs_timeout_seconds', 120);
        $this->ptauPower = (int) config('privacy.zk.ceremony.ptau_power', 14);
        $this->ptauUrl = (string) config('privacy.zk.ceremony.ptau_url', 'https://hermez.s3-eu-west-1.amazonaws.com/powersOfTau28_hez_final_14.ptau');
    }

    /**
     * Download or generate Powers of Tau file.
     *
     * In demo/dev, creates a minimal .ptau stub.
     * In production, downloads from the Hermez ceremony.
     *
     * @param int $power The power parameter for the ceremony (default from config)
     *
     * @return string Path to the .ptau file
     */
    public function downloadPowersOfTau(int $power = 0): string
    {
        if ($power === 0) {
            $power = $this->ptauPower;
        }

        $ptauPath = $this->circuitDirectory . "/powersOfTau_{$power}.ptau";

        if (file_exists($ptauPath)) {
            Log::info('Powers of Tau file already exists', ['path' => $ptauPath]);

            return $ptauPath;
        }

        if (! is_dir($this->circuitDirectory)) {
            mkdir($this->circuitDirectory, 0755, true);
        }

        // In production, download from Hermez ceremony
        if (app()->environment('production')) {
            Log::info('Downloading Powers of Tau from Hermez ceremony', [
                'url'   => $this->ptauUrl,
                'power' => $power,
            ]);

            $this->downloadFile($this->ptauUrl, $ptauPath);

            return $ptauPath;
        }

        // For dev/testing: create a minimal .ptau stub via snarkjs
        Log::info('Generating dev Powers of Tau stub', ['power' => $power]);

        $process = new Process([
            $this->snarkjsBinary,
            'powersoftau', 'new', 'bn128', (string) $power,
            $ptauPath,
        ]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            // Fallback: create a minimal placeholder file for environments without snarkjs
            Log::warning('snarkjs not available, creating placeholder .ptau file', [
                'error' => $process->getErrorOutput(),
            ]);
            file_put_contents($ptauPath, json_encode([
                'type'    => 'ptau_stub',
                'power'   => $power,
                'warning' => 'This is a development stub. Use a real .ptau file for production.',
            ]));
        }

        return $ptauPath;
    }

    /**
     * Run Groth16 phase-2 trusted setup for a circuit.
     *
     * Requires compiled circuit artifacts (.r1cs) and a .ptau file.
     *
     * @param string $circuitName The circuit name (e.g. 'age_check')
     *
     * @return array{zkey_path: string, vkey_path: string}
     *
     * @throws RuntimeException If setup fails
     */
    public function setupCircuit(string $circuitName): array
    {
        $r1csPath = $this->circuitDirectory . "/{$circuitName}.r1cs";
        $zkeyPath = $this->circuitDirectory . "/{$circuitName}.zkey";
        $vkeyPath = $this->circuitDirectory . "/{$circuitName}.vkey.json";
        $ptauPath = $this->getPtauPath();

        if (! file_exists($r1csPath)) {
            throw new RuntimeException(
                "R1CS file not found for circuit '{$circuitName}'. Run circuit compilation first. Path: {$r1csPath}"
            );
        }

        if (! file_exists($ptauPath)) {
            throw new RuntimeException(
                "Powers of Tau file not found. Run downloadPowersOfTau() first. Path: {$ptauPath}"
            );
        }

        Log::info('Running Groth16 trusted setup', ['circuit' => $circuitName]);

        // snarkjs groth16 setup <r1cs> <ptau> <zkey>
        $process = new Process([
            $this->snarkjsBinary,
            'groth16', 'setup',
            $r1csPath,
            $ptauPath,
            $zkeyPath,
        ]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                "Groth16 setup failed for circuit '{$circuitName}': " . $process->getErrorOutput()
            );
        }

        // Export verification key
        $this->exportVerificationKey($circuitName);

        Log::info('Groth16 trusted setup complete', [
            'circuit'   => $circuitName,
            'zkey_path' => $zkeyPath,
            'vkey_path' => $vkeyPath,
        ]);

        return [
            'zkey_path' => $zkeyPath,
            'vkey_path' => $vkeyPath,
        ];
    }

    /**
     * Export the verification key from a circuit's zkey file.
     *
     * @param string $circuitName The circuit name
     *
     * @return string Path to the exported verification key JSON
     *
     * @throws RuntimeException If export fails
     */
    public function exportVerificationKey(string $circuitName): string
    {
        $zkeyPath = $this->circuitDirectory . "/{$circuitName}.zkey";
        $vkeyPath = $this->circuitDirectory . "/{$circuitName}.vkey.json";

        if (! file_exists($zkeyPath)) {
            throw new RuntimeException(
                "Zkey file not found for circuit '{$circuitName}'. Run setupCircuit() first. Path: {$zkeyPath}"
            );
        }

        // snarkjs zkey export verificationkey <zkey> <vkey.json>
        $process = new Process([
            $this->snarkjsBinary,
            'zkey', 'export', 'verificationkey',
            $zkeyPath,
            $vkeyPath,
        ]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                "Verification key export failed for circuit '{$circuitName}': " . $process->getErrorOutput()
            );
        }

        return $vkeyPath;
    }

    /**
     * Export a Solidity verifier contract from a circuit's zkey file.
     *
     * @param string $circuitName The circuit name
     *
     * @return string Path to the exported Solidity verifier contract
     *
     * @throws RuntimeException If export fails
     */
    public function exportSolidityVerifier(string $circuitName): string
    {
        $zkeyPath = $this->circuitDirectory . "/{$circuitName}.zkey";
        $solPath = $this->circuitDirectory . "/verifiers/{$circuitName}_verifier.sol";

        if (! file_exists($zkeyPath)) {
            throw new RuntimeException(
                "Zkey file not found for circuit '{$circuitName}'. Run setupCircuit() first. Path: {$zkeyPath}"
            );
        }

        $verifierDir = $this->circuitDirectory . '/verifiers';
        if (! is_dir($verifierDir)) {
            mkdir($verifierDir, 0755, true);
        }

        // snarkjs zkey export solidityverifier <zkey> <verifier.sol>
        $process = new Process([
            $this->snarkjsBinary,
            'zkey', 'export', 'solidityverifier',
            $zkeyPath,
            $solPath,
        ]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                "Solidity verifier export failed for circuit '{$circuitName}': " . $process->getErrorOutput()
            );
        }

        return $solPath;
    }

    /**
     * Get paths to all artifacts for a circuit.
     *
     * @param string $circuitName The circuit name
     *
     * @return array{circom: string, r1cs: string, wasm: string, zkey: string, vkey: string, sol: string}
     */
    public function getCircuitArtifacts(string $circuitName): array
    {
        return [
            'circom' => $this->circuitDirectory . "/{$circuitName}.circom",
            'r1cs'   => $this->circuitDirectory . "/{$circuitName}.r1cs",
            'wasm'   => $this->circuitDirectory . "/{$circuitName}.wasm",
            'zkey'   => $this->circuitDirectory . "/{$circuitName}.zkey",
            'vkey'   => $this->circuitDirectory . "/{$circuitName}.vkey.json",
            'sol'    => $this->circuitDirectory . "/verifiers/{$circuitName}_verifier.sol",
        ];
    }

    /**
     * Check whether all required artifacts exist for a circuit.
     *
     * @param string $circuitName The circuit name
     */
    public function artifactsExist(string $circuitName): bool
    {
        $artifacts = $this->getCircuitArtifacts($circuitName);

        return file_exists($artifacts['zkey'])
            && file_exists($artifacts['vkey'])
            && file_exists($artifacts['wasm']);
    }

    /**
     * Get the path to the Powers of Tau file.
     */
    public function getPtauPath(): string
    {
        return $this->circuitDirectory . "/powersOfTau_{$this->ptauPower}.ptau";
    }

    /**
     * Get the circuit directory path.
     */
    public function getCircuitDirectory(): string
    {
        return $this->circuitDirectory;
    }

    /**
     * Download a file from a URL to a local path.
     *
     * @throws RuntimeException If download fails
     */
    private function downloadFile(string $url, string $destination): void
    {
        $contents = file_get_contents($url);

        if ($contents === false) {
            throw new RuntimeException("Failed to download file from: {$url}");
        }

        $written = file_put_contents($destination, $contents);

        if ($written === false) {
            throw new RuntimeException("Failed to write downloaded file to: {$destination}");
        }
    }
}
