<?php

declare(strict_types=1);

namespace App\Domain\Basket\Activities;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\Models\Account;
use App\Domain\Basket\Events\BasketDecomposed;
use App\Domain\Basket\Models\BasketAsset;
use App\Domain\Wallet\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Workflow\Activity;

class DecomposeBasketBusinessActivity extends Activity
{
    public function __construct(
        private WalletService $walletService
    ) {
    }

    /**
     * Execute basket decomposition using proper Service → Workflow → Activity → Aggregate pattern.
     */
    public function execute(AccountUuid $accountUuid, string $basketCode, int $amount): array
    {
        $account = Account::where('uuid', (string) $accountUuid)->firstOrFail();
        /** @var BasketAsset $basket */
        $basket = BasketAsset::where('code', $basketCode)->firstOrFail();

        return DB::transaction(
            function () use ($account, $basket, $basketCode, $amount, $accountUuid) {
                // Calculate component amounts based on weights
                $componentAmounts = $this->calculateComponentAmounts($basket, $amount);

                // Use WalletService for proper Service → Workflow → Activity → Aggregate architecture
                // Subtract basket balance using WalletService
                $this->walletService->withdraw($accountUuid, $basketCode, $amount);

                // Add component balances using WalletService
                foreach ($componentAmounts as $assetCode => $componentAmount) {
                    $this->walletService->deposit($accountUuid, $assetCode, $componentAmount);
                }

                // Record decomposition event
                event(
                    new BasketDecomposed(
                        accountUuid: (string) $account->uuid,
                        basketCode: $basketCode,
                        amount: $amount,
                        componentAmounts: $componentAmounts,
                        decomposedAt: now()
                    )
                );

                Log::info(
                    "Decomposed {$amount} of basket {$basketCode} for account {$account->uuid}",
                    [
                        'components' => $componentAmounts,
                    ]
                );

                return [
                    'basket_code'   => $basketCode,
                    'basket_amount' => $amount,
                    'components'    => $componentAmounts,
                    'decomposed_at' => now()->toISOString(),
                ];
            }
        );
    }

    /**
     * Calculate component amounts based on basket weights.
     */
    private function calculateComponentAmounts(BasketAsset $basket, int $basketAmount): array
    {
        $componentAmounts = [];
        $components = $basket->activeComponents;

        foreach ($components as $component) {
            // Calculate proportional amount based on weight
            $componentAmount = (int) round($basketAmount * ($component->weight / 100));
            $componentAmounts[$component->asset_code] = $componentAmount;
        }

        return $componentAmounts;
    }
}
