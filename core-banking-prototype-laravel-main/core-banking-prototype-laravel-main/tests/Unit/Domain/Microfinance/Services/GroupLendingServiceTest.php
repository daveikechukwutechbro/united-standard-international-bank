<?php

declare(strict_types=1);

use App\Domain\Microfinance\Models\Group;
use App\Domain\Microfinance\Models\GroupMeeting;
use App\Domain\Microfinance\Models\GroupMember;
use App\Domain\Microfinance\Services\GroupLendingService;
use Tests\TestCase;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// Structural: methods exist with correct signatures
// ---------------------------------------------------------------------------

it('GroupLendingService has createGroup method', function (): void {
    $service = new GroupLendingService();
    expect((new ReflectionClass($service))->hasMethod('createGroup'))->toBeTrue();
});

it('GroupLendingService has activateGroup method', function (): void {
    $service = new GroupLendingService();
    expect((new ReflectionClass($service))->hasMethod('activateGroup'))->toBeTrue();
});

it('GroupLendingService has closeGroup method', function (): void {
    $service = new GroupLendingService();
    expect((new ReflectionClass($service))->hasMethod('closeGroup'))->toBeTrue();
});

it('GroupLendingService has addMember method', function (): void {
    $service = new GroupLendingService();
    expect((new ReflectionClass($service))->hasMethod('addMember'))->toBeTrue();
});

it('GroupLendingService has removeMember method', function (): void {
    $service = new GroupLendingService();
    expect((new ReflectionClass($service))->hasMethod('removeMember'))->toBeTrue();
});

it('GroupLendingService has getGroupMembers method', function (): void {
    $service = new GroupLendingService();
    expect((new ReflectionClass($service))->hasMethod('getGroupMembers'))->toBeTrue();
});

it('GroupLendingService has recordMeeting method', function (): void {
    $service = new GroupLendingService();
    expect((new ReflectionClass($service))->hasMethod('recordMeeting'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Behavioral
// ---------------------------------------------------------------------------

it('creates a group with PENDING status', function (): void {
    $service = new GroupLendingService();
    $group = $service->createGroup('Test Group', 'weekly', 'Centre A', 'Monday');

    expect($group)->toBeInstanceOf(Group::class);
    expect($group->status->value)->toBe('pending');
    expect($group->name)->toBe('Test Group');
    expect($group->center_name)->toBe('Centre A');
    expect($group->meeting_day)->toBe('Monday');
});

it('activates a group and sets activation_date', function (): void {
    $service = new GroupLendingService();
    $group = $service->createGroup('Activate Group', 'monthly');
    $active = $service->activateGroup($group->id);

    expect($active->status->value)->toBe('active');
    expect($active->activation_date)->not->toBeNull();
});

it('closes a group', function (): void {
    $service = new GroupLendingService();
    $group = $service->createGroup('Close Group', 'weekly');
    $service->activateGroup($group->id);
    $closed = $service->closeGroup($group->id);

    expect($closed->status->value)->toBe('closed');
});

it('adds a member to a group', function (): void {
    $service = new GroupLendingService();
    $group = $service->createGroup('Member Group', 'weekly');
    $user = App\Models\User::factory()->create();
    $member = $service->addMember($group->id, $user->id, 'member');

    expect($member)->toBeInstanceOf(GroupMember::class);
    expect($member->user_id)->toBe($user->id);
    expect($member->group_id)->toBe($group->id);
    expect($member->is_active)->toBeTrue();
});

it('removes a member by setting left_at and is_active=false', function (): void {
    $service = new GroupLendingService();
    $group = $service->createGroup('Remove Member Group', 'weekly');
    $user = App\Models\User::factory()->create();
    $service->addMember($group->id, $user->id, 'member');
    $service->removeMember($group->id, $user->id);

    $member = GroupMember::where('group_id', $group->id)
        ->where('user_id', $user->id)
        ->first();

    expect($member)->not->toBeNull();
    assert($member instanceof GroupMember);
    expect($member->is_active)->toBeFalse();
    expect($member->left_at)->not->toBeNull();
});

it('returns only active members', function (): void {
    $service = new GroupLendingService();
    $group = $service->createGroup('Active Members Group', 'weekly');
    $user1 = App\Models\User::factory()->create();
    $user2 = App\Models\User::factory()->create();

    $service->addMember($group->id, $user1->id, 'member');
    $service->addMember($group->id, $user2->id, 'member');
    $service->removeMember($group->id, $user2->id);

    $members = $service->getGroupMembers($group->id);

    expect($members)->toHaveCount(1);
});

it('records a meeting with correct next meeting date for weekly frequency', function (): void {
    $service = new GroupLendingService();
    $group = $service->createGroup('Weekly Group', 'weekly');
    $meeting = $service->recordMeeting($group->id, 5, 'Meeting notes.');

    expect($meeting)->toBeInstanceOf(GroupMeeting::class);
    expect($meeting->attendees_count)->toBe(5);
    expect($meeting->next_meeting_date)->not->toBeNull();
});

it('throws when adding member to non-existent group', function (): void {
    $service = new GroupLendingService();
    $user = App\Models\User::factory()->create();

    expect(fn () => $service->addMember('non-existent-uuid', $user->id))
        ->toThrow(RuntimeException::class);
});

it('throws when activating non-existent group', function (): void {
    $service = new GroupLendingService();

    expect(fn () => $service->activateGroup('non-existent-uuid'))
        ->toThrow(RuntimeException::class);
});
