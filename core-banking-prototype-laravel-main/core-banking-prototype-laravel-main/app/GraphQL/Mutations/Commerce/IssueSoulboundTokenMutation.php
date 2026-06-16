<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Commerce;

use App\Domain\Commerce\Enums\TokenType;
use App\Domain\Commerce\Services\SoulboundTokenService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class IssueSoulboundTokenMutation
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

        $metadata = isset($args['metadata']) ? (json_decode($args['metadata'], true) ?: []) : [];
        $tokenType = TokenType::from($args['token_type']);

        $token = $this->soulboundTokenService->issueToken(
            $tokenType,
            $args['recipient_address'],
            $metadata,
        );

        return [
            'token_hash' => $token->getTokenHash(),
            'token_type' => $args['token_type'],
            'subject'    => $token->recipientId,
            'issuer'     => $token->issuerId,
            'issued_at'  => $token->issuedAt->format('c'),
            'is_valid'   => $token->isValid(),
        ];
    }
}
