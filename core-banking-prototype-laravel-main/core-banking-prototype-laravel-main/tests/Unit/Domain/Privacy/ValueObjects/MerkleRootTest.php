<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Privacy\ValueObjects;

use App\Domain\Privacy\ValueObjects\MerkleRoot;
use DateTimeImmutable;
use Tests\TestCase;

class MerkleRootTest extends TestCase
{
    public function test_can_create_merkle_root(): void
    {
        $root = new MerkleRoot(
            root: '0x' . str_repeat('a', 64),
            network: 'polygon',
            leafCount: 1000,
            treeDepth: 32,
            blockNumber: 55000000,
            syncedAt: new DateTimeImmutable('2026-02-03T12:00:00Z'),
        );

        $this->assertEquals('0x' . str_repeat('a', 64), $root->root);
        $this->assertEquals('polygon', $root->network);
        $this->assertEquals(1000, $root->leafCount);
        $this->assertEquals(32, $root->treeDepth);
        $this->assertEquals(55000000, $root->blockNumber);
    }

    public function test_is_stale_returns_true_when_older_than_max_age(): void
    {
        $root = new MerkleRoot(
            root: '0x' . str_repeat('a', 64),
            network: 'polygon',
            leafCount: 1000,
            treeDepth: 32,
            blockNumber: 55000000,
            syncedAt: new DateTimeImmutable('-2 minutes'),
        );

        $this->assertTrue($root->isStale(60)); // 60 seconds max age
    }

    public function test_is_stale_returns_false_when_recent(): void
    {
        $root = new MerkleRoot(
            root: '0x' . str_repeat('a', 64),
            network: 'polygon',
            leafCount: 1000,
            treeDepth: 32,
            blockNumber: 55000000,
            syncedAt: new DateTimeImmutable('-10 seconds'),
        );

        $this->assertFalse($root->isStale(60));
    }

    public function test_get_age_seconds(): void
    {
        $root = new MerkleRoot(
            root: '0x' . str_repeat('a', 64),
            network: 'polygon',
            leafCount: 1000,
            treeDepth: 32,
            blockNumber: 55000000,
            syncedAt: new DateTimeImmutable('-30 seconds'),
        );

        $age = $root->getAgeSeconds();
        $this->assertGreaterThanOrEqual(29, $age);
        $this->assertLessThanOrEqual(32, $age);
    }

    public function test_to_array(): void
    {
        $syncedAt = new DateTimeImmutable('2026-02-03T12:00:00Z');
        $root = new MerkleRoot(
            root: '0x' . str_repeat('a', 64),
            network: 'polygon',
            leafCount: 1000,
            treeDepth: 32,
            blockNumber: 55000000,
            syncedAt: $syncedAt,
        );

        $array = $root->toArray();

        $this->assertEquals('0x' . str_repeat('a', 64), $array['root']);
        $this->assertEquals('polygon', $array['network']);
        $this->assertEquals(1000, $array['leaf_count']);
        $this->assertEquals(32, $array['tree_depth']);
        $this->assertEquals(55000000, $array['block_number']);
        $this->assertEquals($syncedAt->format('c'), $array['synced_at']);
    }

    public function test_json_serializable(): void
    {
        $root = new MerkleRoot(
            root: '0x' . str_repeat('a', 64),
            network: 'polygon',
            leafCount: 1000,
            treeDepth: 32,
            blockNumber: 55000000,
            syncedAt: new DateTimeImmutable('2026-02-03T12:00:00Z'),
        );

        $json = json_encode($root);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('polygon', $decoded['network']);
    }

    public function test_from_array(): void
    {
        $data = [
            'root'         => '0x' . str_repeat('b', 64),
            'network'      => 'base',
            'leaf_count'   => 2000,
            'tree_depth'   => 32,
            'block_number' => 12000000,
            'synced_at'    => '2026-02-03T12:00:00Z',
        ];

        $root = MerkleRoot::fromArray($data);

        $this->assertEquals($data['root'], $root->root);
        $this->assertEquals('base', $root->network);
        $this->assertEquals(2000, $root->leafCount);
        $this->assertEquals(12000000, $root->blockNumber);
    }
}
