<?php

declare(strict_types=1);

namespace Tests\Domain\User\Exceptions;

use App\Domain\User\Exceptions\UserProfileException;
use DomainException;
use Tests\UnitTestCase;

class UserProfileExceptionTest extends UnitTestCase
{
    // ===========================================
    // Inheritance Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_extends_domain_exception(): void
    {
        $exception = UserProfileException::notFound('user-123');

        expect($exception)->toBeInstanceOf(DomainException::class);
    }

    // ===========================================
    // noValidFieldsToUpdate Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_no_valid_fields_exception(): void
    {
        $exception = UserProfileException::noValidFieldsToUpdate();

        expect($exception)->toBeInstanceOf(UserProfileException::class);
        expect($exception->getMessage())->toBe('No valid fields provided for update.');
    }

    // ===========================================
    // alreadyVerified Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_already_verified_exception(): void
    {
        $userId = 'user-abc-123';
        $exception = UserProfileException::alreadyVerified($userId);

        expect($exception)->toBeInstanceOf(UserProfileException::class);
        expect($exception->getMessage())->toBe("User profile {$userId} is already verified.");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_user_id_in_already_verified_message(): void
    {
        $exception = UserProfileException::alreadyVerified('test-user-id');

        expect($exception->getMessage())->toContain('test-user-id');
    }

    // ===========================================
    // alreadySuspended Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_already_suspended_exception(): void
    {
        $userId = 'user-xyz-789';
        $exception = UserProfileException::alreadySuspended($userId);

        expect($exception)->toBeInstanceOf(UserProfileException::class);
        expect($exception->getMessage())->toBe("User profile {$userId} is already suspended.");
    }

    // ===========================================
    // alreadyDeleted Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_already_deleted_exception(): void
    {
        $userId = 'deleted-user-456';
        $exception = UserProfileException::alreadyDeleted($userId);

        expect($exception)->toBeInstanceOf(UserProfileException::class);
        expect($exception->getMessage())->toBe("User profile {$userId} is already deleted.");
    }

    // ===========================================
    // notFound Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_not_found_exception(): void
    {
        $userId = 'missing-user-000';
        $exception = UserProfileException::notFound($userId);

        expect($exception)->toBeInstanceOf(UserProfileException::class);
        expect($exception->getMessage())->toBe("User profile {$userId} not found.");
    }

    // ===========================================
    // invalidStatus Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_invalid_status_exception(): void
    {
        $status = 'unknown_status';
        $exception = UserProfileException::invalidStatus($status);

        expect($exception)->toBeInstanceOf(UserProfileException::class);
        expect($exception->getMessage())->toBe("Invalid user profile status: {$status}");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_status_in_invalid_status_message(): void
    {
        $exception = UserProfileException::invalidStatus('bogus');

        expect($exception->getMessage())->toContain('bogus');
    }

    // ===========================================
    // cannotPerformAction Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_cannot_perform_action_exception(): void
    {
        $userId = 'user-123';
        $action = 'delete';
        $reason = 'user has pending transactions';

        $exception = UserProfileException::cannotPerformAction($userId, $action, $reason);

        expect($exception)->toBeInstanceOf(UserProfileException::class);
        expect($exception->getMessage())->toBe("Cannot perform {$action} on user {$userId}: {$reason}");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_all_parameters_in_cannot_perform_action_message(): void
    {
        $exception = UserProfileException::cannotPerformAction('my-user', 'suspend', 'insufficient permissions');

        $message = $exception->getMessage();
        expect($message)->toContain('my-user');
        expect($message)->toContain('suspend');
        expect($message)->toContain('insufficient permissions');
    }

    // ===========================================
    // Throwable Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_thrown_and_caught(): void
    {
        $caught = false;

        try {
            throw UserProfileException::notFound('test-user');
        } catch (UserProfileException $e) {
            $caught = true;
            expect($e->getMessage())->toContain('test-user');
        }

        expect($caught)->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_caught_as_domain_exception(): void
    {
        $caught = false;

        try {
            throw UserProfileException::invalidStatus('bad');
        } catch (DomainException $e) {
            $caught = true;
        }

        expect($caught)->toBeTrue();
    }
}
