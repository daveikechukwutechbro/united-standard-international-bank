<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Contracts;

use App\Domain\CrossChain\Enums\CrossChainNetwork;

interface CrossChainMessageInterface
{
    /**
     * Send a cross-chain message.
     *
     * @param array<string, mixed> $payload
     * @return array{message_id: string, status: string}
     */
    public function sendMessage(
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
        string $destContract,
        array $payload,
    ): array;

    /**
     * Get the status of a cross-chain message.
     *
     * @return array{status: string, source_tx_hash: ?string, dest_tx_hash: ?string}
     */
    public function getMessageStatus(string $messageId): array;
}
