<?php

declare(strict_types=1);

namespace App\Domain\CardIssuance\ValueObjects;

use App\Domain\CardIssuance\Enums\WalletType;

/**
 * Provisioning data for Apple Pay / Google Pay.
 * This data is passed directly to native wallet APIs without decryption.
 */
final readonly class ProvisioningData
{
    /**
     * @param array<string> $certificateChain
     */
    public function __construct(
        public string $cardId,
        public WalletType $walletType,
        public string $encryptedPassData,
        public string $activationData,
        public string $ephemeralPublicKey,
        public array $certificateChain = [],
    ) {
    }

    /**
     * Convert to array for API response.
     * CRITICAL: Client must pass this directly to native APIs without modification.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'card_id'              => $this->cardId,
            'wallet_type'          => $this->walletType->value,
            'encrypted_pass_data'  => $this->encryptedPassData,
            'activation_data'      => $this->activationData,
            'ephemeral_public_key' => $this->ephemeralPublicKey,
            'certificate_chain'    => $this->certificateChain,
        ];
    }
}
