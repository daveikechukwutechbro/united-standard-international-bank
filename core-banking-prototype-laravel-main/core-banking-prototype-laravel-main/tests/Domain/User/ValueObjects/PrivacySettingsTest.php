<?php

declare(strict_types=1);

namespace Tests\Domain\User\ValueObjects;

use App\Domain\User\ValueObjects\PrivacySettings;
use Tests\UnitTestCase;

class PrivacySettingsTest extends UnitTestCase
{
    // ===========================================
    // Constructor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_default_values(): void
    {
        $settings = new PrivacySettings();

        expect($settings->isProfileVisible())->toBeTrue();
        expect($settings->canShareData())->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_custom_values(): void
    {
        $settings = new PrivacySettings(
            profileVisibility: false,
            showEmail: true,
            showPhone: true,
            showActivity: false,
            allowDataSharing: true,
            allowAnalytics: false
        );

        expect($settings->isProfileVisible())->toBeFalse();
        expect($settings->canShareData())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sets_default_data_retention(): void
    {
        $settings = new PrivacySettings();
        $array = $settings->toArray();

        expect($array['dataRetention'])->toHaveKeys(['transactionHistory', 'activityLogs', 'communicationLogs']);
        expect($array['dataRetention']['transactionHistory'])->toBe(365);
        expect($array['dataRetention']['activityLogs'])->toBe(90);
        expect($array['dataRetention']['communicationLogs'])->toBe(30);
    }

    // ===========================================
    // fromArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_array(): void
    {
        $settings = PrivacySettings::fromArray([
            'profileVisibility' => false,
            'showEmail'         => true,
            'showPhone'         => true,
            'allowDataSharing'  => true,
        ]);

        expect($settings->isProfileVisible())->toBeFalse();
        expect($settings->canShareData())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_defaults_for_missing_array_keys(): void
    {
        $settings = PrivacySettings::fromArray([]);

        expect($settings->isProfileVisible())->toBeTrue();
        expect($settings->canShareData())->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_blocked_users_from_array(): void
    {
        $blockedUsers = ['user-1', 'user-2', 'user-3'];

        $settings = PrivacySettings::fromArray([
            'blockedUsers' => $blockedUsers,
        ]);

        $array = $settings->toArray();
        expect($array['blockedUsers'])->toBe($blockedUsers);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_custom_data_retention_from_array(): void
    {
        $customRetention = [
            'transactionHistory' => 730,
            'activityLogs'       => 180,
            'customType'         => 60,
        ];

        $settings = PrivacySettings::fromArray([
            'dataRetention' => $customRetention,
        ]);

        $array = $settings->toArray();
        expect($array['dataRetention'])->toBe($customRetention);
    }

    // ===========================================
    // toArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_array(): void
    {
        $settings = new PrivacySettings(
            profileVisibility: true,
            showEmail: true,
            showPhone: false,
            showActivity: true,
            allowDataSharing: false,
            allowAnalytics: true
        );

        $array = $settings->toArray();

        expect($array)->toHaveKeys([
            'profileVisibility',
            'showEmail',
            'showPhone',
            'showActivity',
            'allowDataSharing',
            'allowAnalytics',
            'blockedUsers',
            'dataRetention',
        ]);
        expect($array['profileVisibility'])->toBeTrue();
        expect($array['showEmail'])->toBeTrue();
        expect($array['showPhone'])->toBeFalse();
    }

    // ===========================================
    // isUserBlocked Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_checks_if_user_is_blocked(): void
    {
        $settings = PrivacySettings::fromArray([
            'blockedUsers' => ['blocked-user-1', 'blocked-user-2'],
        ]);

        expect($settings->isUserBlocked('blocked-user-1'))->toBeTrue();
        expect($settings->isUserBlocked('blocked-user-2'))->toBeTrue();
        expect($settings->isUserBlocked('not-blocked-user'))->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_false_for_empty_blocked_list(): void
    {
        $settings = new PrivacySettings();

        expect($settings->isUserBlocked('any-user'))->toBeFalse();
    }

    // ===========================================
    // getDataRetentionDays Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_data_retention_days(): void
    {
        $settings = new PrivacySettings();

        expect($settings->getDataRetentionDays('transactionHistory'))->toBe(365);
        expect($settings->getDataRetentionDays('activityLogs'))->toBe(90);
        expect($settings->getDataRetentionDays('communicationLogs'))->toBe(30);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_default_retention_for_unknown_type(): void
    {
        $settings = new PrivacySettings();

        expect($settings->getDataRetentionDays('unknownType'))->toBe(90);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_custom_retention_days(): void
    {
        $settings = PrivacySettings::fromArray([
            'dataRetention' => [
                'transactionHistory' => 730,
                'customType'         => 14,
            ],
        ]);

        expect($settings->getDataRetentionDays('transactionHistory'))->toBe(730);
        expect($settings->getDataRetentionDays('customType'))->toBe(14);
    }
}
