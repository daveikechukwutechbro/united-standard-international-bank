<?php

declare(strict_types=1);

// Tests for the zk:setup Artisan command.
// Validates argument handling and error paths without requiring circom/snarkjs.

beforeEach(function (): void {
    $this->testDir = sys_get_temp_dir() . '/zk_setup_cmd_' . uniqid();
    @mkdir($this->testDir, 0755, true);

    config(['privacy.zk.circuit_directory' => $this->testDir]);
    config(['privacy.zk.circom_binary' => '/nonexistent/circom']);
    config(['privacy.zk.snarkjs_binary' => '/nonexistent/snarkjs']);
    config(['privacy.zk.snarkjs_timeout_seconds' => 5]);
    config(['privacy.zk.ceremony.ptau_power' => 10]);
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

it('requires --circuit or --all flag', function (): void {
    $this->artisan('zk:setup')
        ->expectsOutputToContain('Please specify --circuit=<name> or --all')
        ->assertFailed();
});

it('lists available circuits when no flag is given', function (): void {
    $this->artisan('zk:setup')
        ->expectsOutputToContain('age_check')
        ->assertFailed();
});

it('runs with --circuit flag and fails gracefully when circom source is missing', function (): void {
    // Create a stub ptau file so downloadPowersOfTau succeeds
    file_put_contents($this->testDir . '/powersOfTau_10.ptau', 'stub_ptau_data');

    $this->artisan('zk:setup', ['--circuit' => 'age_check', '--ptau-power' => 10])
        ->expectsOutputToContain('Setting up circuit: age_check')
        ->assertFailed();
});

it('runs with --all flag and reports failures for all circuits', function (): void {
    // Create a stub ptau so the PoT step succeeds
    file_put_contents($this->testDir . '/powersOfTau_10.ptau', 'stub_ptau_data');

    $this->artisan('zk:setup', ['--all' => true, '--ptau-power' => 10])
        ->expectsOutputToContain('Setting up circuit: age_check')
        ->expectsOutputToContain('Setting up circuit: sanctions_check')
        ->assertFailed();
});

it('creates ptau placeholder when snarkjs is unavailable and proceeds to circuit setup', function (): void {
    // No ptau file exists, snarkjs binary is nonexistent, so a placeholder stub is created
    $this->artisan('zk:setup', ['--circuit' => 'age_check', '--ptau-power' => 10])
        ->expectsOutputToContain('Downloading Powers of Tau')
        ->assertFailed();

    // The service creates a JSON stub when snarkjs is unavailable
    $ptauPath = $this->testDir . '/powersOfTau_10.ptau';
    expect(file_exists($ptauPath))->toBeTrue();

    /** @var array<string, mixed> $contents */
    $contents = json_decode((string) file_get_contents($ptauPath), true);
    expect($contents['type'])->toBe('ptau_stub');
});

it('skips compilation with --skip-compile and fails when artifacts do not exist', function (): void {
    // Create a stub ptau
    file_put_contents($this->testDir . '/powersOfTau_10.ptau', 'stub_ptau_data');

    $this->artisan('zk:setup', [
        '--circuit'      => 'age_check',
        '--skip-compile' => true,
        '--ptau-power'   => 10,
    ])->expectsOutputToContain('Compiled artifacts not found')
        ->assertFailed();
});

it('reports success count and fail count in summary', function (): void {
    file_put_contents($this->testDir . '/powersOfTau_10.ptau', 'stub_ptau_data');

    $this->artisan('zk:setup', ['--all' => true, '--ptau-power' => 10])
        ->expectsOutputToContain('0 succeeded, 5 failed')
        ->assertFailed();
});
