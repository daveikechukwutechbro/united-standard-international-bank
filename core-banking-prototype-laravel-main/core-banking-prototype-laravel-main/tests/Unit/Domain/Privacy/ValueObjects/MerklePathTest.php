<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Privacy\ValueObjects;

use App\Domain\Privacy\ValueObjects\MerklePath;
use Tests\TestCase;

class MerklePathTest extends TestCase
{
    private function createSamplePath(int $depth = 32): MerklePath
    {
        $siblings = [];
        $pathIndices = [];

        for ($i = 0; $i < $depth; $i++) {
            $siblings[] = '0x' . str_repeat(dechex($i % 16), 64);
            $pathIndices[] = $i % 2;
        }

        return new MerklePath(
            commitment: '0x' . str_repeat('a', 64),
            root: '0x' . str_repeat('b', 64),
            network: 'polygon',
            leafIndex: 42,
            siblings: $siblings,
            pathIndices: $pathIndices,
        );
    }

    public function test_can_create_merkle_path(): void
    {
        $path = $this->createSamplePath();

        $this->assertEquals('0x' . str_repeat('a', 64), $path->commitment);
        $this->assertEquals('0x' . str_repeat('b', 64), $path->root);
        $this->assertEquals('polygon', $path->network);
        $this->assertEquals(42, $path->leafIndex);
        $this->assertCount(32, $path->siblings);
        $this->assertCount(32, $path->pathIndices);
    }

    public function test_get_depth(): void
    {
        $path16 = $this->createSamplePath(16);
        $path32 = $this->createSamplePath(32);

        $this->assertEquals(16, $path16->getDepth());
        $this->assertEquals(32, $path32->getDepth());
    }

    public function test_is_valid_for_depth(): void
    {
        $path = $this->createSamplePath(32);

        $this->assertTrue($path->isValidForDepth(32));
        $this->assertFalse($path->isValidForDepth(16));
        $this->assertFalse($path->isValidForDepth(64));
    }

    public function test_to_array(): void
    {
        $path = $this->createSamplePath(4);

        $array = $path->toArray();

        $this->assertEquals('0x' . str_repeat('a', 64), $array['commitment']);
        $this->assertEquals('0x' . str_repeat('b', 64), $array['root']);
        $this->assertEquals('polygon', $array['network']);
        $this->assertEquals(42, $array['leaf_index']);
        $this->assertCount(4, $array['siblings']);
        $this->assertCount(4, $array['path_indices']);
        $this->assertEquals(4, $array['proof_depth']);
    }

    public function test_json_serializable(): void
    {
        $path = $this->createSamplePath(4);

        $json = json_encode($path);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('polygon', $decoded['network']);
        $this->assertEquals(42, $decoded['leaf_index']);
    }

    public function test_from_array(): void
    {
        $data = [
            'commitment'   => '0x' . str_repeat('c', 64),
            'root'         => '0x' . str_repeat('d', 64),
            'network'      => 'base',
            'leaf_index'   => 100,
            'siblings'     => ['0x' . str_repeat('1', 64), '0x' . str_repeat('2', 64)],
            'path_indices' => [0, 1],
        ];

        $path = MerklePath::fromArray($data);

        $this->assertEquals($data['commitment'], $path->commitment);
        $this->assertEquals($data['root'], $path->root);
        $this->assertEquals('base', $path->network);
        $this->assertEquals(100, $path->leafIndex);
        $this->assertCount(2, $path->siblings);
        $this->assertCount(2, $path->pathIndices);
    }
}
