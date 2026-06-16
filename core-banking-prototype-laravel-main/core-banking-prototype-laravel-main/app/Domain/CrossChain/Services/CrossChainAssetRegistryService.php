<?php

declare(strict_types=1);

namespace App\Domain\CrossChain\Services;

use App\Domain\CrossChain\Contracts\AssetMapperInterface;
use App\Domain\CrossChain\Enums\CrossChainNetwork;

/**
 * Maps token equivalences across chains (e.g., USDC on Polygon <-> USDC on Arbitrum).
 */
class CrossChainAssetRegistryService implements AssetMapperInterface
{
    /**
     * Token address registry: symbol => chain => address.
     *
     * @var array<string, array<string, string>>
     */
    private array $tokenRegistry;

    public function __construct()
    {
        $this->tokenRegistry = $this->getDefaultRegistry();
    }

    public function getTokenAddress(string $tokenSymbol, CrossChainNetwork $chain): ?string
    {
        $symbol = strtoupper($tokenSymbol);

        return $this->tokenRegistry[$symbol][$chain->value] ?? null;
    }

    public function mapTokenAddress(
        string $tokenAddress,
        CrossChainNetwork $sourceChain,
        CrossChainNetwork $destChain,
    ): ?string {
        $symbol = $this->getCanonicalToken($tokenAddress, $sourceChain);

        if ($symbol === null) {
            return null;
        }

        return $this->getTokenAddress($symbol, $destChain);
    }

    public function getCanonicalToken(string $tokenAddress, CrossChainNetwork $chain): ?string
    {
        $normalizedAddress = strtolower($tokenAddress);

        foreach ($this->tokenRegistry as $symbol => $chains) {
            $address = $chains[$chain->value] ?? null;

            if ($address !== null && strtolower($address) === $normalizedAddress) {
                return $symbol;
            }
        }

        return null;
    }

    public function getSupportedTokens(CrossChainNetwork $chain): array
    {
        $tokens = [];

        foreach ($this->tokenRegistry as $symbol => $chains) {
            if (isset($chains[$chain->value])) {
                $tokens[$symbol] = $chains[$chain->value];
            }
        }

        return $tokens;
    }

    /**
     * Register a custom token mapping.
     */
    public function registerToken(string $symbol, CrossChainNetwork $chain, string $address): void
    {
        $this->tokenRegistry[strtoupper($symbol)][$chain->value] = $address;
    }

    /**
     * Check if a token is supported on a specific chain.
     */
    public function isTokenSupported(string $tokenSymbol, CrossChainNetwork $chain): bool
    {
        return $this->getTokenAddress($tokenSymbol, $chain) !== null;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getDefaultRegistry(): array
    {
        return [
            'USDC' => [
                CrossChainNetwork::ETHEREUM->value => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
                CrossChainNetwork::POLYGON->value  => '0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359',
                CrossChainNetwork::ARBITRUM->value => '0xaf88d065e77c8cC2239327C5EDb3A432268e5831',
                CrossChainNetwork::OPTIMISM->value => '0x0b2C639c533813f4Aa9D7837CAf62653d097Ff85',
                CrossChainNetwork::BASE->value     => '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
                CrossChainNetwork::BSC->value      => '0x8AC76a51cc950d9822D68b83fE1Ad97B32Cd580d',
            ],
            'USDT' => [
                CrossChainNetwork::ETHEREUM->value => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                CrossChainNetwork::POLYGON->value  => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F',
                CrossChainNetwork::ARBITRUM->value => '0xFd086bC7CD5C481DCC9C85ebE478A1C0b69FCbb9',
                CrossChainNetwork::BSC->value      => '0x55d398326f99059fF775485246999027B3197955',
                CrossChainNetwork::TRON->value     => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
            ],
            'WETH' => [
                CrossChainNetwork::ETHEREUM->value => '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2',
                CrossChainNetwork::POLYGON->value  => '0x7ceB23fD6bC0adD59E62ac25578270cFf1b9f619',
                CrossChainNetwork::ARBITRUM->value => '0x82aF49447D8a07e3bd95BD0d56f35241523fBab1',
                CrossChainNetwork::OPTIMISM->value => '0x4200000000000000000000000000000000000006',
                CrossChainNetwork::BASE->value     => '0x4200000000000000000000000000000000000006',
            ],
            'WBTC' => [
                CrossChainNetwork::ETHEREUM->value => '0x2260FAC5E5542a773Aa44fBCfeDf7C193bc2C599',
                CrossChainNetwork::POLYGON->value  => '0x1BFD67037B42Cf73acF2047067bd4F2C47D9BfD6',
                CrossChainNetwork::ARBITRUM->value => '0x2f2a2543B76A4166549F7aaB2e75Bef0aefC5B0f',
            ],
            'DAI' => [
                CrossChainNetwork::ETHEREUM->value => '0x6B175474E89094C44Da98b954EedeAC495271d0F',
                CrossChainNetwork::POLYGON->value  => '0x8f3Cf7ad23Cd3CaDbD9735AFf958023239c6A063',
                CrossChainNetwork::ARBITRUM->value => '0xDA10009cBd5D07dd0CeCc66161FC93D7c9000da1',
                CrossChainNetwork::OPTIMISM->value => '0xDA10009cBd5D07dd0CeCc66161FC93D7c9000da1',
                CrossChainNetwork::BASE->value     => '0x50c5725949A6F0c72E6C4a641F24049A917DB0Cb',
            ],
        ];
    }
}
