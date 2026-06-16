<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Services;

use App\Domain\Privacy\Contracts\ZkProverInterface;
use App\Domain\Privacy\Enums\ProofType;
use App\Domain\Privacy\Exceptions\CircuitNotFoundException;
use App\Domain\Privacy\Exceptions\SnarkjsException;
use App\Domain\Privacy\ValueObjects\ZkProof;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Production ZK prover that wraps snarkjs CLI via Symfony Process.
 *
 * Maps ProofType to circuit name via config, then runs:
 *   snarkjs groth16 prove <circuit.zkey> <input.json> <proof.json> <public.json>
 *   snarkjs groth16 verify <verification_key.json> <public.json> <proof.json>
 */
class SnarkjsProverService implements ZkProverInterface
{
    private readonly string $snarkjsBinary;

    private readonly string $circuitDirectory;

    private readonly int $timeoutSeconds;

    /** @var array<string, string> */
    private readonly array $circuitMapping;

    public function __construct()
    {
        $this->snarkjsBinary = (string) config('privacy.zk.snarkjs_binary', 'snarkjs');
        $this->circuitDirectory = (string) config('privacy.zk.circuit_directory', storage_path('app/circuits'));
        $this->timeoutSeconds = (int) config('privacy.zk.snarkjs_timeout_seconds', 120);
        $this->circuitMapping = (array) config('privacy.zk.circuit_mapping', []);
    }

    public function generateProof(
        ProofType $type,
        array $privateInputs,
        array $publicInputs,
    ): ZkProof {
        $circuitName = $this->resolveCircuitName($type);
        $this->validateCircuitFiles($circuitName);

        if (! $this->verifyCircuitIntegrity($circuitName)) {
            throw new RuntimeException(
                "Circuit integrity check failed for: {$circuitName}. Files may be corrupted or tampered."
            );
        }

        // Acquire a concurrency slot — prevents CPU/memory exhaustion from unbounded parallel proofs
        $maxConcurrent = (int) config('privacy.zk.max_concurrent_proofs', 3);
        $slot = null;

        for ($i = 0; $i < $maxConcurrent; $i++) {
            $lock = Cache::lock("zk_proof_slot:{$i}", 120);

            if ($lock->get()) {
                $slot = $lock;
                break;
            }
        }

        if ($slot === null) {
            throw new RuntimeException('ZK proof generation at capacity — try again shortly');
        }

        $constraintCount = $this->getConstraintCount($circuitName);

        Log::info('Starting snarkjs proof generation', [
            'proof_type'       => $type->value,
            'circuit'          => $circuitName,
            'constraint_count' => $constraintCount,
        ]);

        $startTime = microtime(true);

        // Temp file handles initialised to false so the finally block is safe even if
        // tempnam() fails partway through allocation.
        $inputFile = false;
        $proofFile = false;
        $publicFile = false;

        try {
            // Write input JSON to temp file — allocation inside try so finally always runs cleanup
            $inputFile = tempnam(sys_get_temp_dir(), 'zk_input_');
            $proofFile = tempnam(sys_get_temp_dir(), 'zk_proof_');
            $publicFile = tempnam(sys_get_temp_dir(), 'zk_public_');

            if ($inputFile === false || $proofFile === false || $publicFile === false) {
                throw new RuntimeException('Failed to create temporary files for ZK proof generation');
            }

            $allInputs = array_merge($privateInputs, $publicInputs);
            file_put_contents($inputFile, json_encode($allInputs, JSON_THROW_ON_ERROR));

            $wasmPath = $this->getCircuitPath($circuitName, 'wasm');
            $zkeyPath = $this->getCircuitPath($circuitName, 'zkey');

            // Run snarkjs groth16 prove
            $process = new Process([
                $this->snarkjsBinary,
                'groth16', 'prove',
                $zkeyPath,
                $inputFile,
                $proofFile,
                $publicFile,
            ]);
            $process->setTimeout($this->timeoutSeconds);
            $process->run();

            if (! $process->isSuccessful()) {
                if ($process->isStarted() && ! $process->isTerminated()) {
                    throw SnarkjsException::processTimeout($circuitName, $this->timeoutSeconds);
                }
                throw SnarkjsException::processFailed($circuitName, $process->getErrorOutput());
            }

            // Read proof output
            $proofJson = file_get_contents($proofFile);
            if ($proofJson === false || $proofJson === '') {
                throw SnarkjsException::invalidOutput($circuitName, 'Empty proof output');
            }

            $proofData = base64_encode($proofJson);
            $createdAt = new DateTimeImmutable();
            $validityDays = (int) config('privacy.zk.proof_validity_days', 90);
            $expiresAt = $createdAt->modify("+{$validityDays} days");

            $provingTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('snarkjs proof generation complete', [
                'proof_type'       => $type->value,
                'circuit'          => $circuitName,
                'proving_time_ms'  => $provingTimeMs,
                'constraint_count' => $constraintCount,
            ]);

            return new ZkProof(
                type: $type,
                proof: $proofData,
                publicInputs: $publicInputs,
                verifierAddress: $this->getVerifierAddress($type),
                createdAt: $createdAt,
                expiresAt: $expiresAt,
                metadata: [
                    'provider'         => $this->getProviderName(),
                    'circuit'          => $circuitName,
                    'circuit_version'  => config("privacy.zk.circuit_versions.{$type->value}", '1.0.0'),
                    'proving_time_ms'  => $provingTimeMs,
                    'constraint_count' => $constraintCount,
                ],
            );
        } finally {
            // Securely delete temp files containing private inputs — runs on both success and failure
            if ($inputFile !== false) {
                @unlink($inputFile);
            }

            if ($proofFile !== false) {
                @unlink($proofFile);
            }

            if ($publicFile !== false) {
                @unlink($publicFile);
            }

            $slot->release();
        }
    }

    public function verifyProof(ZkProof $proof): bool
    {
        if ($proof->isExpired()) {
            return false;
        }

        $circuitName = $this->resolveCircuitName($proof->type);
        $vkeyPath = $this->getCircuitPath($circuitName, 'vkey.json');

        if (! file_exists($vkeyPath)) {
            Log::warning('Verification key not found, cannot verify', ['circuit' => $circuitName]);

            return false;
        }

        // Write proof and public inputs to temp files
        $proofFile = tempnam(sys_get_temp_dir(), 'zk_verify_proof_');
        $publicFile = tempnam(sys_get_temp_dir(), 'zk_verify_public_');

        try {
            $decodedProof = base64_decode($proof->proof, true);
            if ($decodedProof === false) {
                return false;
            }

            file_put_contents($proofFile, $decodedProof);
            file_put_contents($publicFile, json_encode($proof->publicInputs, JSON_THROW_ON_ERROR));

            // Run snarkjs groth16 verify
            $process = new Process([
                $this->snarkjsBinary,
                'groth16', 'verify',
                $vkeyPath,
                $publicFile,
                $proofFile,
            ]);
            $process->setTimeout($this->timeoutSeconds);
            $process->run();

            return $process->isSuccessful();
        } finally {
            @unlink($proofFile);
            @unlink($publicFile);
        }
    }

    public function getVerifierAddress(ProofType $type): string
    {
        return (string) config("privacy.zk.verifier_addresses.{$type->value}", '0x' . str_repeat('0', 40));
    }

    public function supportsProofType(ProofType $type): bool
    {
        return isset($this->circuitMapping[$type->value])
            || $type !== ProofType::CUSTOM;
    }

    public function getProviderName(): string
    {
        return 'snarkjs';
    }

    /**
     * Get the constraint count for a compiled circuit by parsing R1CS info.
     *
     * Returns null if the r1cs file does not exist or snarkjs is unavailable.
     *
     * @param string $circuitName The circuit name
     */
    public function getConstraintCount(string $circuitName): ?int
    {
        $r1csPath = $this->getCircuitPath($circuitName, 'r1cs');

        if (! file_exists($r1csPath)) {
            return null;
        }

        $process = new Process([
            $this->snarkjsBinary,
            'r1cs', 'info',
            $r1csPath,
        ]);
        $process->setTimeout($this->timeoutSeconds);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = $process->getOutput();

        if (preg_match('/# of Constraints:\s*(\d+)/i', $output, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Resolve the circuit name for a proof type.
     */
    private function resolveCircuitName(ProofType $type): string
    {
        $circuitName = $this->circuitMapping[$type->value] ?? null;

        if ($circuitName === null) {
            throw CircuitNotFoundException::unmappedProofType($type->value);
        }

        return $circuitName;
    }

    /**
     * Get the file path for a circuit artifact.
     */
    private function getCircuitPath(string $circuitName, string $extension): string
    {
        return $this->circuitDirectory . '/' . $circuitName . '.' . $extension;
    }

    /**
     * Verify circuit file integrity against the SHA-256 manifest.
     *
     * Returns true when the manifest is absent (first-run / CI) or when all listed
     * hashes match. Returns false when a hash mismatch is detected, indicating
     * possible file corruption or tampering.
     */
    private function verifyCircuitIntegrity(string $circuitName): bool
    {
        $manifestPath = storage_path('app/circuits/circuit_manifest.json');

        if (! file_exists($manifestPath)) {
            Log::warning('Circuit manifest not found — skipping integrity check', [
                'circuit'       => $circuitName,
                'manifest_path' => $manifestPath,
            ]);

            return true; // Don't block if manifest doesn't exist yet
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        $circuitFiles = $manifest[$circuitName] ?? null;

        if ($circuitFiles === null) {
            return true; // Unknown circuit, skip
        }

        foreach ($circuitFiles as $filename => $expectedHash) {
            $filePath = storage_path("app/circuits/{$filename}");

            if (! file_exists($filePath)) {
                Log::error('Circuit file missing during integrity check', [
                    'circuit'  => $circuitName,
                    'filename' => $filename,
                ]);

                return false;
            }

            $actualHash = hash_file('sha256', $filePath);

            if ($actualHash !== $expectedHash) {
                Log::error('Circuit file integrity check failed — hash mismatch', [
                    'circuit'       => $circuitName,
                    'filename'      => $filename,
                    'expected_hash' => $expectedHash,
                    'actual_hash'   => $actualHash,
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * Validate that required circuit files (.zkey, .wasm) exist.
     *
     * @throws CircuitNotFoundException If the .zkey file is missing
     * @throws RuntimeException If the .wasm file is missing
     */
    private function validateCircuitFiles(string $circuitName): void
    {
        $zkeyPath = $this->getCircuitPath($circuitName, 'zkey');
        if (! file_exists($zkeyPath)) {
            throw CircuitNotFoundException::zkeyNotFound($circuitName, $zkeyPath);
        }

        $wasmPath = $this->getCircuitPath($circuitName, 'wasm');
        if (! file_exists($wasmPath)) {
            throw new RuntimeException(
                "Circuit artifacts not found for: {$circuitName}. Run php artisan zk:setup --circuit={$circuitName}"
            );
        }
    }
}
