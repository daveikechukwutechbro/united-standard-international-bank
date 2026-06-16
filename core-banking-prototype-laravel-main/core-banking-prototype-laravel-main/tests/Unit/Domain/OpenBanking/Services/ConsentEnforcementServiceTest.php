<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\OpenBanking\Services;

use App\Domain\OpenBanking\Services\ConsentEnforcementService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Structural tests for ConsentEnforcementService.
 *
 * These tests verify the service methods exist and have the correct
 * signatures without requiring database access.
 */
class ConsentEnforcementServiceTest extends TestCase
{
    #[Test]
    public function service_class_exists(): void
    {
        $this->assertNotEmpty((new ReflectionClass(ConsentEnforcementService::class))->getName());
    }

    #[Test]
    public function service_is_final(): void
    {
        $reflection = new ReflectionClass(ConsentEnforcementService::class);
        $this->assertTrue($reflection->isFinal());
    }

    #[Test]
    public function service_has_validate_access_method(): void
    {
        $reflection = new ReflectionClass(ConsentEnforcementService::class);
        $method = $reflection->getMethod('validateAccess');

        $this->assertTrue($method->isPublic());
        $this->assertCount(4, $method->getParameters());

        $params = $method->getParameters();
        $this->assertEquals('tppId', $params[0]->getName());
        $this->assertEquals('userId', $params[1]->getName());
        $this->assertEquals('permission', $params[2]->getName());
        $this->assertEquals('accountId', $params[3]->getName());

        // accountId is optional
        $this->assertTrue($params[3]->isDefaultValueAvailable());
        $this->assertNull($params[3]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        /** @var ReflectionNamedType $returnType */
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function service_has_log_access_method(): void
    {
        $reflection = new ReflectionClass(ConsentEnforcementService::class);
        $method = $reflection->getMethod('logAccess');

        $this->assertTrue($method->isPublic());
        $this->assertCount(4, $method->getParameters());

        $params = $method->getParameters();
        $this->assertEquals('consentId', $params[0]->getName());
        $this->assertEquals('tppId', $params[1]->getName());
        $this->assertEquals('endpoint', $params[2]->getName());
        $this->assertEquals('ipAddress', $params[3]->getName());

        // ipAddress is optional
        $this->assertTrue($params[3]->isDefaultValueAvailable());
        $this->assertNull($params[3]->getDefaultValue());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        /** @var ReflectionNamedType $returnType */
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function service_has_check_frequency_limit_method(): void
    {
        $reflection = new ReflectionClass(ConsentEnforcementService::class);
        $method = $reflection->getMethod('checkFrequencyLimit');

        $this->assertTrue($method->isPublic());
        $this->assertCount(1, $method->getParameters());

        $params = $method->getParameters();
        $this->assertEquals('consent', $params[0]->getName());

        $paramType = $params[0]->getType();
        $this->assertNotNull($paramType);
        $this->assertInstanceOf(ReflectionNamedType::class, $paramType);
        /** @var ReflectionNamedType $paramType */
        $this->assertEquals('App\Domain\OpenBanking\Models\Consent', $paramType->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        /** @var ReflectionNamedType $returnType */
        $this->assertEquals('bool', $returnType->getName());
    }
}
