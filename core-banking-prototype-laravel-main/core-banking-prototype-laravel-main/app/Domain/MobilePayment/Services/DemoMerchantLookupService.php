<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Services;

use App\Domain\Commerce\Enums\MerchantStatus;
use App\Domain\Commerce\Models\Merchant;
use App\Domain\MobilePayment\Contracts\MerchantLookupServiceInterface;
use App\Domain\MobilePayment\Exceptions\MerchantNotFoundException;

/**
 * Demo implementation of MerchantLookupService.
 *
 * Returns a fake merchant for any valid-looking public_id.
 * In production, this would query the merchants table.
 */
class DemoMerchantLookupService implements MerchantLookupServiceInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $demoMerchants = [
        'merchant_starbolt' => [
            'display_name'      => 'Starbolt Coffee',
            'icon_url'          => 'https://cdn.finaegis.com/merchants/starbolt.png',
            'accepted_assets'   => ['USDC'],
            'accepted_networks' => ['SOLANA', 'TRON'],
        ],
        'merchant_jumia' => [
            'display_name'      => 'Jumia Marketplace',
            'icon_url'          => 'https://cdn.finaegis.com/merchants/jumia.png',
            'accepted_assets'   => ['USDC'],
            'accepted_networks' => ['SOLANA', 'TRON'],
        ],
        'merchant_netflix' => [
            'display_name'      => 'Netflix Subscription',
            'icon_url'          => 'https://cdn.finaegis.com/merchants/netflix.png',
            'accepted_assets'   => ['USDC'],
            'accepted_networks' => ['SOLANA'],
        ],
    ];

    public function findByPublicId(string $publicId): Merchant
    {
        // Check known demo merchants first
        if (isset($this->demoMerchants[$publicId])) {
            return $this->buildMerchant($publicId, $this->demoMerchants[$publicId]);
        }

        // Try database
        $merchant = Merchant::where('public_id', $publicId)->first();
        if ($merchant) {
            return $merchant;
        }

        // In demo mode, accept any merchant_ prefixed ID
        if (str_starts_with($publicId, 'merchant_')) {
            return $this->buildMerchant($publicId, [
                'display_name'      => 'Demo Merchant',
                'icon_url'          => null,
                'accepted_assets'   => ['USDC'],
                'accepted_networks' => ['SOLANA', 'TRON'],
            ]);
        }

        throw new MerchantNotFoundException($publicId);
    }

    public function acceptsPayment(Merchant $merchant, string $asset, string $network): bool
    {
        return $merchant->acceptsAsset($asset) && $merchant->acceptsNetwork($network);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildMerchant(string $publicId, array $data): Merchant
    {
        return Merchant::firstOrCreate(
            ['public_id' => $publicId],
            [
                'display_name'      => $data['display_name'],
                'icon_url'          => $data['icon_url'] ?? null,
                'accepted_assets'   => $data['accepted_assets'],
                'accepted_networks' => $data['accepted_networks'],
                'status'            => MerchantStatus::ACTIVE,
            ],
        );
    }
}
