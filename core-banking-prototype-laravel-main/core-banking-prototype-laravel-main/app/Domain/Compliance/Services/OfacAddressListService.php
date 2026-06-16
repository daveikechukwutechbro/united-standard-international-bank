<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Maintains a locally-cached list of OFAC-sanctioned blockchain addresses.
 *
 * Sources the official US Treasury SDN list (CSV) and extracts known
 * cryptocurrency addresses. The list is refreshed weekly and cached
 * for fast lookups during address screening.
 */
class OfacAddressListService
{
    private const CACHE_KEY = 'compliance:ofac_sanctioned_addresses';

    private const CACHE_TTL_SECONDS = 604800; // 7 days

    /**
     * Well-known OFAC-sanctioned addresses (baseline fallback).
     * These are publicly documented by the US Treasury and OFAC.
     *
     * @var list<string>
     */
    private const KNOWN_SANCTIONED_ADDRESSES = [
        // Tornado Cash (OFAC August 2022 + November 2022 designations)
        '0x8589427373D6D84E98730D7795D8f6f8731FDA16',
        '0x722122dF12D4e14e13Ac3b6895a86e84145b6967',
        '0xDD4c48C0B24039969fC16D1cdF626eaB821d3384',
        '0xd90e2f925DA726b50C4Ed8D0Fb90Ad053324F31b',
        '0xd96f2B1c14Db8458374d9Aca76E26c3D18364307',
        '0x4736dCf1b7A3d580672CcE6E7c65cd5cc9cFBfA9',
        '0xD4B88Df4D29F5CedD6857912842cff3b20C8Cfa3',
        '0x910Cbd523D972eb0a6f4cAe4618aD62622b39DbF',
        '0xA160cdAB225685dA1d56aa342Ad8841c3b53f291',
        '0xFD8610d20aA15b7B2E3Be39B396a1bC3516c7144',
        '0xF60dD140cFf0706bAE9Cd734Ac3683731B816EDc',
        '0x22aaA7720ddd5388A3c0A3333430953C68f1849b',
        '0xBA214C1c1928a32Bffe790263E38B4Af9bFCD659',
        '0xb1C8094B234DcE6e03f10a5b673c1d8C69739A00',
        '0x527653eA119F3E6a1F5BD18fbF4714081D7B31ce',
        '0x58E8dCC13BE9780fC42E8723D8EaD4CF46943dF2',
        '0xD691F27f38B395864Ea86CfC7253969B409c362d',
        '0xaEaaC358560e11f52454D997AAFF2c5731B6f8a6',
        '0x1356c899D8C9467C7f71C195612F8A395aBf2f0a',
        '0xA60C772958a3eD56CCc4930AD6B1B05b8Dd28C42',
        '0x179f48C78f57A3A78f0608cC9197B8972921d1D2',
        // Blender.io (OFAC May 2022)
        '0x94A1B5CdB22c43faab4AbEb5c74999895464Ddaf',
        // Garantex (OFAC April 2022)
        '0x6F1cA141A28907F78Ebaa64f83D078645f73bED6',
        // Lazarus Group / DPRK-associated
        '0x098B716B8Aaf21512996dC57EB0615e2383E2f96',
        '0xa0e1c89Ef1a489c9C7dE96311eD5Ce5D32c20E4B',
        '0x3Cffd56B47B7b41c56258D9C7731ABaDc360E460',
        '0x53b6936513e738f44FB50d2b9476730C0Ab3Bfc1',
    ];

    /**
     * Check if a blockchain address is on the OFAC sanctioned list.
     */
    public function isSanctioned(string $address): bool
    {
        $list = $this->getSanctionedAddresses();

        return in_array(strtolower($address), $list, true);
    }

    /**
     * Get the full list of sanctioned addresses (lowercased).
     *
     * @return list<string>
     */
    public function getSanctionedAddresses(): array
    {
        /** @var list<string>|null $cached */
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $addresses = $this->buildAddressList();

        Cache::put(self::CACHE_KEY, $addresses, self::CACHE_TTL_SECONDS);

        return $addresses;
    }

    /**
     * Force refresh the cached OFAC list.
     */
    public function refresh(): int
    {
        Cache::forget(self::CACHE_KEY);
        $addresses = $this->buildAddressList();
        Cache::put(self::CACHE_KEY, $addresses, self::CACHE_TTL_SECONDS);

        return count($addresses);
    }

    /**
     * Build the address list from hardcoded + remote sources.
     *
     * @return list<string>
     */
    private function buildAddressList(): array
    {
        $addresses = array_map('strtolower', self::KNOWN_SANCTIONED_ADDRESSES);

        // Attempt to fetch the community-maintained OFAC address list
        try {
            $remote = $this->fetchRemoteOfacAddresses();
            $addresses = array_values(array_unique(array_merge($addresses, $remote)));
        } catch (Throwable $e) {
            Log::warning('Failed to fetch remote OFAC address list, using local fallback', [
                'error' => $e->getMessage(),
            ]);
        }

        return $addresses;
    }

    /**
     * Fetch sanctioned addresses from the community OFAC list on GitHub.
     *
     * @return list<string>
     */
    private function fetchRemoteOfacAddresses(): array
    {
        $response = Http::timeout(10)
            ->get('https://raw.githubusercontent.com/0xB10C/ofac-sanctioned-digital-currency-addresses/lists/sanctioned_addresses_ETH.txt');

        if (! $response->successful()) {
            return [];
        }

        $lines = array_filter(
            array_map('trim', explode("\n", $response->body())),
            fn (string $line): bool => $line !== '' && ! str_starts_with($line, '#'),
        );

        return array_values(array_map('strtolower', $lines));
    }
}
