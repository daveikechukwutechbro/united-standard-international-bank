<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Commerce;

use App\Domain\Commerce\Services\SoulboundTokenService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class VerifySoulboundTokenQuery
{
    public function __construct(
        private readonly SoulboundTokenService $soulboundTokenService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function __invoke(mixed $rootValue, array $args): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        // Check if token is revoked via the service
        $isRevoked = $this->soulboundTokenService->isRevoked($args['token_hash']);

        return [
            'token_hash' => $args['token_hash'],
            'token_type' => 'unknown',
            'subject'    => '',
            'issuer'     => '',
            'issued_at'  => '',
            'is_valid'   => ! $isRevoked,
        ];
    }
}
