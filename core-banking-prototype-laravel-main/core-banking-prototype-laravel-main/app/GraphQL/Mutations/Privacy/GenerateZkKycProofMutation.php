<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Privacy;

use App\Domain\Privacy\Services\ZkKycService;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

final class GenerateZkKycProofMutation
{
    public function __construct(
        private readonly ZkKycService $zkKycService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): string
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $subjectData = json_decode($args['subject_data'], true) ?: [];
        $threshold = $args['threshold'] ?? null;
        $userId = (string) $user->id;

        $proof = match ($args['proof_type']) {
            'age_proof' => $this->zkKycService->generateAgeProof(
                $subjectData,
                $threshold ?? 18,
            ),
            'residency_proof' => $this->zkKycService->generateResidencyProof(
                $subjectData,
                $subjectData['country'] ?? '',
            ),
            'kyc_tier_proof' => $this->zkKycService->generateKycTierProof(
                $userId,
                (int) ($subjectData['actual_tier'] ?? 1),
                $subjectData['kyc_provider'] ?? 'default',
                $threshold ?? 1,
            ),
            'sanctions_clearance_proof' => $this->zkKycService->generateSanctionsClearanceProof(
                $userId,
                $subjectData['full_name'] ?? '',
                new DateTimeImmutable($subjectData['date_of_birth'] ?? 'now'),
                $subjectData['sanctions_list_hash'] ?? '',
            ),
            'accredited_investor_proof' => $this->zkKycService->generateAccreditedInvestorProof(
                $userId,
                (bool) ($subjectData['is_accredited'] ?? true),
                $subjectData['jurisdiction'] ?? '',
            ),
            default => throw new InvalidArgumentException("Unsupported proof type: {$args['proof_type']}"),
        };

        return json_encode($proof) ?: '{}';
    }
}
