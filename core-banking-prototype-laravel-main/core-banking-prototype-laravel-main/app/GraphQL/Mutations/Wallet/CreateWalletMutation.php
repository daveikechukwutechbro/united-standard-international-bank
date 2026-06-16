<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Wallet;

use App\Domain\Wallet\Models\MultiSigWallet;
use App\Domain\Wallet\Services\MultiSigWalletService;
use App\Domain\Wallet\ValueObjects\MultiSigConfiguration;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class CreateWalletMutation
{
    public function __construct(
        private readonly MultiSigWalletService $walletService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): MultiSigWallet
    {
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $config = MultiSigConfiguration::create(
            requiredSignatures: (int) $args['required_signatures'],
            totalSigners: (int) $args['total_signers'],
            chain: $args['chain'],
            name: $args['name'],
        );

        return $this->walletService->createWallet(
            owner: $user,
            config: $config,
            metadata: [],
        );
    }
}
