<?php

declare(strict_types=1);

use App\Domain\PaymentRails\Enums\AchReturnCode;
use App\Domain\PaymentRails\Enums\AchSecCode;
use App\Domain\PaymentRails\Enums\PaymentRail;
use App\Domain\PaymentRails\Enums\RailStatus;
use Tests\TestCase;

uses(TestCase::class);

describe('PaymentRail enum', function (): void {
    it('has all seven rail cases', function (): void {
        expect(PaymentRail::cases())->toHaveCount(7);
        expect(PaymentRail::ACH->value)->toBe('ach');
        expect(PaymentRail::FEDWIRE->value)->toBe('fedwire');
        expect(PaymentRail::RTP->value)->toBe('rtp');
        expect(PaymentRail::FEDNOW->value)->toBe('fednow');
        expect(PaymentRail::SEPA->value)->toBe('sepa');
        expect(PaymentRail::SEPA_INSTANT->value)->toBe('sepa_instant');
        expect(PaymentRail::SWIFT->value)->toBe('swift');
    });

    it('returns correct labels', function (): void {
        expect(PaymentRail::ACH->label())->toBe('ACH');
        expect(PaymentRail::FEDWIRE->label())->toBe('Fedwire');
        expect(PaymentRail::RTP->label())->toBe('RTP');
        expect(PaymentRail::FEDNOW->label())->toBe('FedNow');
        expect(PaymentRail::SEPA->label())->toBe('SEPA');
        expect(PaymentRail::SEPA_INSTANT->label())->toBe('SEPA Instant');
        expect(PaymentRail::SWIFT->label())->toBe('SWIFT');
    });

    it('identifies instant rails correctly', function (): void {
        expect(PaymentRail::RTP->isInstant())->toBeTrue();
        expect(PaymentRail::FEDNOW->isInstant())->toBeTrue();
        expect(PaymentRail::SEPA_INSTANT->isInstant())->toBeTrue();

        expect(PaymentRail::ACH->isInstant())->toBeFalse();
        expect(PaymentRail::FEDWIRE->isInstant())->toBeFalse();
        expect(PaymentRail::SEPA->isInstant())->toBeFalse();
        expect(PaymentRail::SWIFT->isInstant())->toBeFalse();
    });

    it('returns max amounts for bounded rails', function (): void {
        expect(PaymentRail::RTP->maxAmount())->toBe(100000000);
        expect(PaymentRail::FEDNOW->maxAmount())->toBe(50000000);

        expect(PaymentRail::ACH->maxAmount())->toBeNull();
        expect(PaymentRail::FEDWIRE->maxAmount())->toBeNull();
        expect(PaymentRail::SEPA->maxAmount())->toBeNull();
        expect(PaymentRail::SEPA_INSTANT->maxAmount())->toBeNull();
        expect(PaymentRail::SWIFT->maxAmount())->toBeNull();
    });
});

describe('RailStatus enum', function (): void {
    it('has all seven status cases', function (): void {
        expect(RailStatus::cases())->toHaveCount(7);
        expect(RailStatus::INITIATED->value)->toBe('initiated');
        expect(RailStatus::PENDING->value)->toBe('pending');
        expect(RailStatus::PROCESSING->value)->toBe('processing');
        expect(RailStatus::COMPLETED->value)->toBe('completed');
        expect(RailStatus::FAILED->value)->toBe('failed');
        expect(RailStatus::RETURNED->value)->toBe('returned');
        expect(RailStatus::CANCELLED->value)->toBe('cancelled');
    });

    it('returns correct labels', function (): void {
        expect(RailStatus::INITIATED->label())->toBe('Initiated');
        expect(RailStatus::PENDING->label())->toBe('Pending');
        expect(RailStatus::PROCESSING->label())->toBe('Processing');
        expect(RailStatus::COMPLETED->label())->toBe('Completed');
        expect(RailStatus::FAILED->label())->toBe('Failed');
        expect(RailStatus::RETURNED->label())->toBe('Returned');
        expect(RailStatus::CANCELLED->label())->toBe('Cancelled');
    });

    it('identifies terminal statuses correctly', function (): void {
        expect(RailStatus::COMPLETED->isTerminal())->toBeTrue();
        expect(RailStatus::FAILED->isTerminal())->toBeTrue();
        expect(RailStatus::RETURNED->isTerminal())->toBeTrue();
        expect(RailStatus::CANCELLED->isTerminal())->toBeTrue();

        expect(RailStatus::INITIATED->isTerminal())->toBeFalse();
        expect(RailStatus::PENDING->isTerminal())->toBeFalse();
        expect(RailStatus::PROCESSING->isTerminal())->toBeFalse();
    });

    it('identifies successful status correctly', function (): void {
        expect(RailStatus::COMPLETED->isSuccessful())->toBeTrue();

        expect(RailStatus::FAILED->isSuccessful())->toBeFalse();
        expect(RailStatus::RETURNED->isSuccessful())->toBeFalse();
        expect(RailStatus::CANCELLED->isSuccessful())->toBeFalse();
        expect(RailStatus::INITIATED->isSuccessful())->toBeFalse();
        expect(RailStatus::PENDING->isSuccessful())->toBeFalse();
        expect(RailStatus::PROCESSING->isSuccessful())->toBeFalse();
    });
});

describe('AchSecCode enum', function (): void {
    it('has all five SEC code cases', function (): void {
        expect(AchSecCode::cases())->toHaveCount(5);
        expect(AchSecCode::PPD->value)->toBe('PPD');
        expect(AchSecCode::CCD->value)->toBe('CCD');
        expect(AchSecCode::WEB->value)->toBe('WEB');
        expect(AchSecCode::TEL->value)->toBe('TEL');
        expect(AchSecCode::CTX->value)->toBe('CTX');
    });

    it('returns correct labels', function (): void {
        expect(AchSecCode::PPD->label())->toBe('Prearranged Payment/Deposit');
        expect(AchSecCode::CCD->label())->toBe('Corporate Credit/Debit');
        expect(AchSecCode::WEB->label())->toBe('Internet');
        expect(AchSecCode::TEL->label())->toBe('Telephone');
        expect(AchSecCode::CTX->label())->toBe('Corporate Trade Exchange');
    });
});

describe('AchReturnCode enum', function (): void {
    it('has all seven return code cases', function (): void {
        expect(AchReturnCode::cases())->toHaveCount(7);
        expect(AchReturnCode::R01->value)->toBe('R01');
        expect(AchReturnCode::R02->value)->toBe('R02');
        expect(AchReturnCode::R03->value)->toBe('R03');
        expect(AchReturnCode::R04->value)->toBe('R04');
        expect(AchReturnCode::R08->value)->toBe('R08');
        expect(AchReturnCode::R10->value)->toBe('R10');
        expect(AchReturnCode::R29->value)->toBe('R29');
    });

    it('returns correct labels', function (): void {
        expect(AchReturnCode::R01->label())->toBe('Insufficient Funds');
        expect(AchReturnCode::R02->label())->toBe('Account Closed');
        expect(AchReturnCode::R03->label())->toBe('No Account');
        expect(AchReturnCode::R04->label())->toBe('Invalid Account Number');
        expect(AchReturnCode::R08->label())->toBe('Payment Stopped');
        expect(AchReturnCode::R10->label())->toBe('Customer Advises Unauthorized');
        expect(AchReturnCode::R29->label())->toBe('Corporate Customer Advises Not Authorized');
    });

    it('identifies recoverable return codes correctly', function (): void {
        expect(AchReturnCode::R01->isRecoverable())->toBeTrue();

        expect(AchReturnCode::R02->isRecoverable())->toBeFalse();
        expect(AchReturnCode::R03->isRecoverable())->toBeFalse();
        expect(AchReturnCode::R04->isRecoverable())->toBeFalse();
        expect(AchReturnCode::R08->isRecoverable())->toBeFalse();
        expect(AchReturnCode::R10->isRecoverable())->toBeFalse();
        expect(AchReturnCode::R29->isRecoverable())->toBeFalse();
    });
});
