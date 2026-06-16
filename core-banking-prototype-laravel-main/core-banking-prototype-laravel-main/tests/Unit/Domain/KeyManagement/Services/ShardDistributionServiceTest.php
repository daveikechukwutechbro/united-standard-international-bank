<?php

declare(strict_types=1);

use App\Domain\KeyManagement\Enums\ShardStatus;
use App\Domain\KeyManagement\Enums\ShardType;
use App\Domain\KeyManagement\Events\KeyShardsCreated;
use App\Domain\KeyManagement\Events\KeyShardsRotated;
use App\Domain\KeyManagement\HSM\HsmIntegrationService;
use App\Domain\KeyManagement\Models\KeyShardRecord;
use App\Domain\KeyManagement\Models\RecoveryBackup;
use App\Domain\KeyManagement\Services\EncryptionService;
use App\Domain\KeyManagement\Services\ShamirService;
use App\Domain\KeyManagement\Services\ShardDistributionService;
use App\Domain\KeyManagement\ValueObjects\KeyShard;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    Event::fake();

    $this->shamirService = Mockery::mock(ShamirService::class);
    $this->hsm = Mockery::mock(HsmIntegrationService::class);
    $this->encryption = Mockery::mock(EncryptionService::class);

    $this->service = new ShardDistributionService(
        shamirService: $this->shamirService,
        hsm: $this->hsm,
        encryption: $this->encryption
    );

    $this->user = User::factory()->create();
    $this->userUuid = $this->user->uuid;
});

describe('ShardDistributionService', function (): void {
    describe('createAndDistribute', function (): void {
        it('splits key and returns device shard with storage confirmation', function (): void {
            $privateKey = 'test-private-key-for-splitting';
            $deviceShardData = 'device-shard-base64-data';
            $authShardData = 'auth-shard-hsm-encrypted';
            $recoveryShardData = 'recovery-shard-user-encrypted';

            $this->shamirService
                ->shouldReceive('splitKey')
                ->once()
                ->with($privateKey, $this->userUuid)
                ->andReturn([
                    'device'   => new KeyShard(ShardType::DEVICE, $deviceShardData, 'device-enclave', $this->userUuid, 1),
                    'auth'     => new KeyShard(ShardType::AUTH, $authShardData, 'hsm', $this->userUuid, 2),
                    'recovery' => new KeyShard(ShardType::RECOVERY, $recoveryShardData, 'user-cloud', $this->userUuid, 3),
                ]);

            $this->hsm
                ->shouldReceive('store')
                ->once()
                ->with(Mockery::pattern('/^auth-shard:' . preg_quote($this->userUuid, '/') . ':v\d+$/'), $authShardData)
                ->andReturn(true);

            $result = $this->service->createAndDistribute($privateKey, $this->userUuid);

            expect($result)->toBeArray()
                ->and($result['device'])->toBe($deviceShardData)
                ->and($result['auth_stored'])->toBeTrue()
                ->and($result['recovery_stored'])->toBeTrue()
                ->and($result['key_version'])->toStartWith('v');
        });

        it('creates a recovery backup record in the database', function (): void {
            $recoveryShardData = 'recovery-encrypted-data';

            $this->shamirService
                ->shouldReceive('splitKey')
                ->andReturn([
                    'device'   => new KeyShard(ShardType::DEVICE, 'device-data', 'device-enclave', $this->userUuid, 1),
                    'auth'     => new KeyShard(ShardType::AUTH, 'auth-data', 'hsm', $this->userUuid, 2),
                    'recovery' => new KeyShard(ShardType::RECOVERY, $recoveryShardData, 'user-cloud', $this->userUuid, 3),
                ]);

            $this->hsm->shouldReceive('store')->andReturn(true);

            $this->service->createAndDistribute('private-key', $this->userUuid);

            $backup = RecoveryBackup::query()->forUser($this->userUuid)->first();
            expect($backup)->not->toBeNull()
                ->and($backup->encrypted_backup)->toBe($recoveryShardData)
                ->and($backup->encryption_method)->toBe('aes-256-gcm')
                ->and($backup->backup_hash)->toBe(hash('sha256', $recoveryShardData))
                ->and($backup->is_verified)->toBeFalse()
                ->and($backup->usage_count)->toBe(0);
        });

        it('creates shard records for all three shard types', function (): void {
            $privateKey = 'my-secret-key';
            $deviceData = 'device-data';
            $authData = 'auth-data';
            $recoveryData = 'recovery-data';

            $this->shamirService
                ->shouldReceive('splitKey')
                ->andReturn([
                    'device'   => new KeyShard(ShardType::DEVICE, $deviceData, 'device-enclave', $this->userUuid, 1),
                    'auth'     => new KeyShard(ShardType::AUTH, $authData, 'hsm', $this->userUuid, 2),
                    'recovery' => new KeyShard(ShardType::RECOVERY, $recoveryData, 'user-cloud', $this->userUuid, 3),
                ]);

            $this->hsm->shouldReceive('store')->andReturn(true);

            $this->service->createAndDistribute($privateKey, $this->userUuid);

            $shardRecords = KeyShardRecord::query()->forUser($this->userUuid)->get();
            expect($shardRecords)->toHaveCount(3);

            // Device shard should store only a hash, not the actual data
            $deviceRecord = $shardRecords->firstWhere('shard_type', ShardType::DEVICE);
            expect($deviceRecord)->not->toBeNull()
                ->and($deviceRecord->encrypted_data)->toBe(hash('sha256', $deviceData))
                ->and($deviceRecord->shard_index)->toBe(1)
                ->and($deviceRecord->encrypted_for)->toBe('device-enclave')
                ->and($deviceRecord->status)->toBe(ShardStatus::ACTIVE);

            // Auth shard stores actual encrypted data
            $authRecord = $shardRecords->firstWhere('shard_type', ShardType::AUTH);
            expect($authRecord)->not->toBeNull()
                ->and($authRecord->encrypted_data)->toBe($authData)
                ->and($authRecord->shard_index)->toBe(2)
                ->and($authRecord->encrypted_for)->toBe('hsm');

            // Recovery shard stores actual encrypted data
            $recoveryRecord = $shardRecords->firstWhere('shard_type', ShardType::RECOVERY);
            expect($recoveryRecord)->not->toBeNull()
                ->and($recoveryRecord->encrypted_data)->toBe($recoveryData)
                ->and($recoveryRecord->shard_index)->toBe(3)
                ->and($recoveryRecord->encrypted_for)->toBe('user-cloud');
        });

        it('stores the public key hash in shard records', function (): void {
            $privateKey = 'my-private-key';

            $this->shamirService
                ->shouldReceive('splitKey')
                ->andReturn([
                    'device'   => new KeyShard(ShardType::DEVICE, 'd', 'device-enclave', $this->userUuid, 1),
                    'auth'     => new KeyShard(ShardType::AUTH, 'a', 'hsm', $this->userUuid, 2),
                    'recovery' => new KeyShard(ShardType::RECOVERY, 'r', 'user-cloud', $this->userUuid, 3),
                ]);

            $this->hsm->shouldReceive('store')->andReturn(true);

            $this->service->createAndDistribute($privateKey, $this->userUuid);

            $records = KeyShardRecord::query()->forUser($this->userUuid)->get();
            $expectedHash = hash('sha256', $privateKey);

            foreach ($records as $record) {
                expect($record->public_key_hash)->toBe($expectedHash);
            }
        });

        it('dispatches KeyShardsCreated event', function (): void {
            $this->shamirService
                ->shouldReceive('splitKey')
                ->andReturn([
                    'device'   => new KeyShard(ShardType::DEVICE, 'd', 'device-enclave', $this->userUuid, 1),
                    'auth'     => new KeyShard(ShardType::AUTH, 'a', 'hsm', $this->userUuid, 2),
                    'recovery' => new KeyShard(ShardType::RECOVERY, 'r', 'user-cloud', $this->userUuid, 3),
                ]);

            $this->hsm->shouldReceive('store')->andReturn(true);

            $result = $this->service->createAndDistribute('key', $this->userUuid);

            Event::assertDispatched(KeyShardsCreated::class, function ($event) use ($result): bool {
                return $event->userUuid === $this->userUuid
                    && $event->keyVersion === $result['key_version'];
            });
        });

        it('stores auth shard in HSM with correct secret ID format', function (): void {
            $authData = 'auth-encrypted-shard';

            $this->shamirService
                ->shouldReceive('splitKey')
                ->andReturn([
                    'device'   => new KeyShard(ShardType::DEVICE, 'd', 'device-enclave', $this->userUuid, 1),
                    'auth'     => new KeyShard(ShardType::AUTH, $authData, 'hsm', $this->userUuid, 2),
                    'recovery' => new KeyShard(ShardType::RECOVERY, 'r', 'user-cloud', $this->userUuid, 3),
                ]);

            $this->hsm
                ->shouldReceive('store')
                ->once()
                ->with(
                    Mockery::on(function (string $secretId): bool {
                        // Format: auth-shard:{userUuid}:{keyVersion}
                        return str_starts_with($secretId, "auth-shard:{$this->userUuid}:v");
                    }),
                    $authData
                )
                ->andReturn(true);

            $this->service->createAndDistribute('key', $this->userUuid);
        });
    });

    describe('rotateShards', function (): void {
        it('marks old shards as rotated and creates new ones', function (): void {
            $oldKeyVersion = 'v-old-123';

            // Create existing shards with old version
            foreach (ShardType::cases() as $index => $type) {
                KeyShardRecord::create([
                    'user_uuid'       => $this->userUuid,
                    'shard_type'      => $type,
                    'shard_index'     => $index + 1,
                    'encrypted_data'  => "old-data-{$type->value}",
                    'encrypted_for'   => 'test',
                    'key_version'     => $oldKeyVersion,
                    'status'          => ShardStatus::ACTIVE,
                    'public_key_hash' => hash('sha256', 'old-key'),
                ]);
            }

            $this->shamirService
                ->shouldReceive('splitKey')
                ->andReturn([
                    'device'   => new KeyShard(ShardType::DEVICE, 'new-d', 'device-enclave', $this->userUuid, 1),
                    'auth'     => new KeyShard(ShardType::AUTH, 'new-a', 'hsm', $this->userUuid, 2),
                    'recovery' => new KeyShard(ShardType::RECOVERY, 'new-r', 'user-cloud', $this->userUuid, 3),
                ]);

            $this->hsm->shouldReceive('store')->andReturn(true);

            $result = $this->service->rotateShards('new-private-key', $this->userUuid, $oldKeyVersion);

            // Old shards should be marked as rotated
            $oldShards = KeyShardRecord::query()
                ->forUser($this->userUuid)
                ->where('key_version', $oldKeyVersion)
                ->get();

            foreach ($oldShards as $shard) {
                expect($shard->status)->toBe(ShardStatus::ROTATED);
            }

            // New shards should be active
            expect($result['device'])->toBe('new-d')
                ->and($result['auth_stored'])->toBeTrue()
                ->and($result['recovery_stored'])->toBeTrue()
                ->and($result['key_version'])->not->toBe($oldKeyVersion);
        });

        it('dispatches KeyShardsRotated event with old and new versions', function (): void {
            $oldKeyVersion = 'v-old';

            $this->shamirService
                ->shouldReceive('splitKey')
                ->andReturn([
                    'device'   => new KeyShard(ShardType::DEVICE, 'd', 'device-enclave', $this->userUuid, 1),
                    'auth'     => new KeyShard(ShardType::AUTH, 'a', 'hsm', $this->userUuid, 2),
                    'recovery' => new KeyShard(ShardType::RECOVERY, 'r', 'user-cloud', $this->userUuid, 3),
                ]);

            $this->hsm->shouldReceive('store')->andReturn(true);

            $result = $this->service->rotateShards('key', $this->userUuid, $oldKeyVersion);

            Event::assertDispatched(KeyShardsRotated::class, function ($event) use ($oldKeyVersion, $result): bool {
                return $event->userUuid === $this->userUuid
                    && $event->oldKeyVersion === $oldKeyVersion
                    && $event->newKeyVersion === $result['key_version'];
            });
        });

        it('dispatches both KeyShardsCreated and KeyShardsRotated events', function (): void {
            $this->shamirService
                ->shouldReceive('splitKey')
                ->andReturn([
                    'device'   => new KeyShard(ShardType::DEVICE, 'd', 'device-enclave', $this->userUuid, 1),
                    'auth'     => new KeyShard(ShardType::AUTH, 'a', 'hsm', $this->userUuid, 2),
                    'recovery' => new KeyShard(ShardType::RECOVERY, 'r', 'user-cloud', $this->userUuid, 3),
                ]);

            $this->hsm->shouldReceive('store')->andReturn(true);

            $this->service->rotateShards('key', $this->userUuid, 'v-old');

            Event::assertDispatched(KeyShardsCreated::class);
            Event::assertDispatched(KeyShardsRotated::class);
        });
    });

    describe('revokeAllShards', function (): void {
        it('revokes all active shards for a user', function (): void {
            foreach (ShardType::cases() as $index => $type) {
                KeyShardRecord::create([
                    'user_uuid'       => $this->userUuid,
                    'shard_type'      => $type,
                    'shard_index'     => $index + 1,
                    'encrypted_data'  => "data-{$type->value}",
                    'encrypted_for'   => 'test',
                    'key_version'     => 'v1',
                    'status'          => ShardStatus::ACTIVE,
                    'public_key_hash' => hash('sha256', 'key'),
                ]);
            }

            $revokedCount = $this->service->revokeAllShards($this->userUuid);

            expect($revokedCount)->toBe(3);

            $activeShards = KeyShardRecord::query()
                ->forUser($this->userUuid)
                ->active()
                ->count();

            expect($activeShards)->toBe(0);

            $revokedShards = KeyShardRecord::query()
                ->forUser($this->userUuid)
                ->where('status', ShardStatus::REVOKED)
                ->count();

            expect($revokedShards)->toBe(3);
        });

        it('returns zero when no active shards exist', function (): void {
            $revokedCount = $this->service->revokeAllShards($this->userUuid);

            expect($revokedCount)->toBe(0);
        });

        it('does not revoke shards belonging to other users', function (): void {
            $otherUser = User::factory()->create();

            // Create shards for both users
            foreach ([$this->userUuid, $otherUser->uuid] as $uuid) {
                KeyShardRecord::create([
                    'user_uuid'       => $uuid,
                    'shard_type'      => ShardType::DEVICE,
                    'shard_index'     => 1,
                    'encrypted_data'  => 'data',
                    'encrypted_for'   => 'device-enclave',
                    'key_version'     => 'v1',
                    'status'          => ShardStatus::ACTIVE,
                    'public_key_hash' => hash('sha256', 'key'),
                ]);
            }

            $this->service->revokeAllShards($this->userUuid);

            // Other user's shard should still be active
            $otherUserShard = KeyShardRecord::query()
                ->forUser($otherUser->uuid)
                ->first();

            expect($otherUserShard->status)->toBe(ShardStatus::ACTIVE);
        });

        it('does not affect already revoked or rotated shards', function (): void {
            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::DEVICE,
                'shard_index'     => 1,
                'encrypted_data'  => 'data',
                'encrypted_for'   => 'device-enclave',
                'key_version'     => 'v1',
                'status'          => ShardStatus::REVOKED,
                'public_key_hash' => hash('sha256', 'key'),
            ]);

            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::AUTH,
                'shard_index'     => 2,
                'encrypted_data'  => 'data',
                'encrypted_for'   => 'hsm',
                'key_version'     => 'v1',
                'status'          => ShardStatus::ROTATED,
                'public_key_hash' => hash('sha256', 'key'),
            ]);

            $revokedCount = $this->service->revokeAllShards($this->userUuid);

            expect($revokedCount)->toBe(0);
        });
    });

    describe('getShardsSummary', function (): void {
        it('returns summary for all shard types', function (): void {
            KeyShardRecord::create([
                'user_uuid'        => $this->userUuid,
                'shard_type'       => ShardType::DEVICE,
                'shard_index'      => 1,
                'encrypted_data'   => 'data',
                'encrypted_for'    => 'device-enclave',
                'key_version'      => 'v1',
                'status'           => ShardStatus::ACTIVE,
                'public_key_hash'  => hash('sha256', 'key'),
                'last_accessed_at' => now(),
            ]);

            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::AUTH,
                'shard_index'     => 2,
                'encrypted_data'  => 'data',
                'encrypted_for'   => 'hsm',
                'key_version'     => 'v1',
                'status'          => ShardStatus::ACTIVE,
                'public_key_hash' => hash('sha256', 'key'),
            ]);

            $summary = $this->service->getShardsSummary($this->userUuid);

            expect($summary)->toHaveKeys(['device', 'auth', 'recovery'])
                ->and($summary['device']['exists'])->toBeTrue()
                ->and($summary['device']['last_accessed'])->not->toBeNull()
                ->and($summary['auth']['exists'])->toBeTrue()
                ->and($summary['auth']['last_accessed'])->toBeNull()
                ->and($summary['recovery']['exists'])->toBeFalse()
                ->and($summary['recovery']['last_accessed'])->toBeNull();
        });

        it('returns all non-existing when user has no shards', function (): void {
            $summary = $this->service->getShardsSummary($this->userUuid);

            foreach (ShardType::cases() as $type) {
                expect($summary[$type->value]['exists'])->toBeFalse()
                    ->and($summary[$type->value]['last_accessed'])->toBeNull();
            }
        });
    });

    describe('verifyRecoveryShard', function (): void {
        it('returns true when recovery shard matches verified backup', function (): void {
            $decryptedData = 'decrypted-recovery-shard-content';
            $encryptedData = 'encrypted-recovery-shard';

            RecoveryBackup::create([
                'user_uuid'         => $this->userUuid,
                'encrypted_backup'  => $encryptedData,
                'encryption_method' => 'aes-256-gcm',
                'key_version'       => 'v1',
                'backup_hash'       => hash('sha256', $decryptedData),
                'is_verified'       => true,
                'usage_count'       => 0,
            ]);

            $this->encryption
                ->shouldReceive('decryptForUser')
                ->once()
                ->with($encryptedData, $this->userUuid)
                ->andReturn($decryptedData);

            $isValid = $this->service->verifyRecoveryShard($this->userUuid, $encryptedData);

            expect($isValid)->toBeTrue();
        });

        it('returns false when no verified backup exists', function (): void {
            $isValid = $this->service->verifyRecoveryShard($this->userUuid, 'some-data');

            expect($isValid)->toBeFalse();
        });

        it('returns false when backup exists but is not verified', function (): void {
            RecoveryBackup::create([
                'user_uuid'         => $this->userUuid,
                'encrypted_backup'  => 'data',
                'encryption_method' => 'aes-256-gcm',
                'key_version'       => 'v1',
                'backup_hash'       => hash('sha256', 'data'),
                'is_verified'       => false,
                'usage_count'       => 0,
            ]);

            $isValid = $this->service->verifyRecoveryShard($this->userUuid, 'data');

            expect($isValid)->toBeFalse();
        });

        it('returns false when decryption fails', function (): void {
            RecoveryBackup::create([
                'user_uuid'         => $this->userUuid,
                'encrypted_backup'  => 'data',
                'encryption_method' => 'aes-256-gcm',
                'key_version'       => 'v1',
                'backup_hash'       => hash('sha256', 'original'),
                'is_verified'       => true,
                'usage_count'       => 0,
            ]);

            $this->encryption
                ->shouldReceive('decryptForUser')
                ->andThrow(new RuntimeException('Decryption failed'));

            $isValid = $this->service->verifyRecoveryShard($this->userUuid, 'corrupted-data');

            expect($isValid)->toBeFalse();
        });

        it('returns false when hash does not match', function (): void {
            RecoveryBackup::create([
                'user_uuid'         => $this->userUuid,
                'encrypted_backup'  => 'data',
                'encryption_method' => 'aes-256-gcm',
                'key_version'       => 'v1',
                'backup_hash'       => hash('sha256', 'original-content'),
                'is_verified'       => true,
                'usage_count'       => 0,
            ]);

            $this->encryption
                ->shouldReceive('decryptForUser')
                ->andReturn('different-content');

            $isValid = $this->service->verifyRecoveryShard($this->userUuid, 'data');

            expect($isValid)->toBeFalse();
        });
    });
});
