<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\OpenBanking\Services;

use App\Domain\OpenBanking\Services\AispService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Structural tests for AispService.
 *
 * Verifies that the service methods exist and carry the correct signatures
 * without requiring a database or HTTP context.
 */
class AispServiceTest extends TestCase
{
    #[Test]
    public function class_exists_with_expected_methods(): void
    {
        $reflection = new ReflectionClass(AispService::class);

        $this->assertNotEmpty($reflection->getName());
        $this->assertTrue($reflection->hasMethod('getAccounts'));
        $this->assertTrue($reflection->hasMethod('getAccountDetail'));
        $this->assertTrue($reflection->hasMethod('getBalances'));
        $this->assertTrue($reflection->hasMethod('getTransactions'));
    }

    #[Test]
    public function class_is_final(): void
    {
        $reflection = new ReflectionClass(AispService::class);

        $this->assertTrue($reflection->isFinal());
    }

    #[Test]
    public function get_accounts_has_correct_parameters(): void
    {
        $reflection = new ReflectionClass(AispService::class);
        $method = $reflection->getMethod('getAccounts');

        $this->assertTrue($method->isPublic());
        $this->assertCount(3, $method->getParameters());

        $params = $method->getParameters();
        $this->assertEquals('consentId', $params[0]->getName());
        $this->assertEquals('tppId', $params[1]->getName());
        $this->assertEquals('userId', $params[2]->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        /** @var ReflectionNamedType $returnType */
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function get_balances_has_correct_parameters(): void
    {
        $reflection = new ReflectionClass(AispService::class);
        $method = $reflection->getMethod('getBalances');

        $this->assertTrue($method->isPublic());
        $this->assertCount(4, $method->getParameters());

        $params = $method->getParameters();
        $this->assertEquals('consentId', $params[0]->getName());
        $this->assertEquals('tppId', $params[1]->getName());
        $this->assertEquals('userId', $params[2]->getName());
        $this->assertEquals('accountId', $params[3]->getName());

        // Ensure accountId is a required string parameter
        $accountIdType = $params[3]->getType();
        $this->assertNotNull($accountIdType);
        $this->assertInstanceOf(ReflectionNamedType::class, $accountIdType);
        /** @var ReflectionNamedType $accountIdType */
        $this->assertEquals('string', $accountIdType->getName());
    }

    #[Test]
    public function get_transactions_has_optional_from_date_and_to_date(): void
    {
        $reflection = new ReflectionClass(AispService::class);
        $method = $reflection->getMethod('getTransactions');

        $this->assertTrue($method->isPublic());
        $this->assertCount(6, $method->getParameters());

        $params = $method->getParameters();
        $this->assertEquals('consentId', $params[0]->getName());
        $this->assertEquals('tppId', $params[1]->getName());
        $this->assertEquals('userId', $params[2]->getName());
        $this->assertEquals('accountId', $params[3]->getName());
        $this->assertEquals('fromDate', $params[4]->getName());
        $this->assertEquals('toDate', $params[5]->getName());

        // fromDate is optional (nullable with default null)
        $this->assertTrue($params[4]->isDefaultValueAvailable());
        $this->assertNull($params[4]->getDefaultValue());

        // toDate is optional (nullable with default null)
        $this->assertTrue($params[5]->isDefaultValueAvailable());
        $this->assertNull($params[5]->getDefaultValue());

        // Both are nullable string types
        $fromType = $params[4]->getType();
        $this->assertNotNull($fromType);
        $this->assertInstanceOf(ReflectionNamedType::class, $fromType);
        /** @var ReflectionNamedType $fromType */
        $this->assertEquals('string', $fromType->getName());
        $this->assertTrue($fromType->allowsNull());

        $toType = $params[5]->getType();
        $this->assertNotNull($toType);
        $this->assertInstanceOf(ReflectionNamedType::class, $toType);
        /** @var ReflectionNamedType $toType */
        $this->assertEquals('string', $toType->getName());
        $this->assertTrue($toType->allowsNull());
    }

    #[Test]
    public function get_account_detail_has_correct_parameters(): void
    {
        $reflection = new ReflectionClass(AispService::class);
        $method = $reflection->getMethod('getAccountDetail');

        $this->assertTrue($method->isPublic());
        $this->assertCount(4, $method->getParameters());

        $params = $method->getParameters();
        $this->assertEquals('consentId', $params[0]->getName());
        $this->assertEquals('tppId', $params[1]->getName());
        $this->assertEquals('userId', $params[2]->getName());
        $this->assertEquals('accountId', $params[3]->getName());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        /** @var ReflectionNamedType $returnType */
        $this->assertEquals('array', $returnType->getName());
        $this->assertTrue($returnType->allowsNull());
    }
}
