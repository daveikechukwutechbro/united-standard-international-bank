<?php

declare(strict_types=1);

namespace App\Domain\Governance\Enums;

enum PollStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case CLOSED = 'closed';
    case EXECUTED = 'executed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::PENDING   => 'Pending',
            self::ACTIVE    => 'Active',
            self::CLOSED    => 'Closed',
            self::EXECUTED  => 'Executed',
            self::CANCELLED => 'Cancelled',
            self::FAILED    => 'Failed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT     => 'gray',
            self::PENDING   => 'yellow',
            self::ACTIVE    => 'green',
            self::CLOSED    => 'blue',
            self::EXECUTED  => 'purple',
            self::CANCELLED => 'red',
            self::FAILED    => 'red',
        };
    }

    public function canVote(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canModify(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING]);
    }

    public function isFinalized(): bool
    {
        return in_array($this, [self::EXECUTED, self::CANCELLED, self::FAILED]);
    }
}
