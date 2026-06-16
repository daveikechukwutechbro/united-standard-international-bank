<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Lending;

use App\Domain\Lending\Models\LoanApplication;
use App\Domain\Lending\Services\LoanApplicationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class ApplyForLoanMutation
{
    public function __construct(
        private readonly LoanApplicationService $loanApplicationService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): LoanApplication
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $result = $this->loanApplicationService->submitApplication([
            'borrower_id'      => (string) $user->id,
            'requested_amount' => $args['requested_amount'],
            'term_months'      => $args['term_months'],
            'purpose'          => $args['purpose'],
            'borrower_info'    => $args['borrower_info'] ?? [],
        ]);

        // Return the read-model projection or create a fallback record.
        /** @var LoanApplication $application */
        $application = LoanApplication::where('borrower_id', (string) $user->id)
            ->latest()
            ->first()
            ?? LoanApplication::create([
                'borrower_id'      => (string) $user->id,
                'requested_amount' => $args['requested_amount'],
                'term_months'      => $args['term_months'],
                'purpose'          => $args['purpose'],
                'status'           => $result['status'] ?? 'submitted',
                'submitted_at'     => now(),
            ]);

        return $application;
    }
}
