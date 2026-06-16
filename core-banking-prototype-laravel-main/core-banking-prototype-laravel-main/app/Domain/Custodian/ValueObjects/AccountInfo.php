<?php

declare(strict_types=1);

namespace App\Domain\Custodian\ValueObjects;

use Carbon\Carbon;

final class AccountInfo
{
    public function __construct(
        public readonly string $accountId,
        public readonly string $name,
        public readonly string $status,
        public readonly array $balances = [],
        public readonly ?string $currency = null,
        public readonly ?string $type = null,
        public readonly ?Carbon $createdAt = null,
        public readonly ?array $metadata = []
    ) {
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'open', 'enabled']);
    }

    public function isFrozen(): bool
    {
        return in_array($this->status, ['frozen', 'suspended', 'blocked']);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'terminated']);
    }

    public function getBalance(string $assetCode): ?int
    {
        return $this->balances[$assetCode] ?? null;
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'name'       => $this->name,
            'status'     => $this->status,
            'balances'   => $this->balances,
            'currency'   => $this->currency,
            'type'       => $this->type,
            'created_at' => $this->createdAt?->toISOString(),
            'metadata'   => $this->metadata,
        ];
    }
}
