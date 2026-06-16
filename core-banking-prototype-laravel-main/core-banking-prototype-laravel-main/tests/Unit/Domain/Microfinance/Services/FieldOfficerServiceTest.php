<?php

declare(strict_types=1);

use App\Domain\Microfinance\Enums\GroupStatus;
use App\Domain\Microfinance\Enums\MeetingFrequency;
use App\Domain\Microfinance\Models\CollectionSheet;
use App\Domain\Microfinance\Models\FieldOfficer;
use App\Domain\Microfinance\Models\Group;
use App\Domain\Microfinance\Services\FieldOfficerService;
use Tests\TestCase;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// Structural: methods exist with correct signatures
// ---------------------------------------------------------------------------

it('FieldOfficerService has assignOfficer method', function (): void {
    $service = new FieldOfficerService();
    expect((new ReflectionClass($service))->hasMethod('assignOfficer'))->toBeTrue();
});

it('FieldOfficerService has generateCollectionSheet method', function (): void {
    $service = new FieldOfficerService();
    expect((new ReflectionClass($service))->hasMethod('generateCollectionSheet'))->toBeTrue();
});

it('FieldOfficerService has recordCollection method', function (): void {
    $service = new FieldOfficerService();
    expect((new ReflectionClass($service))->hasMethod('recordCollection'))->toBeTrue();
});

it('FieldOfficerService has getCollectionSheets method', function (): void {
    $service = new FieldOfficerService();
    expect((new ReflectionClass($service))->hasMethod('getCollectionSheets'))->toBeTrue();
});

it('FieldOfficerService has syncOfficer method', function (): void {
    $service = new FieldOfficerService();
    expect((new ReflectionClass($service))->hasMethod('syncOfficer'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Behavioral
// ---------------------------------------------------------------------------

it('assigns a field officer with zero client count', function (): void {
    $service = new FieldOfficerService();
    $user = App\Models\User::factory()->create();
    $officer = $service->assignOfficer($user->id, 'Alice Smith', 'North Territory');

    expect($officer)->toBeInstanceOf(FieldOfficer::class);
    expect($officer->name)->toBe('Alice Smith');
    expect($officer->territory)->toBe('North Territory');
    expect($officer->client_count)->toBe(0);
    expect($officer->is_active)->toBeTrue();
});

it('generates a collection sheet with pending status', function (): void {
    $service = new FieldOfficerService();
    $user = App\Models\User::factory()->create();
    $officer = $service->assignOfficer($user->id, 'Alice Smith');
    $group = Group::create([
        'name'              => 'Test Group',
        'meeting_frequency' => MeetingFrequency::WEEKLY,
        'status'            => GroupStatus::ACTIVE,
    ]);

    $sheet = $service->generateCollectionSheet(
        $officer->id,
        $group->id,
        '2026-04-01',
        '5000.00'
    );

    expect($sheet)->toBeInstanceOf(CollectionSheet::class);
    expect($sheet->status)->toBe('pending');
    expect((float) $sheet->collected_amount)->toBe(0.0);
    expect((float) $sheet->expected_amount)->toBe(5000.00);
});

it('records collection and sets status to completed when fully collected', function (): void {
    $service = new FieldOfficerService();
    $user = App\Models\User::factory()->create();
    $officer = $service->assignOfficer($user->id, 'Alice Smith');
    $group = Group::create([
        'name'              => 'Completed Group',
        'meeting_frequency' => MeetingFrequency::WEEKLY,
        'status'            => GroupStatus::ACTIVE,
    ]);

    $sheet = $service->generateCollectionSheet(
        $officer->id,
        $group->id,
        '2026-04-01',
        '5000.00'
    );

    $updated = $service->recordCollection($sheet->id, '5000.00');

    expect($updated->status)->toBe('completed');
    expect((float) $updated->collected_amount)->toBe(5000.00);
});

it('sets status to in_progress when partially collected', function (): void {
    $service = new FieldOfficerService();
    $user = App\Models\User::factory()->create();
    $officer = $service->assignOfficer($user->id, 'Alice Smith');
    $group = Group::create([
        'name'              => 'Partial Group',
        'meeting_frequency' => MeetingFrequency::WEEKLY,
        'status'            => GroupStatus::ACTIVE,
    ]);

    $sheet = $service->generateCollectionSheet(
        $officer->id,
        $group->id,
        '2026-04-01',
        '5000.00'
    );

    $updated = $service->recordCollection($sheet->id, '2500.00');

    expect($updated->status)->toBe('in_progress');
});

it('returns collection sheets for an officer', function (): void {
    $service = new FieldOfficerService();
    $user = App\Models\User::factory()->create();
    $officer = $service->assignOfficer($user->id, 'Alice Smith');
    $group1 = Group::create([
        'name'              => 'Sheets Group 1',
        'meeting_frequency' => MeetingFrequency::WEEKLY,
        'status'            => GroupStatus::ACTIVE,
    ]);
    $group2 = Group::create([
        'name'              => 'Sheets Group 2',
        'meeting_frequency' => MeetingFrequency::WEEKLY,
        'status'            => GroupStatus::ACTIVE,
    ]);

    $service->generateCollectionSheet(
        $officer->id,
        $group1->id,
        '2026-04-01',
        '1000.00'
    );

    $service->generateCollectionSheet(
        $officer->id,
        $group2->id,
        '2026-04-02',
        '2000.00'
    );

    $sheets = $service->getCollectionSheets($officer->id);

    expect($sheets)->toHaveCount(2);
});

it('filters collection sheets by date', function (): void {
    $service = new FieldOfficerService();
    $user = App\Models\User::factory()->create();
    $officer = $service->assignOfficer($user->id, 'Alice Smith');
    $group1 = Group::create([
        'name'              => 'Filter Group 1',
        'meeting_frequency' => MeetingFrequency::WEEKLY,
        'status'            => GroupStatus::ACTIVE,
    ]);
    $group2 = Group::create([
        'name'              => 'Filter Group 2',
        'meeting_frequency' => MeetingFrequency::WEEKLY,
        'status'            => GroupStatus::ACTIVE,
    ]);

    $service->generateCollectionSheet(
        $officer->id,
        $group1->id,
        '2026-04-01',
        '1000.00'
    );

    $service->generateCollectionSheet(
        $officer->id,
        $group2->id,
        '2026-04-02',
        '2000.00'
    );

    $sheets = $service->getCollectionSheets($officer->id, '2026-04-01');

    expect($sheets)->toHaveCount(1);
});

it('syncs officer and updates last_sync_at', function (): void {
    $service = new FieldOfficerService();
    $user = App\Models\User::factory()->create();
    $officer = $service->assignOfficer($user->id, 'Alice Smith');

    expect($officer->last_sync_at)->toBeNull();

    $synced = $service->syncOfficer($officer->id);

    expect($synced->last_sync_at)->not->toBeNull();
});

it('throws when operating on non-existent field officer', function (): void {
    $service = new FieldOfficerService();

    expect(fn () => $service->syncOfficer('non-existent-uuid'))
        ->toThrow(RuntimeException::class);
});
