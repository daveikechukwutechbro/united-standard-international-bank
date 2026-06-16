<?php

declare(strict_types=1);

use App\Infrastructure\Web3\AbiEncoder;

describe('AbiEncoder', function () {
    beforeEach(function () {
        $this->encoder = new AbiEncoder();
    });

    describe('encodeAddress', function () {
        it('pads address to 32 bytes left-padded', function () {
            $result = $this->encoder->encodeAddress('0xdAC17F958D2ee523a2206206994597C13D831ec7');

            expect($result)->toHaveLength(64);
            expect($result)->toEndWith('dac17f958d2ee523a2206206994597c13d831ec7');
            // Left-padded with zeros
            expect(substr($result, 0, 24))->toBe('000000000000000000000000');
        });

        it('handles address without 0x prefix', function () {
            $result = $this->encoder->encodeAddress('dAC17F958D2ee523a2206206994597C13D831ec7');

            expect($result)->toHaveLength(64);
        });

        it('throws for invalid address length', function () {
            expect(fn () => $this->encoder->encodeAddress('0x' . str_repeat('a', 42)))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('encodeUint256', function () {
        it('encodes zero', function () {
            $result = $this->encoder->encodeUint256('0');

            expect($result)->toHaveLength(64);
            expect($result)->toBe(str_repeat('0', 64));
        });

        it('encodes small number', function () {
            $result = $this->encoder->encodeUint256('255');

            expect($result)->toHaveLength(64);
            expect($result)->toEndWith('ff');
        });

        it('encodes large number (wei-scale)', function () {
            // 1 ETH = 1000000000000000000 wei
            $result = $this->encoder->encodeUint256('1000000000000000000');

            expect($result)->toHaveLength(64);
            expect($result)->toEndWith('de0b6b3a7640000');
        });

        it('throws for negative values', function () {
            expect(fn () => $this->encoder->encodeUint256('-1'))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('encodeBytes32', function () {
        it('right-pads bytes32 value', function () {
            $result = $this->encoder->encodeBytes32('abcdef');

            expect($result)->toHaveLength(64);
            expect($result)->toStartWith('abcdef');
        });

        it('handles 0x prefix', function () {
            $result = $this->encoder->encodeBytes32('0xabcdef');

            expect($result)->toHaveLength(64);
            expect($result)->toStartWith('abcdef');
        });

        it('throws for value longer than 32 bytes', function () {
            $tooLong = str_repeat('ab', 33);

            expect(fn () => $this->encoder->encodeBytes32($tooLong))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('encodeUint16', function () {
        it('encodes zero', function () {
            $result = $this->encoder->encodeUint16(0);

            expect($result)->toHaveLength(64);
            expect($result)->toBe(str_repeat('0', 64));
        });

        it('encodes valid uint16', function () {
            $result = $this->encoder->encodeUint16(256);

            expect($result)->toHaveLength(64);
            expect($result)->toEndWith('100');
        });

        it('throws for out of range', function () {
            expect(fn () => $this->encoder->encodeUint16(65536))->toThrow(InvalidArgumentException::class);
            expect(fn () => $this->encoder->encodeUint16(-1))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('functionSelector', function () {
        it('returns 0x-prefixed 4-byte selector', function () {
            $result = $this->encoder->functionSelector('transfer(address,uint256)');

            expect($result)->toStartWith('0x');
            expect(strlen($result))->toBe(10); // 0x + 8 hex chars
        });

        it('produces different selectors for different functions', function () {
            $selector1 = $this->encoder->functionSelector('transfer(address,uint256)');
            $selector2 = $this->encoder->functionSelector('approve(address,uint256)');

            expect($selector1)->not->toBe($selector2);
        });

        it('produces consistent selectors for same function', function () {
            $selector1 = $this->encoder->functionSelector('transferTokens(address,uint256,uint16,bytes32,uint256,uint256)');
            $selector2 = $this->encoder->functionSelector('transferTokens(address,uint256,uint16,bytes32,uint256,uint256)');

            expect($selector1)->toBe($selector2);
        });
    });

    describe('encodeFunctionCall', function () {
        it('produces selector + encoded params', function () {
            $result = $this->encoder->encodeFunctionCall(
                'transfer(address,uint256)',
                [
                    $this->encoder->encodeAddress('0xdAC17F958D2ee523a2206206994597C13D831ec7'),
                    $this->encoder->encodeUint256('1000000'),
                ],
            );

            // 0x + 8 chars selector + 64 chars address + 64 chars uint256
            expect($result)->toStartWith('0x');
            expect(strlen($result))->toBe(2 + 8 + 64 + 64);
        });
    });

    describe('decodeResponse', function () {
        it('decodes uint256 response', function () {
            // Encode 1000000 then decode
            $encoded = '0x' . $this->encoder->encodeUint256('1000000');
            $decoded = $this->encoder->decodeResponse($encoded, ['uint256']);

            expect($decoded[0])->toBe('1000000');
        });

        it('decodes multiple values', function () {
            $encoded = '0x'
                . $this->encoder->encodeUint256('500')
                . $this->encoder->encodeUint256('100');

            $decoded = $this->encoder->decodeResponse($encoded, ['uint256', 'uint256']);

            expect($decoded[0])->toBe('500');
            expect($decoded[1])->toBe('100');
        });

        it('handles short data gracefully', function () {
            $decoded = $this->encoder->decodeResponse('0x', ['uint256']);

            expect($decoded[0])->toBe('0');
        });

        it('decodes address type', function () {
            $addr = 'dac17f958d2ee523a2206206994597c13d831ec7';
            $encoded = '0x' . str_pad($addr, 64, '0', STR_PAD_LEFT);

            $decoded = $this->encoder->decodeResponse($encoded, ['address']);

            expect($decoded[0])->toBe('0x' . $addr);
        });
    });

    describe('encodeStruct', function () {
        it('concatenates encoded fields', function () {
            $fields = [
                $this->encoder->encodeAddress('0xdAC17F958D2ee523a2206206994597C13D831ec7'),
                $this->encoder->encodeUint256('1000'),
            ];

            $result = $this->encoder->encodeStruct($fields);

            expect(strlen($result))->toBe(128); // 64 + 64
        });
    });

    describe('toSmallestUnit', function () {
        it('converts with 18 decimals', function () {
            $result = $this->encoder->toSmallestUnit('1.0', 18);

            expect($result)->toBe('1000000000000000000');
        });

        it('converts with 6 decimals (USDC)', function () {
            $result = $this->encoder->toSmallestUnit('100.50', 6);

            expect($result)->toBe('100500000');
        });

        it('converts whole numbers', function () {
            $result = $this->encoder->toSmallestUnit('1000', 18);

            expect($result)->toBe('1000000000000000000000');
        });
    });
});
