<?php

declare(strict_types=1);

namespace App\Domain\Asset\Aggregates;

use App\Domain\Account\DataObjects\AccountUuid;
use App\Domain\Account\DataObjects\Hash;
use App\Domain\Account\DataObjects\Money;
use App\Domain\Account\Utils\ValidatesHash;
use App\Domain\Asset\Events\AssetTransactionCreated;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class AssetTransactionAggregate extends AggregateRoot
{
    use ValidatesHash;

    private ?AccountUuid $accountUuid = null;

    private ?string $assetCode = null;

    private ?Money $money = null;

    private ?string $type = null;

    private ?Hash $hash = null;

    /**
     * Create a credit transaction for an asset.
     */
    public function credit(
        AccountUuid $accountUuid,
        string $assetCode,
        Money $money,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        $hash = $this->generateHash($money);

        $this->recordThat(
            new AssetTransactionCreated(
                accountUuid: $accountUuid,
                assetCode: $assetCode,
                money: $money,
                type: 'credit',
                hash: $hash,
                description: $description,
                metadata: $metadata
            )
        );

        return $this;
    }

    /**
     * Create a debit transaction for an asset.
     */
    public function debit(
        AccountUuid $accountUuid,
        string $assetCode,
        Money $money,
        ?string $description = null,
        ?array $metadata = null
    ): self {
        $hash = $this->generateHash($money);

        $this->recordThat(
            new AssetTransactionCreated(
                accountUuid: $accountUuid,
                assetCode: $assetCode,
                money: $money,
                type: 'debit',
                hash: $hash,
                description: $description,
                metadata: $metadata
            )
        );

        return $this;
    }

    /**
     * Apply asset transaction created event.
     */
    public function applyAssetTransactionCreated(AssetTransactionCreated $event): void
    {
        $this->accountUuid = $event->accountUuid;
        $this->assetCode = $event->assetCode;
        $this->money = $event->money;
        $this->type = $event->type;
        $this->hash = $event->hash;
    }

    /**
     * Get the account UUID.
     */
    public function getAccountUuid(): ?AccountUuid
    {
        return $this->accountUuid;
    }

    /**
     * Get the asset code.
     */
    public function getAssetCode(): ?string
    {
        return $this->assetCode;
    }

    /**
     * Get the money amount.
     */
    public function getMoney(): ?Money
    {
        return $this->money;
    }

    /**
     * Get the transaction type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get the hash.
     */
    public function getHash(): ?Hash
    {
        return $this->hash;
    }

    /**
     * Check if this is a credit transaction.
     */
    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    /**
     * Check if this is a debit transaction.
     */
    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }
}
