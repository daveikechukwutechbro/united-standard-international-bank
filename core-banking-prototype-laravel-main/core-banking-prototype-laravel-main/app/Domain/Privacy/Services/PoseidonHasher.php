<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Wraps circomlibjs Poseidon hash via Node.js subprocess.
 *
 * Falls back to SHA3-256 with domain separation if Node.js/circomlibjs is unavailable.
 */
class PoseidonHasher
{
    private readonly string $hashAlgorithm;

    private ?bool $poseidonAvailable = null;

    public function __construct()
    {
        $this->hashAlgorithm = (string) config('privacy.merkle.hash_algorithm', 'sha3-256');
    }

    /**
     * Hash two values together (for Merkle tree internal nodes).
     *
     * @param string $left Left child (hex with 0x prefix)
     * @param string $right Right child (hex with 0x prefix)
     * @return string Hash result (hex with 0x prefix)
     */
    public function hash(string $left, string $right): string
    {
        if ($this->hashAlgorithm === 'poseidon' && $this->isAvailable()) {
            return $this->poseidonHash($left, $right);
        }

        return $this->sha3Hash($left, $right);
    }

    /**
     * Check if the Poseidon hasher (via Node.js + circomlibjs) is available.
     */
    public function isAvailable(): bool
    {
        if ($this->poseidonAvailable !== null) {
            return $this->poseidonAvailable;
        }

        try {
            $process = new Process(['node', '-e', 'console.log("ok")']);
            $process->setTimeout(5);
            $process->run();

            $this->poseidonAvailable = $process->isSuccessful();
        } catch (Throwable) {
            $this->poseidonAvailable = false;
        }

        return $this->poseidonAvailable;
    }

    /**
     * Compute Poseidon hash via Node.js subprocess.
     */
    private function poseidonHash(string $left, string $right): string
    {
        $script = <<<'JS'
            const { buildPoseidon } = require("circomlibjs");
            (async () => {
                const poseidon = await buildPoseidon();
                const left = BigInt(process.argv[1]);
                const right = BigInt(process.argv[2]);
                const hash = poseidon([left, right]);
                const result = poseidon.F.toString(hash, 16);
                console.log("0x" + result.padStart(64, "0"));
            })();
            JS;

        $process = new Process(['node', '-e', $script, $left, $right]);
        $process->setTimeout(10);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('Poseidon hash failed, falling back to sha3-256', [
                'error' => $process->getErrorOutput(),
            ]);

            return $this->sha3Hash($left, $right);
        }

        return trim($process->getOutput());
    }

    /**
     * Fallback SHA3-256 hash with domain separation.
     */
    private function sha3Hash(string $left, string $right): string
    {
        // Sort for consistent ordering (prevents second-preimage attacks)
        if (strcmp($left, $right) > 0) {
            [$left, $right] = [$right, $left];
        }

        $leftBin = hex2bin(str_starts_with($left, '0x') ? substr($left, 2) : $left) ?: '';
        $rightBin = hex2bin(str_starts_with($right, '0x') ? substr($right, 2) : $right) ?: '';

        return '0x' . hash('sha3-256', 'merkle_node:' . $leftBin . $rightBin);
    }
}
