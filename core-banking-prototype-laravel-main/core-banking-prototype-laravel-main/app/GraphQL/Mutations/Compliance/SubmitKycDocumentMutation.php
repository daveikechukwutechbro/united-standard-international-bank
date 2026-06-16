<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Compliance;

use App\Domain\Compliance\Models\KycVerification;
use App\Domain\Compliance\Services\KycService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class SubmitKycDocumentMutation
{
    public function __construct(
        private readonly KycService $kycService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): KycVerification
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var User $targetUser */
        $targetUser = User::findOrFail($args['user_id']);

        $this->kycService->submitKyc($targetUser, $args['documents'] ?? []);

        // Return a read-model record for the GraphQL response.
        return KycVerification::create([
            'user_id'          => $args['user_id'],
            'type'             => $args['type'],
            'document_type'    => $args['document_type'],
            'document_country' => $args['document_country'],
            'status'           => 'pending',
            'started_at'       => now(),
        ]);
    }
}
