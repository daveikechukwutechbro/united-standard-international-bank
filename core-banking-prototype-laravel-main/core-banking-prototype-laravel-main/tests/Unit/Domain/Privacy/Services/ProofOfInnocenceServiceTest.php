<?php

declare(strict_types=1);

use App\Domain\Privacy\Contracts\ZkProverInterface;
use App\Domain\Privacy\Enums\ProofType;
use App\Domain\Privacy\Events\ProofOfInnocenceGenerated;
use App\Domain\Privacy\Services\ProofOfInnocenceResult;
use App\Domain\Privacy\Services\ProofOfInnocenceService;
use App\Domain\Privacy\ValueObjects\ZkProof;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

describe('ProofOfInnocenceService', function () {
    beforeEach(function () {
        Event::fake();

        $this->prover = Mockery::mock(ZkProverInterface::class);
        $this->service = new ProofOfInnocenceService($this->prover);
    });

    describe('generateSanctionsClearanceProof', function () {
        it('generates a sanctions clearance proof', function () {
            $userId = 'user-123';
            $transactionHistory = ['0xtx1', '0xtx2'];
            $sanctionsRoot = '0x' . str_repeat('a', 64);

            $expectedProof = createTestProof([
                'sanctions_list_root' => $sanctionsRoot,
            ]);

            $this->prover
                ->shouldReceive('generateProof')
                ->once()
                ->withArgs(function (ProofType $type, array $privateInputs, array $publicInputs) use ($userId, $sanctionsRoot, $transactionHistory) {
                    return $type === ProofType::SANCTIONS_CLEAR
                        && $privateInputs['identity_hash'] === hash('sha256', $userId)
                        && $privateInputs['sanctions_list_hash'] === $sanctionsRoot
                        && $privateInputs['transaction_hashes'] === $transactionHistory
                        && $publicInputs['sanctions_list_root'] === $sanctionsRoot
                        && isset($publicInputs['user_commitment'])
                        && isset($publicInputs['proof_timestamp']);
                })
                ->andReturn($expectedProof);

            $proof = $this->service->generateSanctionsClearanceProof(
                $userId,
                $transactionHistory,
                $sanctionsRoot,
            );

            expect($proof)->toBeInstanceOf(ZkProof::class)
                ->and($proof->type)->toBe(ProofType::SANCTIONS_CLEAR);
        });

        it('dispatches ProofOfInnocenceGenerated event', function () {
            $expectedProof = createTestProof();

            $this->prover
                ->shouldReceive('generateProof')
                ->once()
                ->andReturn($expectedProof);

            $this->service->generateSanctionsClearanceProof(
                'user-456',
                ['0xtx1'],
                '0x' . str_repeat('b', 64),
            );

            Event::assertDispatched(ProofOfInnocenceGenerated::class, function ($event) {
                return $event->userId === 'user-456'
                    && $event->proofType === 'sanctions_clearance'
                    && ! empty($event->proofHash);
            });
        });

        it('passes identity hash as SHA-256 of user ID', function () {
            $userId = 'unique-user-id';
            $expectedHash = hash('sha256', $userId);
            $expectedProof = createTestProof();

            $this->prover
                ->shouldReceive('generateProof')
                ->once()
                ->withArgs(function (ProofType $type, array $privateInputs) use ($expectedHash) {
                    return $privateInputs['identity_hash'] === $expectedHash;
                })
                ->andReturn($expectedProof);

            $this->service->generateSanctionsClearanceProof(
                $userId,
                [],
                '0x' . str_repeat('c', 64),
            );
        });

        it('generates unique user commitments with random nonces', function () {
            $capturedPublicInputs = [];

            $this->prover
                ->shouldReceive('generateProof')
                ->twice()
                ->withArgs(function (ProofType $type, array $privateInputs, array $publicInputs) use (&$capturedPublicInputs) {
                    $capturedPublicInputs[] = $publicInputs['user_commitment'];

                    return true;
                })
                ->andReturn(createTestProof());

            $this->service->generateSanctionsClearanceProof(
                'user-123',
                [],
                '0x' . str_repeat('a', 64),
            );

            $this->service->generateSanctionsClearanceProof(
                'user-123',
                [],
                '0x' . str_repeat('a', 64),
            );

            // Same user, same inputs, but commitments should differ due to random nonce
            expect($capturedPublicInputs)->toHaveCount(2)
                ->and($capturedPublicInputs[0])->not->toBe($capturedPublicInputs[1]);
        });

        it('includes proof timestamp in public inputs', function () {
            $timeBefore = time();
            $expectedProof = createTestProof();

            $this->prover
                ->shouldReceive('generateProof')
                ->once()
                ->withArgs(function (ProofType $type, array $privateInputs, array $publicInputs) use ($timeBefore) {
                    return $publicInputs['proof_timestamp'] >= $timeBefore
                        && $publicInputs['proof_timestamp'] <= time();
                })
                ->andReturn($expectedProof);

            $this->service->generateSanctionsClearanceProof(
                'user-123',
                [],
                '0x' . str_repeat('a', 64),
            );
        });
    });

    describe('generateSourceClearanceProof', function () {
        it('generates a source clearance proof', function () {
            $transactionId = 'tx-789';
            $sourceAddresses = ['0xaddr1', '0xaddr2'];
            $illicitRoot = '0x' . str_repeat('d', 64);
            $merkleProof = ['0xproof1', '0xproof2'];

            $expectedProof = createTestProof([
                'illicit_list_root' => $illicitRoot,
            ]);

            $this->prover
                ->shouldReceive('generateProof')
                ->once()
                ->withArgs(function (ProofType $type, array $privateInputs, array $publicInputs) use ($transactionId, $illicitRoot, $sourceAddresses, $merkleProof) {
                    return $type === ProofType::SANCTIONS_CLEAR
                        && $privateInputs['identity_hash'] === hash('sha256', $transactionId)
                        && $privateInputs['sanctions_list_hash'] === $illicitRoot
                        && $privateInputs['source_addresses'] === $sourceAddresses
                        && $privateInputs['merkle_proof'] === $merkleProof
                        && $publicInputs['illicit_list_root'] === $illicitRoot
                        && isset($publicInputs['transaction_commitment'])
                        && isset($publicInputs['proof_timestamp']);
                })
                ->andReturn($expectedProof);

            $proof = $this->service->generateSourceClearanceProof(
                $transactionId,
                $sourceAddresses,
                $illicitRoot,
                $merkleProof,
            );

            expect($proof)->toBeInstanceOf(ZkProof::class);
        });

        it('generates unique transaction commitments with random nonces', function () {
            $capturedPublicInputs = [];

            $this->prover
                ->shouldReceive('generateProof')
                ->twice()
                ->withArgs(function (ProofType $type, array $privateInputs, array $publicInputs) use (&$capturedPublicInputs) {
                    $capturedPublicInputs[] = $publicInputs['transaction_commitment'];

                    return true;
                })
                ->andReturn(createTestProof());

            $this->service->generateSourceClearanceProof(
                'tx-123',
                ['0xaddr1'],
                '0x' . str_repeat('a', 64),
                ['0xproof1'],
            );

            $this->service->generateSourceClearanceProof(
                'tx-123',
                ['0xaddr1'],
                '0x' . str_repeat('a', 64),
                ['0xproof1'],
            );

            expect($capturedPublicInputs)->toHaveCount(2)
                ->and($capturedPublicInputs[0])->not->toBe($capturedPublicInputs[1]);
        });
    });

    describe('verifyProofOfInnocence', function () {
        it('returns valid result for a valid non-expired proof with matching sanctions root', function () {
            $sanctionsRoot = '0x' . str_repeat('a', 64);
            $expiresAt = new DateTimeImmutable('+30 days');

            $proof = createTestProof([
                'sanctions_list_root' => $sanctionsRoot,
            ], $expiresAt);

            $this->prover
                ->shouldReceive('verifyProof')
                ->once()
                ->with($proof)
                ->andReturn(true);

            $result = $this->service->verifyProofOfInnocence($proof, $sanctionsRoot);

            expect($result)->toBeInstanceOf(ProofOfInnocenceResult::class)
                ->and($result->valid)->toBeTrue()
                ->and($result->reason)->toBeNull()
                ->and($result->validUntil)->toBe($expiresAt);
        });

        it('returns invalid result when sanctions list root does not match', function () {
            $proofRoot = '0x' . str_repeat('a', 64);
            $currentRoot = '0x' . str_repeat('b', 64);

            $proof = createTestProof([
                'sanctions_list_root' => $proofRoot,
            ]);

            // Prover should NOT be called when roots mismatch
            $this->prover->shouldNotReceive('verifyProof');

            $result = $this->service->verifyProofOfInnocence($proof, $currentRoot);

            expect($result->valid)->toBeFalse()
                ->and($result->reason)->toBe('Proof generated against outdated sanctions list');
        });

        it('returns invalid result when ZK proof verification fails', function () {
            $sanctionsRoot = '0x' . str_repeat('a', 64);

            $proof = createTestProof([
                'sanctions_list_root' => $sanctionsRoot,
            ]);

            $this->prover
                ->shouldReceive('verifyProof')
                ->once()
                ->with($proof)
                ->andReturn(false);

            $result = $this->service->verifyProofOfInnocence($proof, $sanctionsRoot);

            expect($result->valid)->toBeFalse()
                ->and($result->reason)->toBe('Invalid ZK proof');
        });

        it('returns invalid result when proof has expired', function () {
            $sanctionsRoot = '0x' . str_repeat('a', 64);

            $proof = createTestProof(
                ['sanctions_list_root' => $sanctionsRoot],
                new DateTimeImmutable('-1 day'), // Already expired
            );

            $this->prover
                ->shouldReceive('verifyProof')
                ->once()
                ->andReturn(true);

            $result = $this->service->verifyProofOfInnocence($proof, $sanctionsRoot);

            expect($result->valid)->toBeFalse()
                ->and($result->reason)->toBe('Proof has expired');
        });

        it('checks sanctions root before verifying the ZK proof', function () {
            // When sanctions root does not match, verifyProof should not be called
            $proof = createTestProof([
                'sanctions_list_root' => '0x' . str_repeat('a', 64),
            ]);

            $this->prover->shouldNotReceive('verifyProof');

            $this->service->verifyProofOfInnocence($proof, '0x' . str_repeat('b', 64));
        });

        it('checks expiration after ZK proof verification', function () {
            $sanctionsRoot = '0x' . str_repeat('a', 64);

            // Valid ZK proof but expired
            $proof = createTestProof(
                ['sanctions_list_root' => $sanctionsRoot],
                new DateTimeImmutable('-10 days'),
            );

            $this->prover
                ->shouldReceive('verifyProof')
                ->once()
                ->andReturn(true);

            $result = $this->service->verifyProofOfInnocence($proof, $sanctionsRoot);

            // Should report expired even though ZK proof was valid
            expect($result->valid)->toBeFalse()
                ->and($result->reason)->toBe('Proof has expired');
        });
    });

    describe('isProofRenewalNeeded', function () {
        it('returns true when sanctions list root has changed', function () {
            $proof = createTestProof([
                'sanctions_list_root' => '0x' . str_repeat('a', 64),
            ], new DateTimeImmutable('+60 days'));

            $newRoot = '0x' . str_repeat('b', 64);

            $result = $this->service->isProofRenewalNeeded($proof, $newRoot);

            expect($result)->toBeTrue();
        });

        it('returns true when proof is expiring within threshold', function () {
            $proof = createTestProof(
                ['sanctions_list_root' => '0x' . str_repeat('a', 64)],
                new DateTimeImmutable('+15 days'), // Less than 30-day default threshold
            );

            $result = $this->service->isProofRenewalNeeded(
                $proof,
                '0x' . str_repeat('a', 64),
            );

            expect($result)->toBeTrue();
        });

        it('returns false when proof is valid and not expiring soon', function () {
            $sanctionsRoot = '0x' . str_repeat('a', 64);

            $proof = createTestProof(
                ['sanctions_list_root' => $sanctionsRoot],
                new DateTimeImmutable('+60 days'), // Well above 30-day threshold
            );

            $result = $this->service->isProofRenewalNeeded($proof, $sanctionsRoot);

            expect($result)->toBeFalse();
        });

        it('respects custom renewal threshold', function () {
            $sanctionsRoot = '0x' . str_repeat('a', 64);

            $proof = createTestProof(
                ['sanctions_list_root' => $sanctionsRoot],
                new DateTimeImmutable('+15 days'),
            );

            // With default threshold (30 days), renewal would be needed
            $resultDefaultThreshold = $this->service->isProofRenewalNeeded($proof, $sanctionsRoot);

            // With lower threshold (10 days), renewal is not needed
            $resultLowThreshold = $this->service->isProofRenewalNeeded($proof, $sanctionsRoot, 10);

            expect($resultDefaultThreshold)->toBeTrue()
                ->and($resultLowThreshold)->toBeFalse();
        });

        it('returns true when proof is already expired', function () {
            $sanctionsRoot = '0x' . str_repeat('a', 64);

            $proof = createTestProof(
                ['sanctions_list_root' => $sanctionsRoot],
                new DateTimeImmutable('-5 days'), // Already expired
            );

            $result = $this->service->isProofRenewalNeeded($proof, $sanctionsRoot);

            expect($result)->toBeTrue();
        });
    });
});

/**
 * Helper: Create a test ZkProof with configurable public inputs and expiration.
 *
 * @param  array<string, mixed>  $publicInputs
 */
function createTestProof(array $publicInputs = [], ?DateTimeImmutable $expiresAt = null): ZkProof
{
    return new ZkProof(
        type: ProofType::SANCTIONS_CLEAR,
        proof: base64_encode((string) json_encode([
            'statement'  => 'sanctions_clearance_test',
            'commitment' => 'test_commitment',
            'challenge'  => 'test_challenge',
            'response'   => 'test_response',
        ])),
        publicInputs: $publicInputs,
        verifierAddress: '0x' . str_repeat('0', 40),
        createdAt: new DateTimeImmutable(),
        expiresAt: $expiresAt ?? new DateTimeImmutable('+90 days'),
    );
}
