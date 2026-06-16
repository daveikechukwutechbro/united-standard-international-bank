<?php

declare(strict_types=1);

use App\Domain\OpenBanking\Enums\ConsentPermission;
use App\Domain\OpenBanking\Enums\ConsentStatus;
use App\Domain\OpenBanking\Enums\OpenBankingStandard;

it('has all consent lifecycle statuses', function (): void {
    expect(ConsentStatus::cases())->toHaveCount(5);
    expect(ConsentStatus::AWAITING_AUTHORIZATION->value)->toBe('awaiting_authorization');
    expect(ConsentStatus::AUTHORIZED->value)->toBe('authorized');
    expect(ConsentStatus::REVOKED->value)->toBe('revoked');
});

it('identifies terminal statuses', function (): void {
    expect(ConsentStatus::REVOKED->isTerminal())->toBeTrue();
    expect(ConsentStatus::EXPIRED->isTerminal())->toBeTrue();
    expect(ConsentStatus::REJECTED->isTerminal())->toBeTrue();
    expect(ConsentStatus::AUTHORIZED->isTerminal())->toBeFalse();
    expect(ConsentStatus::AWAITING_AUTHORIZATION->isTerminal())->toBeFalse();
});

it('identifies active status', function (): void {
    expect(ConsentStatus::AUTHORIZED->isActive())->toBeTrue();
    expect(ConsentStatus::AWAITING_AUTHORIZATION->isActive())->toBeFalse();
});

it('has all consent permissions', function (): void {
    expect(ConsentPermission::cases())->toHaveCount(8);
    expect(ConsentPermission::READ_BALANCES->value)->toBe('ReadBalances');
    expect(ConsentPermission::READ_BALANCES->label())->toBe('Read Balances');
    expect(ConsentPermission::PAYMENT_INITIATION->value)->toBe('PaymentInitiation');
    expect(ConsentPermission::PAYMENT_INITIATION->label())->toBe('Payment Initiation');
});

it('has open banking standards', function (): void {
    expect(OpenBankingStandard::BERLIN_GROUP->value)->toBe('berlin_group');
    expect(OpenBankingStandard::UK_OB->label())->toBe('UK Open Banking');
});
