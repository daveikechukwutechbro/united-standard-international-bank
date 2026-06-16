<?php

declare(strict_types=1);

namespace App\Domain\X402\Services;

use App\Domain\X402\Contracts\X402SignerInterface;
use App\Domain\X402\Enums\X402Network;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * EIP-712 signer for creating x402 TransferWithAuthorization payloads.
 *
 * In production, this delegates to an HSM or secure enclave for signing.
 * The demo implementation creates properly structured payloads with
 * placeholder signatures for testing the full protocol flow.
 */
class X402EIP712SignerService implements X402SignerInterface
{
    private ?string $signerAddress = null;

    public function __construct(
        /** @phpstan-ignore property.onlyWritten */
        private readonly string $signerKeyId = 'default',
    ) {
    }

    /**
     * Create an EIP-3009 TransferWithAuthorization signed payload.
     *
     * @param array<string, mixed> $extra EIP-712 domain info from PaymentRequirements
     * @return array<string, mixed> The payload with signature and authorization
     */
    public function signTransferAuthorization(
        string $network,
        string $to,
        string $amount,
        string $asset,
        int $maxTimeoutSeconds,
        array $extra = [],
    ): array {
        $from = $this->getAddress();
        $nonce = '0x' . bin2hex(random_bytes(32));
        $validAfter = '0';
        $validBefore = (string) (time() + $maxTimeoutSeconds);

        Log::info('x402: Creating EIP-3009 transfer authorization', [
            'from'    => $from,
            'to'      => $to,
            'amount'  => $amount,
            'network' => $network,
        ]);

        // In production: sign EIP-712 typed data via HSM / KeyManagement
        // Demo mode: return structured payload without real signature
        $signature = $this->sign($network, $asset, [
            'from'        => $from,
            'to'          => $to,
            'value'       => $amount,
            'validAfter'  => $validAfter,
            'validBefore' => $validBefore,
            'nonce'       => $nonce,
        ], $extra);

        return [
            'signature'     => $signature,
            'authorization' => [
                'from'        => $from,
                'to'          => $to,
                'value'       => $amount,
                'validAfter'  => $validAfter,
                'validBefore' => $validBefore,
                'nonce'       => $nonce,
            ],
        ];
    }

    /**
     * Get the signer wallet address (lazy-loaded from config).
     */
    public function getAddress(): string
    {
        if ($this->signerAddress === null) {
            $this->signerAddress = (string) config('x402.client.signer_address', '0x0000000000000000000000000000000000000000');
        }

        return $this->signerAddress;
    }

    /**
     * Sign EIP-712 typed data.
     *
     * @param array<string, string> $message The message to sign
     * @param array<string, mixed> $extra Domain info (name, version)
     */
    private function sign(string $network, string $verifyingContract, array $message, array $extra): string
    {
        if (app()->isProduction()) {
            throw new RuntimeException(
                'X402EIP712SignerService demo signer must not be used in production. '
                . 'Bind a real X402SignerInterface implementation (e.g., HSM-backed signer).'
            );
        }

        // Build EIP-712 domain separator
        $networkEnum = X402Network::tryFrom($network);
        $chainId = $networkEnum?->chainId() ?? 1;

        $domain = [
            'name'              => $extra['name'] ?? 'USD Coin',
            'version'           => $extra['version'] ?? '2',
            'chainId'           => $chainId,
            'verifyingContract' => $verifyingContract,
        ];

        // The actual EIP-712 signing would happen here via:
        // - HSM (Azure Key Vault, AWS CloudHSM)
        // - KeyManagement/ShamirService for key retrieval
        // - ethers-php or kornrunner/ethereum for local signing
        //
        // For the demo/prototype, we return a deterministic placeholder
        // that the test facilitator accepts.
        $dataToSign = json_encode(['domain' => $domain, 'message' => $message], JSON_THROW_ON_ERROR);

        // Produce a valid 65-byte signature: 130 hex chars + '1c' (v=28)
        return '0x' . substr(hash('sha512', $dataToSign), 0, 128) . '1c';
    }
}
