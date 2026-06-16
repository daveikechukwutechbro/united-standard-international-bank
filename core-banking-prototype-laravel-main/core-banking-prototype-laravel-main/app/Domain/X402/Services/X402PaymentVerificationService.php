<?php

declare(strict_types=1);

namespace App\Domain\X402\Services;

use App\Domain\X402\Contracts\FacilitatorClientInterface;
use App\Domain\X402\DataObjects\MonetizedRouteConfig;
use App\Domain\X402\DataObjects\PaymentPayload;
use App\Domain\X402\DataObjects\PaymentRequirements;
use App\Domain\X402\DataObjects\VerifyResponse;
use App\Domain\X402\Enums\X402Network;
use Illuminate\Support\Facades\Log;

class X402PaymentVerificationService
{
    public function __construct(
        private readonly FacilitatorClientInterface $facilitator,
        private readonly X402PricingService $pricingService,
    ) {
    }

    /**
     * Verify a payment payload against the route configuration.
     *
     * Performs local pre-validation then delegates to the facilitator
     * for cryptographic signature verification.
     */
    public function verify(PaymentPayload $payload, MonetizedRouteConfig $config): VerifyResponse
    {
        // Local pre-validation
        $localResult = $this->preValidate($payload, $config);
        if ($localResult !== null) {
            return $localResult;
        }

        // Build requirements from config
        $requirements = $this->buildRequirements($config);

        // Delegate to facilitator for cryptographic verification
        Log::info('x402: Delegating verification to facilitator', [
            'network' => $requirements->network,
            'amount'  => $requirements->amount,
        ]);

        return $this->facilitator->verify($payload, $requirements);
    }

    /**
     * Build PaymentRequirements from a MonetizedRouteConfig.
     */
    public function buildRequirements(MonetizedRouteConfig $config): PaymentRequirements
    {
        $atomicAmount = $this->pricingService->usdToAtomicUnits($config->price);
        $assetAddress = (string) config("x402.assets.{$config->network}.{$config->asset}", '');

        return new PaymentRequirements(
            scheme: $config->scheme,
            network: $config->network,
            asset: $assetAddress,
            amount: $atomicAmount,
            payTo: (string) config('x402.server.pay_to', ''),
            maxTimeoutSeconds: (int) config('x402.server.max_timeout_seconds', 60),
            extra: $config->extra,
        );
    }

    /**
     * Perform local pre-validation before contacting the facilitator.
     */
    private function preValidate(PaymentPayload $payload, MonetizedRouteConfig $config): ?VerifyResponse
    {
        // Validate x402 version
        if ($payload->x402Version !== (int) config('x402.version', 2)) {
            return new VerifyResponse(
                isValid: false,
                invalidReason: 'invalid_x402_version',
                invalidMessage: 'Unsupported x402 version: ' . $payload->x402Version,
            );
        }

        // Validate scheme
        if ($payload->accepted->scheme !== $config->scheme) {
            return new VerifyResponse(
                isValid: false,
                invalidReason: 'invalid_scheme',
                invalidMessage: "Expected scheme '{$config->scheme}', got '{$payload->accepted->scheme}'",
            );
        }

        // Validate network
        $network = X402Network::tryFrom($payload->accepted->network);
        if ($network === null) {
            return new VerifyResponse(
                isValid: false,
                invalidReason: 'invalid_network',
                invalidMessage: 'Unsupported network: ' . $payload->accepted->network,
            );
        }

        // Validate amount is sufficient
        $requiredAmount = $this->pricingService->usdToAtomicUnits($config->price);
        /** @var numeric-string $paymentAmount */
        $paymentAmount = $payload->accepted->amount;
        /** @var numeric-string $requiredAmount */
        if (bccomp($paymentAmount, $requiredAmount) < 0) {
            return new VerifyResponse(
                isValid: false,
                invalidReason: 'invalid_exact_evm_payload_authorization_value',
                invalidMessage: "Payment amount {$payload->accepted->amount} is less than required {$requiredAmount}",
            );
        }

        // Validate payTo matches
        $expectedPayTo = (string) config('x402.server.pay_to', '');
        if ($expectedPayTo !== '' && strtolower($payload->accepted->payTo) !== strtolower($expectedPayTo)) {
            return new VerifyResponse(
                isValid: false,
                invalidReason: 'invalid_exact_evm_payload_recipient_mismatch',
                invalidMessage: 'Payment recipient does not match expected address',
            );
        }

        // Payload structure check
        if (empty($payload->payload)) {
            return new VerifyResponse(
                isValid: false,
                invalidReason: 'invalid_payload',
                invalidMessage: 'Missing payment payload data (signature/authorization)',
            );
        }

        return null;
    }
}
