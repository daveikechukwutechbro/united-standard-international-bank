<?php

declare(strict_types=1);

namespace Tests\Domain\Wallet\ValueObjects;

use App\Domain\Wallet\ValueObjects\MultiSigConfiguration;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MultiSigConfigurationTest extends TestCase
{
    #[Test]
    public function it_creates_configuration_from_explicit_values(): void
    {
        $config = MultiSigConfiguration::create(
            requiredSignatures: 2,
            totalSigners: 3,
            chain: 'ethereum',
            name: 'Test Wallet',
        );

        $this->assertEquals(2, $config->requiredSignatures);
        $this->assertEquals(3, $config->totalSigners);
        $this->assertEquals('ethereum', $config->chain);
        $this->assertEquals('Test Wallet', $config->name);
    }

    #[Test]
    public function it_creates_configuration_from_scheme_string(): void
    {
        $config = MultiSigConfiguration::fromScheme(
            scheme: '2-of-3',
            chain: 'bitcoin',
            name: 'Bitcoin Treasury',
        );

        $this->assertEquals(2, $config->requiredSignatures);
        $this->assertEquals(3, $config->totalSigners);
        $this->assertEquals('bitcoin', $config->chain);
    }

    #[Test]
    public function it_creates_configuration_from_array(): void
    {
        $config = MultiSigConfiguration::fromArray([
            'required_signatures' => 3,
            'total_signers'       => 5,
            'chain'               => 'polygon',
            'name'                => 'Corporate Wallet',
        ]);

        $this->assertEquals(3, $config->requiredSignatures);
        $this->assertEquals(5, $config->totalSigners);
        $this->assertEquals('polygon', $config->chain);
    }

    #[Test]
    public function it_returns_scheme_description(): void
    {
        $config = MultiSigConfiguration::create(2, 3, 'ethereum', 'Test');

        $this->assertEquals('2-of-3', $config->getScheme());
    }

    #[Test]
    public function it_identifies_standard_schemes(): void
    {
        $standard = MultiSigConfiguration::create(2, 3, 'ethereum', 'Test');
        $this->assertTrue($standard->isStandardScheme());

        $nonStandard = MultiSigConfiguration::create(4, 7, 'ethereum', 'Test');
        $this->assertFalse($nonStandard->isStandardScheme());
    }

    #[Test]
    public function it_calculates_quorum_percentage(): void
    {
        $twoOfThree = MultiSigConfiguration::create(2, 3, 'ethereum', 'Test');
        $this->assertEquals(66.67, $twoOfThree->getQuorumPercentage());

        $threeOfFive = MultiSigConfiguration::create(3, 5, 'ethereum', 'Test');
        $this->assertEquals(60.0, $threeOfFive->getQuorumPercentage());

        $twoOfTwo = MultiSigConfiguration::create(2, 2, 'ethereum', 'Test');
        $this->assertEquals(100.0, $twoOfTwo->getQuorumPercentage());
    }

    #[Test]
    public function it_identifies_majority_requirement(): void
    {
        $majority = MultiSigConfiguration::create(2, 3, 'ethereum', 'Test');
        $this->assertTrue($majority->isMajorityRequired());

        $notMajority = MultiSigConfiguration::create(2, 5, 'ethereum', 'Test');
        $this->assertFalse($notMajority->isMajorityRequired());
    }

    #[Test]
    public function it_identifies_unanimous_requirement(): void
    {
        $unanimous = MultiSigConfiguration::create(3, 3, 'ethereum', 'Test');
        $this->assertTrue($unanimous->isUnanimousRequired());

        $notUnanimous = MultiSigConfiguration::create(2, 3, 'ethereum', 'Test');
        $this->assertFalse($notUnanimous->isUnanimousRequired());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $config = MultiSigConfiguration::create(2, 3, 'ethereum', 'Test');
        $array = $config->toArray();

        $this->assertArrayHasKey('required_signatures', $array);
        $this->assertArrayHasKey('total_signers', $array);
        $this->assertArrayHasKey('chain', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('scheme', $array);
        $this->assertArrayHasKey('quorum_percentage', $array);
        $this->assertArrayHasKey('is_standard_scheme', $array);

        $this->assertEquals(2, $array['required_signatures']);
        $this->assertEquals(3, $array['total_signers']);
        $this->assertEquals('2-of-3', $array['scheme']);
    }

    #[Test]
    public function it_throws_exception_for_invalid_scheme_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid scheme format: invalid. Expected format: 'M-of-N'");

        MultiSigConfiguration::fromScheme('invalid', 'ethereum', 'Test');
    }

    #[Test]
    #[DataProvider('invalidConfigurationsProvider')]
    public function it_validates_configuration(
        int $required,
        int $total,
        string $chain,
        string $name,
        string $expectedMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        MultiSigConfiguration::create($required, $total, $chain, $name);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function invalidConfigurationsProvider(): array
    {
        return [
            'total below minimum' => [
                1, 1, 'ethereum', 'Test',
                'Total signers must be at least',
            ],
            'required exceeds total' => [
                4, 3, 'ethereum', 'Test',
                'Required signatures cannot exceed total signers',
            ],
            'required is zero' => [
                0, 3, 'ethereum', 'Test',
                'Required signatures must be at least 1',
            ],
            'empty chain' => [
                2, 3, '', 'Test',
                'Chain must be specified',
            ],
            'empty name' => [
                2, 3, 'ethereum', '',
                'Name must be specified',
            ],
            'name too long' => [
                2, 3, 'ethereum', str_repeat('a', 101),
                'Name cannot exceed 100 characters',
            ],
        ];
    }
}
