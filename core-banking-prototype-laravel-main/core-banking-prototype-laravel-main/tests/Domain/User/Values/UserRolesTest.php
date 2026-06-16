<?php

declare(strict_types=1);

namespace Tests\Domain\User\Values;

use App\Domain\User\Values\UserRoles;
use Tests\UnitTestCase;
use ValueError;

class UserRolesTest extends UnitTestCase
{
    // ===========================================
    // Enum Cases Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_business_case(): void
    {
        expect(UserRoles::BUSINESS)->toBeInstanceOf(UserRoles::class);
        expect(UserRoles::BUSINESS->value)->toBe('business');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_private_case(): void
    {
        expect(UserRoles::PRIVATE)->toBeInstanceOf(UserRoles::class);
        expect(UserRoles::PRIVATE->value)->toBe('private');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_admin_case(): void
    {
        expect(UserRoles::ADMIN)->toBeInstanceOf(UserRoles::class);
        expect(UserRoles::ADMIN->value)->toBe('admin');
    }

    // ===========================================
    // All Cases Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_has_exactly_three_cases(): void
    {
        $cases = UserRoles::cases();

        expect($cases)->toHaveCount(3);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_lists_all_cases(): void
    {
        $cases = UserRoles::cases();
        $values = array_map(fn (UserRoles $role) => $role->value, $cases);

        expect($values)->toContain('business', 'private', 'admin');
    }

    // ===========================================
    // From Value Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_valid_value(): void
    {
        expect(UserRoles::from('business'))->toBe(UserRoles::BUSINESS);
        expect(UserRoles::from('private'))->toBe(UserRoles::PRIVATE);
        expect(UserRoles::from('admin'))->toBe(UserRoles::ADMIN);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_null_for_invalid_value_with_try_from(): void
    {
        expect(UserRoles::tryFrom('invalid'))->toBeNull();
        expect(UserRoles::tryFrom(''))->toBeNull();
        expect(UserRoles::tryFrom('ADMIN'))->toBeNull(); // Case sensitive
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_for_invalid_value_with_from(): void
    {
        expect(fn () => UserRoles::from('invalid'))->toThrow(ValueError::class);
    }

    // ===========================================
    // Comparison Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_compares_enum_values(): void
    {
        // Use tryFrom to make comparison dynamic for PHPStan
        $role1 = UserRoles::tryFrom('business');
        $role2 = UserRoles::tryFrom('business');
        $role3 = UserRoles::tryFrom('admin');

        expect($role1)->toBe($role2);
        expect($role1)->not->toBe($role3);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_used_in_match_expressions(): void
    {
        $getPermissionLevel = fn (UserRoles $role) => match ($role) {
            UserRoles::ADMIN    => 'full',
            UserRoles::BUSINESS => 'business',
            UserRoles::PRIVATE  => 'limited',
        };

        expect($getPermissionLevel(UserRoles::ADMIN))->toBe('full');
        expect($getPermissionLevel(UserRoles::BUSINESS))->toBe('business');
        expect($getPermissionLevel(UserRoles::PRIVATE))->toBe('limited');
    }
}
