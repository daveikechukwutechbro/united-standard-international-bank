<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Providers\HelperServiceProvider;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class HelperServiceProviderTest extends UnitTestCase
{
    #[Test]
    public function test_provider_is_service_provider()
    {
        $provider = new HelperServiceProvider(app());

        $this->assertInstanceOf(ServiceProvider::class, $provider);
    }

    #[Test]
    public function test_provider_registers_services()
    {
        $provider = new HelperServiceProvider(app());

        // Test that the provider can be instantiated and doesn't throw errors
        $this->assertNotNull($provider);

        // Call register method
        $provider->register();

        // If we get here without exceptions, the test passes
        $this->assertTrue(true);
    }

    #[Test]
    public function test_provider_boots_successfully()
    {
        $provider = new HelperServiceProvider(app());

        // Call boot method
        $provider->boot();

        // If we get here without exceptions, the test passes
        $this->assertTrue(true);
    }
}
