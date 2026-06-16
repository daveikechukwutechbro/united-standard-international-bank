<?php

declare(strict_types=1);

use App\Domain\Microfinance\Enums\GroupStatus;
use Tests\TestCase;

uses(TestCase::class);
use App\Domain\Microfinance\Enums\MeetingFrequency;
use App\Domain\Microfinance\Enums\MemberRole;
use App\Domain\Microfinance\Enums\ProvisionCategory;
use App\Domain\Microfinance\Enums\ShareAccountStatus;

// ---------------------------------------------------------------------------
// GroupStatus
// ---------------------------------------------------------------------------

it('has correct GroupStatus values', function (): void {
    expect(GroupStatus::PENDING->value)->toBe('pending');
    expect(GroupStatus::ACTIVE->value)->toBe('active');
    expect(GroupStatus::CLOSED->value)->toBe('closed');
});

it('returns correct GroupStatus labels', function (): void {
    expect(GroupStatus::PENDING->label())->toBe('Pending');
    expect(GroupStatus::ACTIVE->label())->toBe('Active');
    expect(GroupStatus::CLOSED->label())->toBe('Closed');
});

it('correctly identifies active GroupStatus', function (): void {
    expect(GroupStatus::ACTIVE->isActive())->toBeTrue();
    expect(GroupStatus::PENDING->isActive())->toBeFalse();
    expect(GroupStatus::CLOSED->isActive())->toBeFalse();
});

it('can create GroupStatus from string value', function (): void {
    expect(GroupStatus::from('active'))->toBe(GroupStatus::ACTIVE);
    expect(GroupStatus::from('pending'))->toBe(GroupStatus::PENDING);
    expect(GroupStatus::from('closed'))->toBe(GroupStatus::CLOSED);
});

// ---------------------------------------------------------------------------
// MemberRole
// ---------------------------------------------------------------------------

it('has correct MemberRole values', function (): void {
    expect(MemberRole::LEADER->value)->toBe('leader');
    expect(MemberRole::SECRETARY->value)->toBe('secretary');
    expect(MemberRole::TREASURER->value)->toBe('treasurer');
    expect(MemberRole::MEMBER->value)->toBe('member');
});

it('returns correct MemberRole labels', function (): void {
    expect(MemberRole::LEADER->label())->toBe('Leader');
    expect(MemberRole::SECRETARY->label())->toBe('Secretary');
    expect(MemberRole::TREASURER->label())->toBe('Treasurer');
    expect(MemberRole::MEMBER->label())->toBe('Member');
});

it('provides labels for all MemberRole cases', function (): void {
    foreach (MemberRole::cases() as $role) {
        expect($role->label())->toBeString();
        expect($role->label())->not->toBeEmpty();
    }
});

// ---------------------------------------------------------------------------
// MeetingFrequency
// ---------------------------------------------------------------------------

it('has correct MeetingFrequency values', function (): void {
    expect(MeetingFrequency::DAILY->value)->toBe('daily');
    expect(MeetingFrequency::WEEKLY->value)->toBe('weekly');
    expect(MeetingFrequency::BIWEEKLY->value)->toBe('biweekly');
    expect(MeetingFrequency::MONTHLY->value)->toBe('monthly');
});

it('returns correct MeetingFrequency labels', function (): void {
    expect(MeetingFrequency::DAILY->label())->toBe('Daily');
    expect(MeetingFrequency::WEEKLY->label())->toBe('Weekly');
    expect(MeetingFrequency::BIWEEKLY->label())->toBe('Bi-Weekly');
    expect(MeetingFrequency::MONTHLY->label())->toBe('Monthly');
});

it('provides labels for all MeetingFrequency cases', function (): void {
    foreach (MeetingFrequency::cases() as $freq) {
        expect($freq->label())->toBeString();
        expect($freq->label())->not->toBeEmpty();
    }
});

// ---------------------------------------------------------------------------
// ShareAccountStatus
// ---------------------------------------------------------------------------

it('has correct ShareAccountStatus values', function (): void {
    expect(ShareAccountStatus::ACTIVE->value)->toBe('active');
    expect(ShareAccountStatus::DORMANT->value)->toBe('dormant');
    expect(ShareAccountStatus::CLOSED->value)->toBe('closed');
});

it('returns correct ShareAccountStatus labels', function (): void {
    expect(ShareAccountStatus::ACTIVE->label())->toBe('Active');
    expect(ShareAccountStatus::DORMANT->label())->toBe('Dormant');
    expect(ShareAccountStatus::CLOSED->label())->toBe('Closed');
});

it('can create ShareAccountStatus from string value', function (): void {
    expect(ShareAccountStatus::from('active'))->toBe(ShareAccountStatus::ACTIVE);
    expect(ShareAccountStatus::from('dormant'))->toBe(ShareAccountStatus::DORMANT);
    expect(ShareAccountStatus::from('closed'))->toBe(ShareAccountStatus::CLOSED);
});

// ---------------------------------------------------------------------------
// ProvisionCategory
// ---------------------------------------------------------------------------

it('has correct ProvisionCategory values', function (): void {
    expect(ProvisionCategory::STANDARD->value)->toBe('standard');
    expect(ProvisionCategory::SUBSTANDARD->value)->toBe('substandard');
    expect(ProvisionCategory::DOUBTFUL->value)->toBe('doubtful');
    expect(ProvisionCategory::LOSS->value)->toBe('loss');
});

it('returns correct ProvisionCategory labels', function (): void {
    expect(ProvisionCategory::STANDARD->label())->toBe('Standard');
    expect(ProvisionCategory::SUBSTANDARD->label())->toBe('Substandard');
    expect(ProvisionCategory::DOUBTFUL->label())->toBe('Doubtful');
    expect(ProvisionCategory::LOSS->label())->toBe('Loss');
});

it('returns provision rates from config defaults', function (): void {
    expect(ProvisionCategory::STANDARD->rate())->toBe(0.01);
    expect(ProvisionCategory::SUBSTANDARD->rate())->toBe(0.05);
    expect(ProvisionCategory::DOUBTFUL->rate())->toBe(0.50);
    expect(ProvisionCategory::LOSS->rate())->toBe(1.00);
});

it('returns minimum days overdue from config defaults', function (): void {
    expect(ProvisionCategory::STANDARD->minDaysOverdue())->toBe(30);
    expect(ProvisionCategory::SUBSTANDARD->minDaysOverdue())->toBe(90);
    expect(ProvisionCategory::DOUBTFUL->minDaysOverdue())->toBe(180);
    expect(ProvisionCategory::LOSS->minDaysOverdue())->toBe(365);
});

it('provision rates are ordered ascending', function (): void {
    $standard = ProvisionCategory::STANDARD->rate();
    $substandard = ProvisionCategory::SUBSTANDARD->rate();
    $doubtful = ProvisionCategory::DOUBTFUL->rate();
    $loss = ProvisionCategory::LOSS->rate();

    expect($standard)->toBeLessThan($substandard);
    expect($substandard)->toBeLessThan($doubtful);
    expect($doubtful)->toBeLessThan($loss);
});

it('minimum days overdue are ordered ascending', function (): void {
    $standard = ProvisionCategory::STANDARD->minDaysOverdue();
    $substandard = ProvisionCategory::SUBSTANDARD->minDaysOverdue();
    $doubtful = ProvisionCategory::DOUBTFUL->minDaysOverdue();
    $loss = ProvisionCategory::LOSS->minDaysOverdue();

    expect($standard)->toBeLessThan($substandard);
    expect($substandard)->toBeLessThan($doubtful);
    expect($doubtful)->toBeLessThan($loss);
});

it('provides labels for all ProvisionCategory cases', function (): void {
    foreach (ProvisionCategory::cases() as $category) {
        expect($category->label())->toBeString();
        expect($category->label())->not->toBeEmpty();
    }
});
