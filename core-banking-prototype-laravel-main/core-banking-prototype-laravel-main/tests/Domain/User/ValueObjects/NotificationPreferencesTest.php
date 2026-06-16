<?php

declare(strict_types=1);

namespace Tests\Domain\User\ValueObjects;

use App\Domain\User\ValueObjects\NotificationPreferences;
use DateTimeImmutable;
use Tests\UnitTestCase;

class NotificationPreferencesTest extends UnitTestCase
{
    // ===========================================
    // Constructor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_default_values(): void
    {
        $prefs = new NotificationPreferences();

        expect($prefs->shouldSendEmail())->toBeTrue();
        expect($prefs->shouldSendSms())->toBeFalse();
        expect($prefs->shouldSendPush())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_custom_values(): void
    {
        $prefs = new NotificationPreferences(
            emailNotifications: false,
            smsNotifications: true,
            pushNotifications: false
        );

        expect($prefs->shouldSendEmail())->toBeFalse();
        expect($prefs->shouldSendSms())->toBeTrue();
        expect($prefs->shouldSendPush())->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sets_default_notification_types(): void
    {
        $prefs = new NotificationPreferences();
        $array = $prefs->toArray();

        expect($array['notificationTypes'])->toHaveKeys(['transactions', 'security', 'marketing', 'updates', 'reminders']);
        expect($array['notificationTypes']['transactions'])->toBeTrue();
        expect($array['notificationTypes']['security'])->toBeTrue();
        expect($array['notificationTypes']['marketing'])->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sets_default_email_frequency(): void
    {
        $prefs = new NotificationPreferences();
        $array = $prefs->toArray();

        expect($array['emailFrequency'])->toHaveKeys(['immediate', 'daily', 'weekly']);
        expect($array['emailFrequency']['immediate'])->toContain('security', 'transactions');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sets_default_quiet_hours(): void
    {
        $prefs = new NotificationPreferences();
        $array = $prefs->toArray();

        expect($array['quietHours']['enabled'])->toBeFalse();
        expect($array['quietHours']['start'])->toBe('22:00');
        expect($array['quietHours']['end'])->toBe('08:00');
    }

    // ===========================================
    // fromArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_array(): void
    {
        $prefs = NotificationPreferences::fromArray([
            'emailNotifications' => false,
            'smsNotifications'   => true,
            'pushNotifications'  => false,
        ]);

        expect($prefs->shouldSendEmail())->toBeFalse();
        expect($prefs->shouldSendSms())->toBeTrue();
        expect($prefs->shouldSendPush())->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_defaults_for_missing_array_keys(): void
    {
        $prefs = NotificationPreferences::fromArray([]);

        expect($prefs->shouldSendEmail())->toBeTrue();
        expect($prefs->shouldSendSms())->toBeFalse();
        expect($prefs->shouldSendPush())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_custom_notification_types_from_array(): void
    {
        $customTypes = [
            'transactions' => false,
            'security'     => true,
            'custom_type'  => true,
        ];

        $prefs = NotificationPreferences::fromArray([
            'notificationTypes' => $customTypes,
        ]);

        $array = $prefs->toArray();
        expect($array['notificationTypes'])->toBe($customTypes);
    }

    // ===========================================
    // toArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_array(): void
    {
        $prefs = new NotificationPreferences(
            emailNotifications: true,
            smsNotifications: true,
            pushNotifications: false
        );

        $array = $prefs->toArray();

        expect($array)->toHaveKeys([
            'emailNotifications',
            'smsNotifications',
            'pushNotifications',
            'notificationTypes',
            'emailFrequency',
            'quietHours',
        ]);
        expect($array['emailNotifications'])->toBeTrue();
        expect($array['smsNotifications'])->toBeTrue();
        expect($array['pushNotifications'])->toBeFalse();
    }

    // ===========================================
    // isTypeEnabled Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_checks_if_notification_type_is_enabled(): void
    {
        $prefs = new NotificationPreferences();

        expect($prefs->isTypeEnabled('transactions'))->toBeTrue();
        expect($prefs->isTypeEnabled('security'))->toBeTrue();
        expect($prefs->isTypeEnabled('marketing'))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_false_for_unknown_notification_type(): void
    {
        $prefs = new NotificationPreferences();

        expect($prefs->isTypeEnabled('unknown_type'))->toBeFalse();
    }

    // ===========================================
    // isInQuietHours Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_false_when_quiet_hours_disabled(): void
    {
        $prefs = new NotificationPreferences();
        $time = new DateTimeImmutable('23:00');

        expect($prefs->isInQuietHours($time))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_detects_time_within_same_day_quiet_hours(): void
    {
        $prefs = NotificationPreferences::fromArray([
            'quietHours' => [
                'enabled' => true,
                'start'   => '09:00',
                'end'     => '17:00',
            ],
        ]);

        $duringQuiet = new DateTimeImmutable('12:00');
        $beforeQuiet = new DateTimeImmutable('08:00');
        $afterQuiet = new DateTimeImmutable('18:00');

        expect($prefs->isInQuietHours($duringQuiet))->toBeTrue();
        expect($prefs->isInQuietHours($beforeQuiet))->toBeFalse();
        expect($prefs->isInQuietHours($afterQuiet))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_detects_time_within_overnight_quiet_hours(): void
    {
        $prefs = NotificationPreferences::fromArray([
            'quietHours' => [
                'enabled' => true,
                'start'   => '22:00',
                'end'     => '08:00',
            ],
        ]);

        $lateNight = new DateTimeImmutable('23:30');
        $earlyMorning = new DateTimeImmutable('06:00');
        $daytime = new DateTimeImmutable('12:00');

        expect($prefs->isInQuietHours($lateNight))->toBeTrue();
        expect($prefs->isInQuietHours($earlyMorning))->toBeTrue();
        expect($prefs->isInQuietHours($daytime))->toBeFalse();
    }
}
