<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a required ZK circuit file is missing.
 */
class CircuitNotFoundException extends RuntimeException
{
    public static function wasmNotFound(string $circuit, string $path): self
    {
        return new self("Circuit WASM not found for '{$circuit}' at: {$path}");
    }

    public static function zkeyNotFound(string $circuit, string $path): self
    {
        return new self("Circuit zkey not found for '{$circuit}' at: {$path}");
    }

    public static function verificationKeyNotFound(string $circuit, string $path): self
    {
        return new self("Verification key not found for '{$circuit}' at: {$path}");
    }

    public static function unmappedProofType(string $proofType): self
    {
        return new self("No circuit mapping found for proof type: {$proofType}");
    }
}
