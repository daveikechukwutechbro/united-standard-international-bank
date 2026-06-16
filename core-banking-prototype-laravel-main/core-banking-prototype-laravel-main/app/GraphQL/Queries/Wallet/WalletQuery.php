<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Wallet;

use App\Domain\Wallet\Models\MultiSigWallet;

class WalletQuery
{
    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): MultiSigWallet
    {
        /** @var MultiSigWallet */
        return MultiSigWallet::findOrFail($args['id']);
    }
}
