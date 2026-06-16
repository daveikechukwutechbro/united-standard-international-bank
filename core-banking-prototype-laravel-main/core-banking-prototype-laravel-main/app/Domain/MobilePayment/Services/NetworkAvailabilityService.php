<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Services;

use App\Domain\MobilePayment\Enums\PaymentNetwork;
use App\Domain\Wallet\Factories\BlockchainConnectorFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Provides network availability and health status for mobile clients.
 *
 * Returns current status, average fees, and estimated confirmation times
 * for all supported payment networks.
 */
class NetworkAvailabilityService
{
    private const CACHE_TTL_SECONDS = 30;

    /**
     * Get the status of all supported networks.
     *
     * @return array<int, array{id: string, name: string, native_asset: string, status: string, avg_fee_usd: string, avg_confirmation_seconds: int, congestion: string, supported_assets: array<string>}>
     */
    public function getNetworkStatuses(): array
    {
        return Cache::remember(
            'mobile:network_statuses',
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->fetchNetworkStatuses()
        );
    }

    /**
     * Get status for a single network.
     *
     * @return array{id: string, name: string, native_asset: string, status: string, avg_fee_usd: string, avg_confirmation_seconds: int, congestion: string, supported_assets: array<string>}|null
     */
    public function getNetworkStatus(PaymentNetwork $network): ?array
    {
        $statuses = $this->getNetworkStatuses();

        foreach ($statuses as $status) {
            if ($status['id'] === $network->value) {
                return $status;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{id: string, name: string, native_asset: string, status: string, avg_fee_usd: string, avg_confirmation_seconds: int, congestion: string, supported_assets: array<string>}>
     */
    private function fetchNetworkStatuses(): array
    {
        $statuses = [];

        foreach (PaymentNetwork::cases() as $network) {
            $statuses[] = $this->checkNetwork($network);
        }

        return $statuses;
    }

    /**
     * @return array{id: string, name: string, native_asset: string, status: string, avg_fee_usd: string, avg_confirmation_seconds: int, congestion: string, supported_assets: array<string>}
     */
    private function checkNetwork(PaymentNetwork $network): array
    {
        $healthy = $this->isNetworkHealthy($network);

        return [
            'id'                       => $network->value,
            'name'                     => $network->label(),
            'native_asset'             => $network->nativeAsset(),
            'status'                   => $healthy ? 'active' : 'unavailable',
            'avg_fee_usd'              => $this->getAverageFeeUsd($network),
            'avg_confirmation_seconds' => $this->getAvgConfirmationSeconds($network),
            'congestion'               => $this->getCongestionLevel($network),
            'supported_assets'         => ['USDC'],
        ];
    }

    private function isNetworkHealthy(PaymentNetwork $network): bool
    {
        try {
            $connector = BlockchainConnectorFactory::create(strtolower($network->value));

            return $connector->isHealthy();
        } catch (Throwable $e) {
            Log::warning('Network health check failed', [
                'network' => $network->value,
                'error'   => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function getAverageFeeUsd(PaymentNetwork $network): string
    {
        return number_format($network->averageGasCostUsd(), 3);
    }

    private function getAvgConfirmationSeconds(PaymentNetwork $network): int
    {
        return $network->avgConfirmationSeconds();
    }

    private function getCongestionLevel(PaymentNetwork $network): string
    {
        // In production, this would query real network congestion data
        return 'low';
    }
}
