<?php

declare(strict_types=1);

use App\Domain\KeyManagement\Enums\ShardStatus;
use App\Domain\KeyManagement\Enums\ShardType;
use App\Domain\KeyManagement\Events\KeyReconstructed;
use App\Domain\KeyManagement\Events\KeyReconstructionFailed;
use App\Domain\KeyManagement\Models\KeyReconstructionLog;
use App\Domain\KeyManagement\Models\KeyShardRecord;
use App\Domain\KeyManagement\Services\KeyReconstructionService;
use App\Domain\KeyManagement\Services\ShamirService;
use App\Domain\KeyManagement\ValueObjects\KeyShard;
use App\Domain\KeyManagement\ValueObjects\ReconstructedKey;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    Event::fake();

    $this->shamirService = Mockery::mock(ShamirService::class);
    $this->request = Mockery::mock(Illuminate\Http\Request::class);
    $this->request->shouldReceive('ip')->andReturn('127.0.0.1')->byDefault();
    $this->request->shouldReceive('userAgent')->andReturn('TestAgent/1.0')->byDefault();
    $this->request->shouldReceive('header')->with('X-Device-Id')->andReturn('test-device-001')->byDefault();

    $this->service = new KeyReconstructionService(
        shamirService: $this->shamirService,
        request: $this->request
    );

    $this->user = User::factory()->create();
    $this->userUuid = $this->user->uuid;
});

describe('KeyReconstructionService', function (): void {
    describe('reconstructWithAuth', function (): void {
        it('reconstructs key using device shard and auth shard from database', function (): void {
            $deviceShardData = 'device-shard-data-encrypted';
            $authShardEncryptedData = 'auth-shard-data-encrypted';
            $sessionToken = 'valid-session-token';

            // Create an active auth shard record in the database
            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::AUTH,
                'shard_index'     => 2,
                'encrypted_data'  => $authShardEncryptedData,
                'encrypted_for'   => 'hsm',
                'key_version'     => 'v1',
                'status'          => ShardStatus::ACTIVE,
                'public_key_hash' => hash('sha256', 'test-private-key'),
            ]);

            $expectedReconstructedKey = new ReconstructedKey(
                privateKey: 'test-private-key',
                userId: $this->userUuid,
                reconstructedAt: new DateTimeImmutable(),
                ttlSeconds: 300
            );

            $this->shamirService
                ->shouldReceive('reconstructKey')
                ->once()
                ->with(
                    Mockery::on(fn (KeyShard $shard) => $shard->type === ShardType::DEVICE
                        && $shard->data === $deviceShardData
                        && $shard->userId === $this->userUuid
                        && $shard->index === 1),
                    Mockery::on(fn (KeyShard $shard) => $shard->type === ShardType::AUTH
                        && $shard->data === $authShardEncryptedData
                        && $shard->userId === $this->userUuid
                        && $shard->index === 2)
                )
                ->andReturn($expectedReconstructedKey);

            $result = $this->service->reconstructWithAuth(
                $this->userUuid,
                $deviceShardData,
                $sessionToken
            );

            expect($result)->toBeInstanceOf(ReconstructedKey::class)
                ->and($result->privateKey)->toBe('test-private-key')
                ->and($result->userId)->toBe($this->userUuid);

            Event::assertDispatched(KeyReconstructed::class, function ($event): bool {
                return $event->userUuid === $this->userUuid
                    && $event->purpose === 'transaction_signing'
                    && in_array('device', $event->shardsUsed, true)
                    && in_array('auth', $event->shardsUsed, true);
            });
        });

        it('creates a successful reconstruction log entry', function (): void {
            $authShardEncryptedData = 'auth-shard-data';

            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::AUTH,
                'shard_index'     => 2,
                'encrypted_data'  => $authShardEncryptedData,
                'encrypted_for'   => 'hsm',
                'key_version'     => 'v1',
                'status'          => ShardStatus::ACTIVE,
                'public_key_hash' => hash('sha256', 'key'),
            ]);

            $this->shamirService
                ->shouldReceive('reconstructKey')
                ->andReturn(new ReconstructedKey(
                    privateKey: 'key',
                    userId: $this->userUuid,
                    reconstructedAt: new DateTimeImmutable(),
                ));

            $this->service->reconstructWithAuth(
                $this->userUuid,
                'device-data',
                'session-token'
            );

            $log = KeyReconstructionLog::query()->forUser($this->userUuid)->first();
            expect($log)->not->toBeNull()
                ->and($log->success)->toBeTrue()
                ->and($log->purpose)->toBe('transaction_signing')
                ->and($log->ip_address)->toBe('127.0.0.1')
                ->and($log->user_agent)->toBe('TestAgent/1.0')
                ->and($log->device_id)->toBe('test-device-001')
                ->and($log->failure_reason)->toBeNull();
        });

        it('marks auth shard as accessed after successful reconstruction', function (): void {
            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::AUTH,
                'shard_index'     => 2,
                'encrypted_data'  => 'auth-data',
                'encrypted_for'   => 'hsm',
                'key_version'     => 'v1',
                'status'          => ShardStatus::ACTIVE,
                'public_key_hash' => hash('sha256', 'key'),
            ]);

            $this->shamirService
                ->shouldReceive('reconstructKey')
                ->andReturn(new ReconstructedKey(
                    privateKey: 'key',
                    userId: $this->userUuid,
                    reconstructedAt: new DateTimeImmutable(),
                ));

            $this->service->reconstructWithAuth(
                $this->userUuid,
                'device-data',
                'session'
            );

            $authShard = KeyShardRecord::query()
                ->forUser($this->userUuid)
                ->ofType(ShardType::AUTH)
                ->first();

            expect($authShard->last_accessed_at)->not->toBeNull();
        });

        it('throws exception when no active auth shard exists', function (): void {
            // No auth shard record in database
            $this->service->reconstructWithAuth(
                $this->userUuid,
                'device-data',
                'session'
            );
        })->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);

        it('does not use revoked auth shards', function (): void {
            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::AUTH,
                'shard_index'     => 2,
                'encrypted_data'  => 'auth-data',
                'encrypted_for'   => 'hsm',
                'key_version'     => 'v1',
                'status'          => ShardStatus::REVOKED,
                'public_key_hash' => hash('sha256', 'key'),
            ]);

            $this->service->reconstructWithAuth(
                $this->userUuid,
                'device-data',
                'session'
            );
        })->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

    describe('reconstructWithRecovery', function (): void {
        it('reconstructs key using device shard and recovery shard', function (): void {
            $deviceShardData = 'device-shard-data';
            $recoveryShardData = 'recovery-shard-data';

            // Create an active shard record so the logging query can find key_version
            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::DEVICE,
                'shard_index'     => 1,
                'encrypted_data'  => hash('sha256', $deviceShardData),
                'encrypted_for'   => 'device-enclave',
                'key_version'     => 'v1',
                'status'          => ShardStatus::ACTIVE,
                'public_key_hash' => hash('sha256', 'test-key'),
            ]);

            $expectedReconstructedKey = new ReconstructedKey(
                privateKey: 'test-private-key',
                userId: $this->userUuid,
                reconstructedAt: new DateTimeImmutable(),
                ttlSeconds: 300
            );

            $this->shamirService
                ->shouldReceive('reconstructKey')
                ->once()
                ->with(
                    Mockery::on(fn (KeyShard $shard) => $shard->type === ShardType::DEVICE
                        && $shard->data === $deviceShardData
                        && $shard->index === 1),
                    Mockery::on(fn (KeyShard $shard) => $shard->type === ShardType::RECOVERY
                        && $shard->data === $recoveryShardData
                        && $shard->index === 3)
                )
                ->andReturn($expectedReconstructedKey);

            $result = $this->service->reconstructWithRecovery(
                $this->userUuid,
                $deviceShardData,
                $recoveryShardData
            );

            expect($result)->toBeInstanceOf(ReconstructedKey::class)
                ->and($result->privateKey)->toBe('test-private-key');

            Event::assertDispatched(KeyReconstructed::class, function ($event): bool {
                return $event->userUuid === $this->userUuid
                    && $event->purpose === 'device_recovery'
                    && in_array('device', $event->shardsUsed, true)
                    && in_array('recovery', $event->shardsUsed, true);
            });
        });

        it('creates reconstruction log with device_recovery purpose', function (): void {
            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::DEVICE,
                'shard_index'     => 1,
                'encrypted_data'  => 'device-hash',
                'encrypted_for'   => 'device-enclave',
                'key_version'     => 'v1',
                'status'          => ShardStatus::ACTIVE,
                'public_key_hash' => hash('sha256', 'key'),
            ]);

            $this->shamirService
                ->shouldReceive('reconstructKey')
                ->andReturn(new ReconstructedKey(
                    privateKey: 'key',
                    userId: $this->userUuid,
                    reconstructedAt: new DateTimeImmutable(),
                ));

            $this->service->reconstructWithRecovery(
                $this->userUuid,
                'device-data',
                'recovery-data'
            );

            $log = KeyReconstructionLog::query()->forUser($this->userUuid)->first();
            expect($log)->not->toBeNull()
                ->and($log->purpose)->toBe('device_recovery')
                ->and($log->success)->toBeTrue();
        });
    });

    describe('reconstruct', function (): void {
        it('throws exception when not exactly 2 shards are provided', function (): void {
            $shard = new KeyShard(
                type: ShardType::DEVICE,
                data: 'data',
                encryptedFor: 'device-enclave',
                userId: $this->userUuid,
                index: 1
            );

            $this->service->reconstruct(
                $this->userUuid,
                [$shard],
                'test'
            );
        })->throws(RuntimeException::class, 'Exactly 2 shards required for reconstruction');

        it('throws exception when more than 2 shards are provided', function (): void {
            $shards = [
                new KeyShard(ShardType::DEVICE, 'data1', 'device-enclave', $this->userUuid, 1),
                new KeyShard(ShardType::AUTH, 'data2', 'hsm', $this->userUuid, 2),
                new KeyShard(ShardType::RECOVERY, 'data3', 'user-cloud', $this->userUuid, 3),
            ];

            $this->service->reconstruct(
                $this->userUuid,
                $shards,
                'test'
            );
        })->throws(RuntimeException::class, 'Exactly 2 shards required for reconstruction');

        it('logs failure and dispatches event when ShamirService throws', function (): void {
            $deviceShard = new KeyShard(ShardType::DEVICE, 'bad-data', 'device-enclave', $this->userUuid, 1);
            $authShard = new KeyShard(ShardType::AUTH, 'bad-data', 'hsm', $this->userUuid, 2);

            $this->shamirService
                ->shouldReceive('reconstructKey')
                ->andThrow(new RuntimeException('Invalid shard format'));

            try {
                $this->service->reconstruct(
                    $this->userUuid,
                    [$deviceShard, $authShard],
                    'transaction_signing'
                );
            } catch (RuntimeException) {
                // Expected
            }

            $log = KeyReconstructionLog::query()->forUser($this->userUuid)->first();
            expect($log)->not->toBeNull()
                ->and($log->success)->toBeFalse()
                ->and($log->failure_reason)->toBe('Invalid shard format');

            Event::assertDispatched(KeyReconstructionFailed::class, function ($event): bool {
                return $event->userUuid === $this->userUuid
                    && $event->reason === 'Invalid shard format';
            });
        });

        it('re-throws the original exception after logging failure', function (): void {
            $deviceShard = new KeyShard(ShardType::DEVICE, 'data', 'device-enclave', $this->userUuid, 1);
            $authShard = new KeyShard(ShardType::AUTH, 'data', 'hsm', $this->userUuid, 2);

            $this->shamirService
                ->shouldReceive('reconstructKey')
                ->andThrow(new RuntimeException('Decryption failed'));

            expect(fn () => $this->service->reconstruct(
                $this->userUuid,
                [$deviceShard, $authShard],
                'signing'
            ))->toThrow(RuntimeException::class, 'Decryption failed');
        });

        it('does not mark device shard as accessed in database', function (): void {
            // Device shard is client-stored, so marking it in DB should be skipped
            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::DEVICE,
                'shard_index'     => 1,
                'encrypted_data'  => 'device-hash',
                'encrypted_for'   => 'device-enclave',
                'key_version'     => 'v1',
                'status'          => ShardStatus::ACTIVE,
                'public_key_hash' => hash('sha256', 'key'),
            ]);

            KeyShardRecord::create([
                'user_uuid'       => $this->userUuid,
                'shard_type'      => ShardType::AUTH,
                'shard_index'     => 2,
                'encrypted_data'  => 'auth-data',
                'encrypted_for'   => 'hsm',
                'key_version'     => 'v1',
                'status'          => ShardStatus::ACTIVE,
                'public_key_hash' => hash('sha256', 'key'),
            ]);

            $this->shamirService
                ->shouldReceive('reconstructKey')
                ->andReturn(new ReconstructedKey(
                    privateKey: 'key',
                    userId: $this->userUuid,
                    reconstructedAt: new DateTimeImmutable(),
                ));

            $deviceShard = new KeyShard(ShardType::DEVICE, 'data', 'device-enclave', $this->userUuid, 1);
            $authShard = new KeyShard(ShardType::AUTH, 'data', 'hsm', $this->userUuid, 2);

            $this->service->reconstruct($this->userUuid, [$deviceShard, $authShard], 'signing');

            $deviceRecord = KeyShardRecord::query()
                ->forUser($this->userUuid)
                ->ofType(ShardType::DEVICE)
                ->first();

            // Device shard should NOT be marked as accessed
            expect($deviceRecord->last_accessed_at)->toBeNull();
        });
    });

    describe('canReconstruct', function (): void {
        it('returns true when no recent attempts exist', function (): void {
            expect($this->service->canReconstruct($this->userUuid))->toBeTrue();
        });

        it('returns true when recent attempts are below the threshold', function (): void {
            // Create 5 recent attempts
            for ($i = 0; $i < 5; $i++) {
                KeyReconstructionLog::create([
                    'user_uuid'   => $this->userUuid,
                    'key_version' => 'v1',
                    'shards_used' => ['device', 'auth'],
                    'purpose'     => 'transaction_signing',
                    'ip_address'  => '127.0.0.1',
                    'success'     => true,
                ]);
            }

            expect($this->service->canReconstruct($this->userUuid))->toBeTrue();
        });

        it('returns false when recent attempts reach the limit', function (): void {
            // Create 10 recent attempts (default max is 10)
            for ($i = 0; $i < 10; $i++) {
                KeyReconstructionLog::create([
                    'user_uuid'   => $this->userUuid,
                    'key_version' => 'v1',
                    'shards_used' => ['device', 'auth'],
                    'purpose'     => 'transaction_signing',
                    'ip_address'  => '127.0.0.1',
                    'success'     => true,
                ]);
            }

            expect($this->service->canReconstruct($this->userUuid))->toBeFalse();
        });

        it('respects custom max attempts parameter', function (): void {
            // Create 3 recent attempts
            for ($i = 0; $i < 3; $i++) {
                KeyReconstructionLog::create([
                    'user_uuid'   => $this->userUuid,
                    'key_version' => 'v1',
                    'shards_used' => ['device', 'auth'],
                    'purpose'     => 'transaction_signing',
                    'ip_address'  => '127.0.0.1',
                    'success'     => true,
                ]);
            }

            expect($this->service->canReconstruct($this->userUuid, 3))->toBeFalse()
                ->and($this->service->canReconstruct($this->userUuid, 5))->toBeTrue();
        });

        it('does not count attempts older than one hour', function (): void {
            // Create 10 attempts more than an hour ago
            for ($i = 0; $i < 10; $i++) {
                $log = KeyReconstructionLog::create([
                    'user_uuid'   => $this->userUuid,
                    'key_version' => 'v1',
                    'shards_used' => ['device', 'auth'],
                    'purpose'     => 'transaction_signing',
                    'ip_address'  => '127.0.0.1',
                    'success'     => true,
                ]);
                // Manually backdate the record
                $log->forceFill(['created_at' => now()->subHours(2)])->save();
            }

            expect($this->service->canReconstruct($this->userUuid))->toBeTrue();
        });
    });

    describe('getRecentLogs', function (): void {
        it('returns recent reconstruction logs ordered by newest first', function (): void {
            for ($i = 1; $i <= 3; $i++) {
                KeyReconstructionLog::create([
                    'user_uuid'   => $this->userUuid,
                    'key_version' => "v{$i}",
                    'shards_used' => ['device', 'auth'],
                    'purpose'     => 'transaction_signing',
                    'ip_address'  => '127.0.0.1',
                    'success'     => true,
                ]);
            }

            $logs = $this->service->getRecentLogs($this->userUuid);

            expect($logs)->toHaveCount(3);
        });

        it('respects the limit parameter', function (): void {
            for ($i = 1; $i <= 5; $i++) {
                KeyReconstructionLog::create([
                    'user_uuid'   => $this->userUuid,
                    'key_version' => "v{$i}",
                    'shards_used' => ['device', 'auth'],
                    'purpose'     => 'signing',
                    'ip_address'  => '127.0.0.1',
                    'success'     => true,
                ]);
            }

            $logs = $this->service->getRecentLogs($this->userUuid, 2);

            expect($logs)->toHaveCount(2);
        });

        it('returns empty collection when no logs exist', function (): void {
            $logs = $this->service->getRecentLogs($this->userUuid);

            expect($logs)->toBeEmpty();
        });

        it('only returns logs for the specified user', function (): void {
            $otherUser = User::factory()->create();

            KeyReconstructionLog::create([
                'user_uuid'   => $this->userUuid,
                'key_version' => 'v1',
                'shards_used' => ['device', 'auth'],
                'purpose'     => 'signing',
                'ip_address'  => '127.0.0.1',
                'success'     => true,
            ]);

            KeyReconstructionLog::create([
                'user_uuid'   => $otherUser->uuid,
                'key_version' => 'v1',
                'shards_used' => ['device', 'recovery'],
                'purpose'     => 'recovery',
                'ip_address'  => '127.0.0.1',
                'success'     => true,
            ]);

            $logs = $this->service->getRecentLogs($this->userUuid);

            expect($logs)->toHaveCount(1)
                ->and($logs->first()->user_uuid)->toBe($this->userUuid);
        });
    });
});
