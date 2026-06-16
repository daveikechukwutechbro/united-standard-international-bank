<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\FinancialInstitution;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Services\OnboardingService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class OnboardPartnerMutation
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): FinancialInstitutionPartner
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $application = $this->onboardingService->submitApplication([
            'institution_name' => $args['institution_name'],
            'legal_name'       => $args['legal_name'],
            'institution_type' => $args['institution_type'],
            'country'          => $args['country'],
            'submitted_by'     => (string) $user->id,
        ]);

        // Return the partner projection or create a fallback record.
        /** @var FinancialInstitutionPartner $partner */
        $partner = FinancialInstitutionPartner::where('application_id', $application->id)->first()
            ?? FinancialInstitutionPartner::create([
                'application_id'   => $application->id,
                'institution_name' => $args['institution_name'],
                'legal_name'       => $args['legal_name'],
                'institution_type' => $args['institution_type'],
                'country'          => $args['country'],
                'status'           => 'pending',
                'tier'             => $args['tier'] ?? 'starter',
            ]);

        return $partner;
    }
}
