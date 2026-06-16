<?php

declare(strict_types=1);

use App\Domain\Privacy\Services\TrustedSetupService;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    $this->testDir = sys_get_temp_dir() . '/test_circuits_' . uniqid();
    @mkdir($this->testDir, 0755, true);

    config(['privacy.zk.circuit_directory' => $this->testDir]);
    config(['privacy.zk.snarkjs_binary' => 'snarkjs']);
    config(['privacy.zk.snarkjs_timeout_seconds' => 30]);
    config(['privacy.zk.ceremony.ptau_power' => 10]);
    config(['privacy.zk.ceremony.ptau_url' => 'https://hermez.s3-eu-west-1.amazonaws.com/powersOfTau28_hez_final_10.ptau']);
});

afterEach(function (): void {
    // Clean up test directory
    if (is_dir($this->testDir)) {
        $files = glob($this->testDir . '/*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $verifierDir = $this->testDir . '/verifiers';
        if (is_dir($verifierDir)) {
            $vFiles = glob($verifierDir . '/*') ?: [];
            foreach ($vFiles as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($verifierDir);
        }
        @rmdir($this->testDir);
    }
});

describe('TrustedSetupService', function (): void {
    describe('getCircuitArtifacts', function (): void {
        it('returns all expected artifact paths', function (): void {
            $service = new TrustedSetupService();
            $artifacts = $service->getCircuitArtifacts('age_check');

            expect($artifacts)->toHaveKeys(['circom', 'r1cs', 'wasm', 'zkey', 'vkey', 'sol'])
                ->and($artifacts['circom'])->toEndWith('age_check.circom')
                ->and($artifacts['r1cs'])->toEndWith('age_check.r1cs')
                ->and($artifacts['wasm'])->toEndWith('age_check.wasm')
                ->and($artifacts['zkey'])->toEndWith('age_check.zkey')
                ->and($artifacts['vkey'])->toEndWith('age_check.vkey.json')
                ->and($artifacts['sol'])->toEndWith('age_check_verifier.sol');
        });

        it('uses configured circuit directory', function (): void {
            $service = new TrustedSetupService();
            $artifacts = $service->getCircuitArtifacts('test_circuit');

            expect($artifacts['circom'])->toStartWith($this->testDir);
        });
    });

    describe('artifactsExist', function (): void {
        it('returns false when no artifacts exist', function (): void {
            $service = new TrustedSetupService();

            expect($service->artifactsExist('nonexistent_circuit'))->toBeFalse();
        });

        it('returns false when only partial artifacts exist', function (): void {
            // Create only zkey but not wasm or vkey
            file_put_contents($this->testDir . '/partial_circuit.zkey', 'fake_zkey');

            $service = new TrustedSetupService();

            expect($service->artifactsExist('partial_circuit'))->toBeFalse();
        });

        it('returns true when all required artifacts exist', function (): void {
            file_put_contents($this->testDir . '/complete_circuit.zkey', 'fake_zkey');
            file_put_contents($this->testDir . '/complete_circuit.vkey.json', '{}');
            file_put_contents($this->testDir . '/complete_circuit.wasm', 'fake_wasm');

            $service = new TrustedSetupService();

            expect($service->artifactsExist('complete_circuit'))->toBeTrue();
        });
    });

    describe('getPtauPath', function (): void {
        it('returns path based on configured power', function (): void {
            $service = new TrustedSetupService();
            $ptauPath = $service->getPtauPath();

            expect($ptauPath)->toContain('powersOfTau_10.ptau')
                ->and($ptauPath)->toStartWith($this->testDir);
        });
    });

    describe('getCircuitDirectory', function (): void {
        it('returns configured circuit directory', function (): void {
            $service = new TrustedSetupService();

            expect($service->getCircuitDirectory())->toBe($this->testDir);
        });
    });

    describe('downloadPowersOfTau', function (): void {
        it('returns existing ptau file without re-downloading', function (): void {
            $ptauPath = $this->testDir . '/powersOfTau_10.ptau';
            file_put_contents($ptauPath, 'existing_ptau_data');

            $service = new TrustedSetupService();
            $result = $service->downloadPowersOfTau(10);

            expect($result)->toBe($ptauPath)
                ->and(file_get_contents($result))->toBe('existing_ptau_data');
        });

        it('creates placeholder ptau when snarkjs is unavailable', function (): void {
            config(['privacy.zk.snarkjs_binary' => '/nonexistent/snarkjs']);
            $service = new TrustedSetupService();

            $result = $service->downloadPowersOfTau(10);

            expect($result)->toEndWith('powersOfTau_10.ptau')
                ->and(file_exists($result))->toBeTrue();

            $contents = json_decode((string) file_get_contents($result), true);
            expect($contents)->toHaveKey('type')
                ->and($contents['type'])->toBe('ptau_stub');
        });
    });

    describe('setupCircuit', function (): void {
        it('throws RuntimeException when r1cs file is missing', function (): void {
            $service = new TrustedSetupService();

            $service->setupCircuit('missing_circuit');
        })->throws(RuntimeException::class, 'R1CS file not found');

        it('throws RuntimeException when ptau file is missing', function (): void {
            // Create r1cs but no ptau
            file_put_contents($this->testDir . '/test_circuit.r1cs', 'fake_r1cs');

            $service = new TrustedSetupService();

            $service->setupCircuit('test_circuit');
        })->throws(RuntimeException::class, 'Powers of Tau file not found');
    });

    describe('exportVerificationKey', function (): void {
        it('throws RuntimeException when zkey file is missing', function (): void {
            $service = new TrustedSetupService();

            $service->exportVerificationKey('nonexistent');
        })->throws(RuntimeException::class, 'Zkey file not found');
    });

    describe('exportSolidityVerifier', function (): void {
        it('throws RuntimeException when zkey file is missing', function (): void {
            $service = new TrustedSetupService();

            $service->exportSolidityVerifier('nonexistent');
        })->throws(RuntimeException::class, 'Zkey file not found');
    });
});
