<?php

declare(strict_types=1);

namespace App\Domain\Security\Contracts;

use App\Domain\Security\ValueObjects\SecurityCheckResult;

interface SecurityCheckInterface
{
    /**
     * Get the name of this security check.
     */
    public function getName(): string;

    /**
     * Get the OWASP category this check covers.
     */
    public function getCategory(): string;

    /**
     * Run the security check and return the result.
     */
    public function run(): SecurityCheckResult;
}
