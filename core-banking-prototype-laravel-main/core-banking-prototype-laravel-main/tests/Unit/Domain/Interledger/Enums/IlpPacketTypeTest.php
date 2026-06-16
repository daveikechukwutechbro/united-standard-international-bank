<?php

declare(strict_types=1);

use App\Domain\Interledger\Enums\IlpPacketType;

describe('IlpPacketType enum', function (): void {
    it('has the correct string values', function (): void {
        expect(IlpPacketType::PREPARE->value)->toBe('prepare');
        expect(IlpPacketType::FULFILL->value)->toBe('fulfill');
        expect(IlpPacketType::REJECT->value)->toBe('reject');
    });

    it('can be created from a valid string value', function (): void {
        expect(IlpPacketType::from('prepare'))->toBe(IlpPacketType::PREPARE);
        expect(IlpPacketType::from('fulfill'))->toBe(IlpPacketType::FULFILL);
        expect(IlpPacketType::from('reject'))->toBe(IlpPacketType::REJECT);
    });

    it('returns correct human-readable labels', function (): void {
        expect(IlpPacketType::PREPARE->label())->toBe('Prepare');
        expect(IlpPacketType::FULFILL->label())->toBe('Fulfill');
        expect(IlpPacketType::REJECT->label())->toBe('Reject');
    });

    it('covers all three packet types in the enum', function (): void {
        $cases = IlpPacketType::cases();

        expect($cases)->toHaveCount(3);
    });
});
