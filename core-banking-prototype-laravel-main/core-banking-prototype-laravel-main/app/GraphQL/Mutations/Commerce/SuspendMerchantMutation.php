<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Commerce;

use App\Domain\Commerce\Models\Merchant;
use App\Domain\Commerce\Services\MerchantOnboardingService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

final class SuspendMerchantMutation
{
    public function __construct(
        private readonly MerchantOnboardingService $merchantOnboardingService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public function __invoke(mixed $rootValue, array $args): Merchant
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        $this->merchantOnboardingService->suspend(
            $args['id'],
            'Suspended via GraphQL',
        );

        /** @var Merchant */
        return Merchant::findOrFail($args['id']);
    }
}
