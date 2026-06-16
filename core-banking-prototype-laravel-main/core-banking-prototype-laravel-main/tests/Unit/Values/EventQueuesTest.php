<?php

declare(strict_types=1);

namespace Tests\Unit\Values;

use App\Values\EventQueues;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class EventQueuesTest extends UnitTestCase
{
    #[Test]
    public function test_enum_has_correct_values()
    {
        $this->assertEquals('events', EventQueues::EVENTS->value);
        $this->assertEquals('ledger', EventQueues::LEDGER->value);
        $this->assertEquals('transactions', EventQueues::TRANSACTIONS->value);
        $this->assertEquals('transfers', EventQueues::TRANSFERS->value);
        $this->assertEquals('liquidity_pools', EventQueues::LIQUIDITY_POOLS->value);
    }

    #[Test]
    public function test_default_returns_events()
    {
        $default = EventQueues::default();

        $this->assertEquals(EventQueues::EVENTS, $default);
        $this->assertEquals('events', $default->value);
    }

    #[Test]
    public function test_all_enum_cases_exist()
    {
        $cases = EventQueues::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(EventQueues::EVENTS, $cases);
        $this->assertContains(EventQueues::LEDGER, $cases);
        $this->assertContains(EventQueues::TRANSACTIONS, $cases);
        $this->assertContains(EventQueues::TRANSFERS, $cases);
        $this->assertContains(EventQueues::LIQUIDITY_POOLS, $cases);
    }

    #[Test]
    public function test_enum_from_value()
    {
        $events = EventQueues::from('events');
        $ledger = EventQueues::from('ledger');
        $transactions = EventQueues::from('transactions');
        $transfers = EventQueues::from('transfers');
        $liquidityPools = EventQueues::from('liquidity_pools');

        $this->assertEquals(EventQueues::EVENTS, $events);
        $this->assertEquals(EventQueues::LEDGER, $ledger);
        $this->assertEquals(EventQueues::TRANSACTIONS, $transactions);
        $this->assertEquals(EventQueues::TRANSFERS, $transfers);
        $this->assertEquals(EventQueues::LIQUIDITY_POOLS, $liquidityPools);
    }

    #[Test]
    public function test_enum_try_from_valid()
    {
        $events = EventQueues::tryFrom('events');
        $ledger = EventQueues::tryFrom('ledger');
        $transactions = EventQueues::tryFrom('transactions');
        $transfers = EventQueues::tryFrom('transfers');
        $liquidityPools = EventQueues::tryFrom('liquidity_pools');

        $this->assertEquals(EventQueues::EVENTS, $events);
        $this->assertEquals(EventQueues::LEDGER, $ledger);
        $this->assertEquals(EventQueues::TRANSACTIONS, $transactions);
        $this->assertEquals(EventQueues::TRANSFERS, $transfers);
        $this->assertEquals(EventQueues::LIQUIDITY_POOLS, $liquidityPools);
    }

    #[Test]
    public function test_enum_try_from_invalid()
    {
        $invalid = EventQueues::tryFrom('invalid');

        $this->assertNull($invalid);
    }

    #[Test]
    public function test_queue_values_are_lowercase()
    {
        $cases = EventQueues::cases();

        foreach ($cases as $case) {
            $this->assertEquals(strtolower($case->value), $case->value);
        }
    }
}
