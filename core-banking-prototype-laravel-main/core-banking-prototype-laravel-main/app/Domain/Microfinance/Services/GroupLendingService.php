<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Services;

use App\Domain\Microfinance\Enums\GroupStatus;
use App\Domain\Microfinance\Enums\MeetingFrequency;
use App\Domain\Microfinance\Enums\MemberRole;
use App\Domain\Microfinance\Models\Group;
use App\Domain\Microfinance\Models\GroupMeeting;
use App\Domain\Microfinance\Models\GroupMember;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class GroupLendingService
{
    /**
     * Create a new joint liability group.
     *
     * @throws InvalidArgumentException
     */
    public function createGroup(
        string $name,
        string $meetingFrequency,
        ?string $centerName = null,
        ?string $meetingDay = null,
    ): Group {
        $frequency = MeetingFrequency::from($meetingFrequency);

        $group = Group::create([
            'name'              => $name,
            'meeting_frequency' => $frequency,
            'center_name'       => $centerName,
            'meeting_day'       => $meetingDay,
            'status'            => GroupStatus::PENDING,
        ]);

        return $group;
    }

    /**
     * Activate a pending group.
     *
     * @throws RuntimeException
     */
    public function activateGroup(string $groupId): Group
    {
        $group = Group::find($groupId);

        if ($group === null) {
            throw new RuntimeException("Group not found: {$groupId}");
        }

        $group->update([
            'status'          => GroupStatus::ACTIVE,
            'activation_date' => Carbon::today(),
        ]);

        return $group->fresh() ?? $group;
    }

    /**
     * Close an active group.
     *
     * @throws RuntimeException
     */
    public function closeGroup(string $groupId): Group
    {
        $group = Group::find($groupId);

        if ($group === null) {
            throw new RuntimeException("Group not found: {$groupId}");
        }

        $group->update([
            'status' => GroupStatus::CLOSED,
        ]);

        return $group->fresh() ?? $group;
    }

    /**
     * Add a member to a group.
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function addMember(string $groupId, int $userId, string $role = 'member'): GroupMember
    {
        $group = Group::find($groupId);

        if ($group === null) {
            throw new RuntimeException("Group not found: {$groupId}");
        }

        $maxSize = (int) config('microfinance.max_group_size', 30);
        $currentCount = GroupMember::where('group_id', $groupId)
            ->where('is_active', true)
            ->count();

        if ($currentCount >= $maxSize) {
            throw new RuntimeException("Group has reached maximum size of {$maxSize} members.");
        }

        $memberRole = MemberRole::from($role);

        $member = GroupMember::create([
            'group_id'  => $groupId,
            'user_id'   => $userId,
            'role'      => $memberRole,
            'joined_at' => Carbon::today(),
            'is_active' => true,
        ]);

        return $member;
    }

    /**
     * Remove a member from a group (soft removal).
     *
     * @throws RuntimeException
     */
    public function removeMember(string $groupId, int $userId): void
    {
        $member = GroupMember::where('group_id', $groupId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if ($member === null) {
            throw new RuntimeException("Active member not found in group {$groupId} for user {$userId}.");
        }

        $member->update([
            'left_at'   => Carbon::today(),
            'is_active' => false,
        ]);
    }

    /**
     * Get active members of a group.
     *
     * @return Collection<int, GroupMember>
     *
     * @throws RuntimeException
     */
    public function getGroupMembers(string $groupId): Collection
    {
        $group = Group::find($groupId);

        if ($group === null) {
            throw new RuntimeException("Group not found: {$groupId}");
        }

        return GroupMember::where('group_id', $groupId)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Record a group meeting and calculate next meeting date.
     *
     * @throws RuntimeException
     */
    public function recordMeeting(
        string $groupId,
        int $attendeesCount,
        ?string $minutes = null,
    ): GroupMeeting {
        $group = Group::find($groupId);

        if ($group === null) {
            throw new RuntimeException("Group not found: {$groupId}");
        }

        $totalMembers = GroupMember::where('group_id', $groupId)
            ->where('is_active', true)
            ->count();

        $today = Carbon::today();
        $nextMeetingDate = $this->calculateNextMeetingDate($today, $group->meeting_frequency);

        $meeting = GroupMeeting::create([
            'group_id'          => $groupId,
            'meeting_date'      => $today,
            'attendees_count'   => $attendeesCount,
            'total_members'     => $totalMembers,
            'minutes'           => $minutes,
            'next_meeting_date' => $nextMeetingDate,
        ]);

        return $meeting;
    }

    private function calculateNextMeetingDate(Carbon $from, MeetingFrequency $frequency): Carbon
    {
        return match ($frequency) {
            MeetingFrequency::DAILY    => $from->copy()->addDay(),
            MeetingFrequency::WEEKLY   => $from->copy()->addWeek(),
            MeetingFrequency::BIWEEKLY => $from->copy()->addWeeks(2),
            MeetingFrequency::MONTHLY  => $from->copy()->addMonth(),
        };
    }
}
