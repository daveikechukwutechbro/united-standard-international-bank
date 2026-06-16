<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Microfinance;

use App\Domain\Microfinance\Services\LoanProvisioningService;

class ProvisionSummaryQuery
{
    public function __construct(
        private readonly LoanProvisioningService $loanProvisioningService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{standard: string, substandard: string, doubtful: string, loss: string, total: string}
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        return $this->loanProvisioningService->getTotalProvisions();
    }
}
