<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Fraud;

use App\Domain\Fraud\Models\FraudCase;
use App\Domain\Fraud\Services\FraudCaseService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class EscalateFraudCaseMutation
{
    public function __construct(
        private readonly FraudCaseService $fraudCaseService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): FraudCase
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var FraudCase|null $fraudCase */
        $fraudCase = FraudCase::query()->find($args['id']);

        if (! $fraudCase) {
            throw new ModelNotFoundException('Fraud case not found.');
        }

        $reason = $args['notes'] ?? $args['reason'] ?? 'Escalated via GraphQL';

        return $this->fraudCaseService->escalateCase($fraudCase, $reason);
    }
}
