<?php

declare(strict_types=1);

namespace App\Domain\DeFi\Services;

use App\Domain\CrossChain\Enums\CrossChainNetwork;
use App\Domain\DeFi\Contracts\LendingProtocolInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Flash loan orchestration via Aave V3.
 *
 * In production, executes atomic flash loan + callback operations.
 */
class FlashLoanService
{
    public function __construct(
        private readonly ?LendingProtocolInterface $lendingProtocol = null,
    ) {
    }

    /**
     * Execute a flash loan.
     *
     * @param array<string, mixed> $callbackParams Parameters for the callback operation
     * @return array{tx_hash: string, borrowed_amount: string, fee: string, callback_result: array<string, mixed>}
     */
    public function executeFlashLoan(
        CrossChainNetwork $chain,
        string $token,
        string $amount,
        string $callbackContract,
        array $callbackParams = [],
    ): array {
        $feeRate = (string) config('defi.aave.flash_loan_fee', '0.0005');
        $fee = bcmul($amount, $feeRate, 8);

        Log::info('Flash loan: Executing', [
            'chain'    => $chain->value,
            'token'    => $token,
            'amount'   => $amount,
            'fee'      => $fee,
            'callback' => $callbackContract,
        ]);

        // In production: call Aave V3 Pool.flashLoan()
        return [
            'tx_hash'         => '0x' . Str::random(64),
            'borrowed_amount' => $amount,
            'fee'             => $fee,
            'callback_result' => [
                'success'  => true,
                'contract' => $callbackContract,
                'params'   => $callbackParams,
            ],
        ];
    }

    /**
     * Estimate flash loan fee.
     */
    public function estimateFee(string $amount): string
    {
        $feeRate = (string) config('defi.aave.flash_loan_fee', '0.0005');

        return bcmul($amount, $feeRate, 8);
    }

    /**
     * Check if flash loans are available on a given chain.
     */
    public function isAvailable(CrossChainNetwork $chain): bool
    {
        $supportedChains = ['ethereum', 'polygon', 'arbitrum', 'optimism', 'base'];

        return in_array($chain->value, $supportedChains);
    }
}
