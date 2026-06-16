<?php

declare(strict_types=1);

namespace App\Domain\Custodian\ValueObjects;

use App\Domain\Account\DataObjects\Money;

final class TransferRequest
{
    public function __construct(
        public readonly string $fromAccount,
        public readonly string $toAccount,
        public readonly string $assetCode,
        public readonly Money $amount,
        public readonly ?string $reference = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'from_account' => $this->fromAccount,
            'to_account'   => $this->toAccount,
            'asset_code'   => $this->assetCode,
            'amount'       => $this->amount->getAmount(),
            'reference'    => $this->reference,
            'description'  => $this->description,
            'metadata'     => $this->metadata,
        ];
    }

    public static function create(
        string $fromAccount,
        string $toAccount,
        string $assetCode,
        int $amount,
        ?string $reference = null,
        ?string $description = null,
        ?array $metadata = []
    ): self {
        return new self(
            fromAccount: $fromAccount,
            toAccount: $toAccount,
            assetCode: $assetCode,
            amount: new Money($amount),
            reference: $reference,
            description: $description,
            metadata: $metadata
        );
    }
}
