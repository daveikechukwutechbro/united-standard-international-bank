<?php

declare(strict_types=1);

namespace App\Domain\Interledger\Services;

use App\Domain\Interledger\Enums\IlpPacketType;

/**
 * Simulates the ILP STREAM protocol connector for development and testing.
 *
 * In production this would delegate to a real ILP connector node (e.g. Rafiki).
 * Here we provide deterministic, in-process simulation so that the rest of the
 * application can be built and tested without an external dependency.
 */
class IlpConnectorService
{
    /**
     * In-memory store of active STREAM connections (keyed by connection_id).
     *
     * @var array<string, array{source_address: string, shared_secret: string, destination_address: string}>
     */
    private array $connections = [];

    public function __construct(
        private readonly IlpAddressResolver $addressResolver = new IlpAddressResolver(),
    ) {
    }

    /**
     * Create a new STREAM connection to a destination ILP address.
     *
     * @return array{connection_id: string, source_address: string, shared_secret: string}
     */
    public function createConnection(string $destinationAddress, string $sharedSecret): array
    {
        $connectionId = bin2hex(random_bytes(16));
        $sourceAddress = (string) config('interledger.ilp_address', 'g.finaegis') . '.conn.' . $connectionId;

        $this->connections[$connectionId] = [
            'source_address'      => $sourceAddress,
            'shared_secret'       => $sharedSecret,
            'destination_address' => $destinationAddress,
        ];

        return [
            'connection_id'  => $connectionId,
            'source_address' => $sourceAddress,
            'shared_secret'  => $sharedSecret,
        ];
    }

    /**
     * Send an ILP PREPARE packet over an existing STREAM connection.
     *
     * Returns a FULFILL response on success or a REJECT response when the
     * amount exceeds the configured max packet size or the connection is
     * not found.
     *
     * @return array{type: string, connection_id: string, amount: string, asset_code: string, asset_scale: int, fulfillment?: string, error_code?: string, error_message?: string}
     */
    public function sendPacket(
        string $connectionId,
        string $amount,
        string $assetCode,
        int $assetScale,
    ): array {
        if (! array_key_exists($connectionId, $this->connections)) {
            return [
                'type'          => IlpPacketType::REJECT->value,
                'connection_id' => $connectionId,
                'amount'        => $amount,
                'asset_code'    => $assetCode,
                'asset_scale'   => $assetScale,
                'error_code'    => 'F02',
                'error_message' => 'Connection not found.',
            ];
        }

        $maxPacket = (int) config('interledger.stream.max_packet_amount', 1_000_000);

        if ((int) $amount > $maxPacket) {
            return [
                'type'          => IlpPacketType::REJECT->value,
                'connection_id' => $connectionId,
                'amount'        => $amount,
                'asset_code'    => $assetCode,
                'asset_scale'   => $assetScale,
                'error_code'    => 'F08',
                'error_message' => 'Amount exceeds maximum packet size.',
            ];
        }

        // Simulate a successful FULFILL.
        $fulfillment = bin2hex(random_bytes(32));

        return [
            'type'          => IlpPacketType::FULFILL->value,
            'connection_id' => $connectionId,
            'amount'        => $amount,
            'asset_code'    => $assetCode,
            'asset_scale'   => $assetScale,
            'fulfillment'   => $fulfillment,
        ];
    }

    /**
     * Close and clean up a STREAM connection.
     */
    public function closeConnection(string $connectionId): void
    {
        unset($this->connections[$connectionId]);
    }

    /**
     * Resolve a payment pointer ($wallet.example.com/alice) to an ILP address.
     *
     * Delegates to IlpAddressResolver for the actual conversion logic.
     */
    public function resolveAddress(string $paymentPointer): string
    {
        if (str_starts_with($paymentPointer, '$')) {
            return $this->addressResolver->fromPaymentPointer($paymentPointer);
        }

        // Already an ILP address.
        return $paymentPointer;
    }
}
