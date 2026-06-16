<?php

declare(strict_types=1);

namespace Tests\Domain\User\ValueObjects;

use App\Domain\User\ValueObjects\UserPreferences;
use Tests\UnitTestCase;

class UserPreferencesTest extends UnitTestCase
{
    // ===========================================
    // Constructor Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_default_values(): void
    {
        $prefs = new UserPreferences();

        expect($prefs->getLanguage())->toBe('en');
        expect($prefs->getTimezone())->toBe('UTC');
        expect($prefs->getCurrency())->toBe('USD');
        expect($prefs->isDarkMode())->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_with_custom_values(): void
    {
        $prefs = new UserPreferences(
            language: 'de',
            timezone: 'Europe/Berlin',
            dateFormat: 'd.m.Y',
            currency: 'EUR',
            darkMode: true
        );

        expect($prefs->getLanguage())->toBe('de');
        expect($prefs->getTimezone())->toBe('Europe/Berlin');
        expect($prefs->getCurrency())->toBe('EUR');
        expect($prefs->isDarkMode())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_dashboard_widgets(): void
    {
        $widgets = ['balance', 'transactions', 'chart'];
        $prefs = new UserPreferences(dashboardWidgets: $widgets);

        $array = $prefs->toArray();
        expect($array['dashboardWidgets'])->toBe($widgets);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_accepts_custom_settings(): void
    {
        $customSettings = [
            'compactView'    => true,
            'showBalances'   => false,
            'defaultAccount' => 'acc-123',
        ];
        $prefs = new UserPreferences(customSettings: $customSettings);

        $array = $prefs->toArray();
        expect($array['customSettings'])->toBe($customSettings);
    }

    // ===========================================
    // fromArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_from_array(): void
    {
        $prefs = UserPreferences::fromArray([
            'language'   => 'fr',
            'timezone'   => 'Europe/Paris',
            'dateFormat' => 'd/m/Y',
            'currency'   => 'EUR',
            'darkMode'   => true,
        ]);

        expect($prefs->getLanguage())->toBe('fr');
        expect($prefs->getTimezone())->toBe('Europe/Paris');
        expect($prefs->getCurrency())->toBe('EUR');
        expect($prefs->isDarkMode())->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_defaults_for_missing_array_keys(): void
    {
        $prefs = UserPreferences::fromArray([]);

        expect($prefs->getLanguage())->toBe('en');
        expect($prefs->getTimezone())->toBe('UTC');
        expect($prefs->getCurrency())->toBe('USD');
        expect($prefs->isDarkMode())->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_dashboard_widgets_from_array(): void
    {
        $widgets = ['portfolio', 'news', 'alerts'];

        $prefs = UserPreferences::fromArray([
            'dashboardWidgets' => $widgets,
        ]);

        $array = $prefs->toArray();
        expect($array['dashboardWidgets'])->toBe($widgets);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_custom_settings_from_array(): void
    {
        $customSettings = ['key1' => 'value1', 'key2' => 'value2'];

        $prefs = UserPreferences::fromArray([
            'customSettings' => $customSettings,
        ]);

        $array = $prefs->toArray();
        expect($array['customSettings'])->toBe($customSettings);
    }

    // ===========================================
    // toArray Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_converts_to_array(): void
    {
        $prefs = new UserPreferences(
            language: 'es',
            timezone: 'America/New_York',
            dateFormat: 'm/d/Y',
            currency: 'MXN',
            darkMode: false
        );

        $array = $prefs->toArray();

        expect($array)->toHaveKeys([
            'language',
            'timezone',
            'dateFormat',
            'currency',
            'darkMode',
            'dashboardWidgets',
            'customSettings',
        ]);
        expect($array['language'])->toBe('es');
        expect($array['timezone'])->toBe('America/New_York');
        expect($array['dateFormat'])->toBe('m/d/Y');
        expect($array['currency'])->toBe('MXN');
        expect($array['darkMode'])->toBeFalse();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_roundtrips_through_array(): void
    {
        $original = new UserPreferences(
            language: 'ja',
            timezone: 'Asia/Tokyo',
            dateFormat: 'Y/m/d',
            currency: 'JPY',
            darkMode: true,
            dashboardWidgets: ['widget1', 'widget2'],
            customSettings: ['setting1' => true]
        );

        $array = $original->toArray();
        $restored = UserPreferences::fromArray($array);

        expect($restored->getLanguage())->toBe('ja');
        expect($restored->getTimezone())->toBe('Asia/Tokyo');
        expect($restored->getCurrency())->toBe('JPY');
        expect($restored->isDarkMode())->toBeTrue();
        expect($restored->toArray())->toBe($array);
    }

    // ===========================================
    // Getter Tests
    // ===========================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_language(): void
    {
        $prefs = new UserPreferences(language: 'pt');

        expect($prefs->getLanguage())->toBe('pt');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_timezone(): void
    {
        $prefs = new UserPreferences(timezone: 'Australia/Sydney');

        expect($prefs->getTimezone())->toBe('Australia/Sydney');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_currency(): void
    {
        $prefs = new UserPreferences(currency: 'GBP');

        expect($prefs->getCurrency())->toBe('GBP');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_dark_mode_status(): void
    {
        $darkPrefs = new UserPreferences(darkMode: true);
        $lightPrefs = new UserPreferences(darkMode: false);

        expect($darkPrefs->isDarkMode())->toBeTrue();
        expect($lightPrefs->isDarkMode())->toBeFalse();
    }
}
