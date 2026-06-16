<?php

declare(strict_types=1);

use App\Domain\Commerce\Services\DemoOnChainSbtService;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    $this->service = new DemoOnChainSbtService();
});

describe('DemoOnChainSbtService', function (): void {
    describe('isAvailable', function (): void {
        it('is always available', function (): void {
            expect($this->service->isAvailable())->toBeTrue();
        });
    });

    describe('deployContract', function (): void {
        it('returns deterministic contract address', function (): void {
            $result1 = $this->service->deployContract('SBT', 'FASBT', 'https://finaegis.com/');
            $result2 = $this->service->deployContract('SBT', 'FASBT', 'https://finaegis.com/');

            expect($result1['contract_address'])->toBe($result2['contract_address']);
            expect($result1['contract_address'])->toStartWith('0x');
            expect($result1['network'])->toBe('polygon-demo');
        });

        it('returns different addresses for different contracts', function (): void {
            $result1 = $this->service->deployContract('SBT1', 'S1', 'https://finaegis.com/');
            $result2 = $this->service->deployContract('SBT2', 'S2', 'https://finaegis.com/');

            expect($result1['contract_address'])->not->toBe($result2['contract_address']);
        });
    });

    describe('mintToken', function (): void {
        it('mints a token with incrementing IDs', function (): void {
            $result1 = $this->service->mintToken('0xcontract', '0xrecipient', 'uri://1');
            $result2 = $this->service->mintToken('0xcontract', '0xrecipient', 'uri://2');

            expect($result1['token_id'])->toBe(1);
            expect($result2['token_id'])->toBe(2);
            expect($result1['tx_hash'])->toStartWith('0x');
            expect($result1['network'])->toBe('polygon-demo');
        });

        it('stores token data for retrieval', function (): void {
            $result = $this->service->mintToken('0xcontract', '0xrecipient', 'uri://test');

            expect($this->service->isTokenValid('0xcontract', $result['token_id']))->toBeTrue();
            expect($this->service->getTokenUri('0xcontract', $result['token_id']))->toBe('uri://test');
        });
    });

    describe('revokeToken', function (): void {
        it('revokes a minted token', function (): void {
            $mintResult = $this->service->mintToken('0xcontract', '0xrecipient', 'uri://1');
            $revokeResult = $this->service->revokeToken('0xcontract', $mintResult['token_id']);

            expect($revokeResult['tx_hash'])->toStartWith('0x');
            expect($this->service->isTokenValid('0xcontract', $mintResult['token_id']))->toBeFalse();
        });
    });

    describe('isTokenValid', function (): void {
        it('returns false for non-existent token', function (): void {
            expect($this->service->isTokenValid('0xcontract', 999))->toBeFalse();
        });
    });

    describe('getTokenUri', function (): void {
        it('returns empty string for non-existent token', function (): void {
            expect($this->service->getTokenUri('0xcontract', 999))->toBe('');
        });
    });
});
