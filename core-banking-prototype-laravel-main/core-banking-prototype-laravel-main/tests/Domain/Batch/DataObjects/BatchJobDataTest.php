<?php

declare(strict_types=1);

namespace Tests\Domain\Batch\DataObjects;

use App\Domain\Batch\DataObjects\BatchJob;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BatchJob data object.
 */
class BatchJobDataTest extends TestCase
{
    public function test_create_generates_uuid(): void
    {
        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Test Batch',
            type: 'transfer',
            items: []
        );

        // UUID format: 8-4-4-4-12
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/',
            $batchJob->uuid
        );
    }

    public function test_create_sets_user_uuid(): void
    {
        $batchJob = BatchJob::create(
            userUuid: 'user-456',
            name: 'Test Batch',
            type: 'transfer',
            items: []
        );

        $this->assertEquals('user-456', $batchJob->userUuid);
    }

    public function test_create_sets_name(): void
    {
        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Payroll Processing',
            type: 'payment',
            items: []
        );

        $this->assertEquals('Payroll Processing', $batchJob->name);
    }

    public function test_create_sets_type(): void
    {
        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Test',
            type: 'conversion',
            items: []
        );

        $this->assertEquals('conversion', $batchJob->type);
    }

    public function test_create_sets_items(): void
    {
        $items = [
            ['from_account' => 'acc-1', 'to_account' => 'acc-2', 'amount' => 100],
            ['from_account' => 'acc-3', 'to_account' => 'acc-4', 'amount' => 200],
        ];

        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Test',
            type: 'transfer',
            items: $items
        );

        $this->assertEquals($items, $batchJob->items);
        $this->assertCount(2, $batchJob->items);
    }

    public function test_create_with_scheduled_at(): void
    {
        $scheduledAt = '2026-01-26 10:00:00';

        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Scheduled Batch',
            type: 'transfer',
            items: [],
            scheduledAt: $scheduledAt
        );

        $this->assertEquals($scheduledAt, $batchJob->scheduledAt);
    }

    public function test_create_without_scheduled_at_is_null(): void
    {
        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Immediate Batch',
            type: 'transfer',
            items: []
        );

        $this->assertNull($batchJob->scheduledAt);
    }

    public function test_create_with_metadata(): void
    {
        $metadata = [
            'source'     => 'api',
            'request_id' => 'req-123',
        ];

        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Test',
            type: 'transfer',
            items: [],
            metadata: $metadata
        );

        $this->assertEquals($metadata, $batchJob->metadata);
    }

    public function test_create_without_metadata_is_empty_array(): void
    {
        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Test',
            type: 'transfer',
            items: []
        );

        $this->assertEquals([], $batchJob->metadata);
    }

    public function test_constructor_accepts_all_parameters(): void
    {
        $batchJob = new BatchJob(
            uuid: 'custom-uuid-123',
            userUuid: 'user-789',
            name: 'Direct Construction',
            type: 'payment',
            items: [['data' => 'test']],
            scheduledAt: '2026-02-01 00:00:00',
            metadata: ['key' => 'value']
        );

        $this->assertEquals('custom-uuid-123', $batchJob->uuid);
        $this->assertEquals('user-789', $batchJob->userUuid);
        $this->assertEquals('Direct Construction', $batchJob->name);
        $this->assertEquals('payment', $batchJob->type);
        $this->assertEquals([['data' => 'test']], $batchJob->items);
        $this->assertEquals('2026-02-01 00:00:00', $batchJob->scheduledAt);
        $this->assertEquals(['key' => 'value'], $batchJob->metadata);
    }

    public function test_batch_type_transfer(): void
    {
        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Transfer Batch',
            type: 'transfer',
            items: []
        );

        $this->assertEquals('transfer', $batchJob->type);
    }

    public function test_batch_type_payment(): void
    {
        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Payment Batch',
            type: 'payment',
            items: []
        );

        $this->assertEquals('payment', $batchJob->type);
    }

    public function test_batch_type_conversion(): void
    {
        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Conversion Batch',
            type: 'conversion',
            items: []
        );

        $this->assertEquals('conversion', $batchJob->type);
    }

    public function test_create_generates_unique_uuids(): void
    {
        $batchJob1 = BatchJob::create(
            userUuid: 'user-123',
            name: 'Batch 1',
            type: 'transfer',
            items: []
        );

        $batchJob2 = BatchJob::create(
            userUuid: 'user-123',
            name: 'Batch 2',
            type: 'transfer',
            items: []
        );

        $this->assertNotEquals($batchJob1->uuid, $batchJob2->uuid);
    }

    public function test_items_preserve_complex_data(): void
    {
        $items = [
            [
                'type'         => 'transfer',
                'from_account' => 'acc-123',
                'to_account'   => 'acc-456',
                'amount'       => 1000.50,
                'currency'     => 'USD',
                'reference'    => 'REF-001',
                'metadata'     => ['note' => 'Monthly payment'],
            ],
        ];

        $batchJob = BatchJob::create(
            userUuid: 'user-123',
            name: 'Complex Batch',
            type: 'transfer',
            items: $items
        );

        $this->assertEquals(1000.50, $batchJob->items[0]['amount']);
        $this->assertEquals('USD', $batchJob->items[0]['currency']);
        $this->assertEquals(['note' => 'Monthly payment'], $batchJob->items[0]['metadata']);
    }
}
