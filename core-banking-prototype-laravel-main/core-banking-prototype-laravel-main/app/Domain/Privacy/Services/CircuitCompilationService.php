<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Compiles Circom 2.x circuit source files into R1CS, WASM, and SYM artifacts.
 *
 * Wraps the `circom` CLI binary via Symfony Process.
 */
class CircuitCompilationService
{
    private readonly string $circomBinary;

    private readonly string $snarkjsBinary;

    private readonly string $circuitDirectory;

    private readonly int $timeoutSeconds;

    public function __construct()
    {
        $this->circomBinary = (string) config('privacy.zk.circom_binary', 'circom');
        $this->snarkjsBinary = (string) config('privacy.zk.snarkjs_binary', 'snarkjs');
        $this->circuitDirectory = (string) config('privacy.zk.circuit_directory', storage_path('app/circuits'));
        $this->timeoutSeconds = (int) config('privacy.zk.snarkjs_timeout_seconds', 120);
    }

    /**
     * Compile a Circom circuit into R1CS, WASM, and SYM artifacts.
     *
     * Runs: circom <name>.circom --r1cs --wasm --sym -o <outputDir>
     *
     * @param string $circuitName The circuit name (e.g. 'age_check')
     *
     * @return array{r1cs_path: string, wasm_path: string, sym_path: string}
     *
     * @throws RuntimeException If compilation fails
     */
    public function compile(string $circuitName): array
    {
        $circomPath = $this->circuitDirectory . "/{$circuitName}.circom";

        if (! file_exists($circomPath)) {
            throw new RuntimeException(
                "Circom source file not found for circuit '{$circuitName}' at: {$circomPath}"
            );
        }

        Log::info('Compiling Circom circuit', ['circuit' => $circuitName, 'source' => $circomPath]);

        // circom <name>.circom --r1cs --wasm --sym -o <outputDir>
        $process = new Process([
            $this->circomBinary,
            $circomPath,
            '--r1cs',
            '--wasm',
            '--sym',
            '-o', $this->circuitDirectory,
        ]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                "Circom compilation failed for circuit '{$circuitName}': " . $process->getErrorOutput()
            );
        }

        // Circom outputs to <outputDir>/<circuitName>.r1cs, <outputDir>/<circuitName>_js/<circuitName>.wasm
        $r1csPath = $this->circuitDirectory . "/{$circuitName}.r1cs";
        $wasmDir = $this->circuitDirectory . "/{$circuitName}_js";
        $wasmPath = $wasmDir . "/{$circuitName}.wasm";
        $symPath = $this->circuitDirectory . "/{$circuitName}.sym";

        // Move WASM to flat structure for easier access
        $flatWasmPath = $this->circuitDirectory . "/{$circuitName}.wasm";
        if (file_exists($wasmPath) && ! file_exists($flatWasmPath)) {
            copy($wasmPath, $flatWasmPath);
        }

        Log::info('Circom compilation complete', [
            'circuit'   => $circuitName,
            'r1cs_path' => $r1csPath,
            'wasm_path' => $flatWasmPath,
            'sym_path'  => $symPath,
        ]);

        return [
            'r1cs_path' => $r1csPath,
            'wasm_path' => $flatWasmPath,
            'sym_path'  => $symPath,
        ];
    }

    /**
     * Get the constraint count for a compiled circuit.
     *
     * Runs: snarkjs r1cs info <r1cs>
     *
     * @param string $circuitName The circuit name
     *
     * @return int The number of constraints, or 0 if r1cs info cannot be read
     */
    public function getConstraintCount(string $circuitName): int
    {
        $r1csPath = $this->circuitDirectory . "/{$circuitName}.r1cs";

        if (! file_exists($r1csPath)) {
            return 0;
        }

        $process = new Process([
            $this->snarkjsBinary,
            'r1cs', 'info',
            $r1csPath,
        ]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('Failed to read R1CS info', [
                'circuit' => $circuitName,
                'error'   => $process->getErrorOutput(),
            ]);

            return 0;
        }

        $output = $process->getOutput();

        // Parse constraint count from snarkjs output:
        // "# of Constraints: 1234"
        if (preg_match('/# of Constraints:\s*(\d+)/i', $output, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Check whether compiled artifacts (.r1cs and .wasm) exist for a circuit.
     *
     * @param string $circuitName The circuit name
     */
    public function artifactsExist(string $circuitName): bool
    {
        $r1csPath = $this->circuitDirectory . "/{$circuitName}.r1cs";
        $wasmPath = $this->circuitDirectory . "/{$circuitName}.wasm";

        return file_exists($r1csPath) && file_exists($wasmPath);
    }

    /**
     * Get the circuit directory path.
     */
    public function getCircuitDirectory(): string
    {
        return $this->circuitDirectory;
    }

    /**
     * Get the expected file paths for a circuit's compilation artifacts.
     *
     * @param string $circuitName The circuit name
     *
     * @return array{r1cs_path: string, wasm_path: string, sym_path: string}
     */
    public function getArtifactPaths(string $circuitName): array
    {
        return [
            'r1cs_path' => $this->circuitDirectory . "/{$circuitName}.r1cs",
            'wasm_path' => $this->circuitDirectory . "/{$circuitName}.wasm",
            'sym_path'  => $this->circuitDirectory . "/{$circuitName}.sym",
        ];
    }
}
