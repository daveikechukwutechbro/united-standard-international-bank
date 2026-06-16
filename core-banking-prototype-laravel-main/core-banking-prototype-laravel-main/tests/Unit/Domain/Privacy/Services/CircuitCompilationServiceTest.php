<?php

declare(strict_types=1);

use App\Domain\Privacy\Services\CircuitCompilationService;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    $this->testDir = sys_get_temp_dir() . '/test_circuits_compile_' . uniqid();
    @mkdir($this->testDir, 0755, true);

    config(['privacy.zk.circuit_directory' => $this->testDir]);
    config(['privacy.zk.circom_binary' => 'circom']);
    config(['privacy.zk.snarkjs_binary' => 'snarkjs']);
    config(['privacy.zk.snarkjs_timeout_seconds' => 30]);
});

afterEach(function (): void {
    if (is_dir($this->testDir)) {
        $files = glob($this->testDir . '/*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($this->testDir);
    }
});

describe('CircuitCompilationService', function (): void {
    describe('getArtifactPaths', function (): void {
        it('returns all expected paths', function (): void {
            $service = new CircuitCompilationService();
            $paths = $service->getArtifactPaths('age_check');

            expect($paths)->toHaveKeys(['r1cs_path', 'wasm_path', 'sym_path'])
                ->and($paths['r1cs_path'])->toEndWith('age_check.r1cs')
                ->and($paths['wasm_path'])->toEndWith('age_check.wasm')
                ->and($paths['sym_path'])->toEndWith('age_check.sym');
        });

        it('uses configured circuit directory', function (): void {
            $service = new CircuitCompilationService();
            $paths = $service->getArtifactPaths('test_circuit');

            expect($paths['r1cs_path'])->toStartWith($this->testDir);
        });
    });

    describe('artifactsExist', function (): void {
        it('returns false when no artifacts exist', function (): void {
            $service = new CircuitCompilationService();

            expect($service->artifactsExist('nonexistent_circuit'))->toBeFalse();
        });

        it('returns false when only r1cs exists', function (): void {
            file_put_contents($this->testDir . '/partial.r1cs', 'fake_r1cs');

            $service = new CircuitCompilationService();

            expect($service->artifactsExist('partial'))->toBeFalse();
        });

        it('returns false when only wasm exists', function (): void {
            file_put_contents($this->testDir . '/partial.wasm', 'fake_wasm');

            $service = new CircuitCompilationService();

            expect($service->artifactsExist('partial'))->toBeFalse();
        });

        it('returns true when both r1cs and wasm exist', function (): void {
            file_put_contents($this->testDir . '/complete.r1cs', 'fake_r1cs');
            file_put_contents($this->testDir . '/complete.wasm', 'fake_wasm');

            $service = new CircuitCompilationService();

            expect($service->artifactsExist('complete'))->toBeTrue();
        });
    });

    describe('compile', function (): void {
        it('throws RuntimeException when circom source file is missing', function (): void {
            $service = new CircuitCompilationService();

            $service->compile('nonexistent_circuit');
        })->throws(RuntimeException::class, 'Circom source file not found');

        it('throws RuntimeException when circom binary fails', function (): void {
            // Create a circom source file
            file_put_contents($this->testDir . '/bad_circuit.circom', 'invalid circom code');

            config(['privacy.zk.circom_binary' => '/nonexistent/circom']);
            $service = new CircuitCompilationService();

            $service->compile('bad_circuit');
        })->throws(RuntimeException::class, 'Circom compilation failed');
    });

    describe('getConstraintCount', function (): void {
        it('returns 0 when r1cs file does not exist', function (): void {
            $service = new CircuitCompilationService();

            expect($service->getConstraintCount('nonexistent'))->toBe(0);
        });

        it('returns 0 when snarkjs is unavailable', function (): void {
            file_put_contents($this->testDir . '/test.r1cs', 'fake_r1cs');
            config(['privacy.zk.snarkjs_binary' => '/nonexistent/snarkjs']);

            $service = new CircuitCompilationService();

            expect($service->getConstraintCount('test'))->toBe(0);
        });
    });

    describe('getCircuitDirectory', function (): void {
        it('returns configured circuit directory', function (): void {
            $service = new CircuitCompilationService();

            expect($service->getCircuitDirectory())->toBe($this->testDir);
        });
    });
});
