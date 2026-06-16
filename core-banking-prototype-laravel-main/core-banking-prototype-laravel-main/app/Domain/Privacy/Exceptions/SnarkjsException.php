<?php

declare(strict_types=1);

namespace App\Domain\Privacy\Exceptions;

use RuntimeException;

/**
 * Exception thrown when snarkjs CLI execution fails.
 */
class SnarkjsException extends RuntimeException
{
    public static function processTimeout(string $circuit, int $timeoutSeconds): self
    {
        return new self("snarkjs process timed out after {$timeoutSeconds}s for circuit: {$circuit}");
    }

    public static function processFailed(string $circuit, string $stderr): self
    {
        return new self("snarkjs execution failed for circuit '{$circuit}': {$stderr}");
    }

    public static function invalidOutput(string $circuit, string $reason): self
    {
        return new self("snarkjs produced invalid output for circuit '{$circuit}': {$reason}");
    }
}
