<?php

declare(strict_types=1);

namespace App\Domain\MobilePayment\Contracts;

use App\Domain\Commerce\Models\Merchant;

interface MerchantLookupServiceInterface
{
    /**
     * Find a merchant by their public ID.
     *
     * @throws \App\Domain\MobilePayment\Exceptions\MerchantNotFoundException
     */
    public function findByPublicId(string $publicId): Merchant;

    /**
     * Check if a merchant accepts the given asset on the given network.
     */
    public function acceptsPayment(Merchant $merchant, string $asset, string $network): bool;
}
