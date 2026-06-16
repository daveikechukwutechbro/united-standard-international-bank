<?php

declare(strict_types=1);

use App\Domain\Microfinance\Enums\ProvisionCategory;
use App\Domain\Microfinance\Models\LoanProvision;
use App\Domain\Microfinance\Services\LoanProvisioningService;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// Structural: methods exist with correct signatures
// ---------------------------------------------------------------------------

it('LoanProvisioningService has classifyLoan method', function (): void {
    $service = new LoanProvisioningService();
    expect((new ReflectionClass($service))->hasMethod('classifyLoan'))->toBeTrue();
});

it('LoanProvisioningService has reclassifyAll method', function (): void {
    $service = new LoanProvisioningService();
    expect((new ReflectionClass($service))->hasMethod('reclassifyAll'))->toBeTrue();
});

it('LoanProvisioningService has writeOff method', function (): void {
    $service = new LoanProvisioningService();
    expect((new ReflectionClass($service))->hasMethod('writeOff'))->toBeTrue();
});

it('LoanProvisioningService has getProvisionsByCategory method', function (): void {
    $service = new LoanProvisioningService();
    expect((new ReflectionClass($service))->hasMethod('getProvisionsByCategory'))->toBeTrue();
});

it('LoanProvisioningService has getTotalProvisions method', function (): void {
    $service = new LoanProvisioningService();
    expect((new ReflectionClass($service))->hasMethod('getTotalProvisions'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Behavioral: classifyLoan categories match config thresholds
// ---------------------------------------------------------------------------

it('classifies loan as STANDARD when days_overdue < standard threshold', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();

    $provision = $service->classifyLoan($loanId, 10, '1000.00');

    expect($provision)->toBeInstanceOf(LoanProvision::class);
    expect($provision->category)->toBe(ProvisionCategory::STANDARD);
});

it('classifies loan as STANDARD at exactly the standard threshold', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();
    $threshold = (int) config('microfinance.provisioning.standard_days', 30);

    $provision = $service->classifyLoan($loanId, $threshold, '1000.00');

    expect($provision->category)->toBe(ProvisionCategory::STANDARD);
});

it('classifies loan as SUBSTANDARD at the substandard threshold', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();
    $threshold = (int) config('microfinance.provisioning.substandard_days', 90);

    $provision = $service->classifyLoan($loanId, $threshold, '1000.00');

    expect($provision->category)->toBe(ProvisionCategory::SUBSTANDARD);
});

it('classifies loan as DOUBTFUL at the doubtful threshold', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();
    $threshold = (int) config('microfinance.provisioning.doubtful_days', 180);

    $provision = $service->classifyLoan($loanId, $threshold, '1000.00');

    expect($provision->category)->toBe(ProvisionCategory::DOUBTFUL);
});

it('classifies loan as LOSS at the loss threshold', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();
    $threshold = (int) config('microfinance.provisioning.loss_days', 365);

    $provision = $service->classifyLoan($loanId, $threshold, '1000.00');

    expect($provision->category)->toBe(ProvisionCategory::LOSS);
});

it('classifies loan as LOSS beyond the loss threshold', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();

    $provision = $service->classifyLoan($loanId, 400, '5000.00');

    expect($provision->category)->toBe(ProvisionCategory::LOSS);
});

it('calculates provision_amount correctly for STANDARD category', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();

    // Standard rate is 1% => provision = 1000 * 0.01 = 10.00
    $provision = $service->classifyLoan($loanId, 10, '1000.00');

    expect((float) $provision->provision_amount)->toBe(10.00);
});

it('calculates provision_amount correctly for LOSS category', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();

    // Loss rate is 100% => provision = 2000 * 1.00 = 2000.00
    $provision = $service->classifyLoan($loanId, 400, '2000.00');

    expect((float) $provision->provision_amount)->toBe(2000.00);
});

it('updates existing provision on reclassification', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();

    $first = $service->classifyLoan($loanId, 10, '1000.00');
    $second = $service->classifyLoan($loanId, 400, '1000.00');

    expect($first->id)->toBe($second->id);
    expect($second->category)->toBe(ProvisionCategory::LOSS);
});

it('writes off a provision with LOSS category and records reason', function (): void {
    $service = new LoanProvisioningService();
    $loanId = (string) Str::uuid();
    $provision = $service->classifyLoan($loanId, 10, '1000.00');

    $writtenOff = $service->writeOff($provision->id, 'Borrower absconded');

    expect($writtenOff->category)->toBe(ProvisionCategory::LOSS);
    expect($writtenOff->notes)->toBe('Borrower absconded');
});

it('getTotalProvisions returns array with all category keys and total', function (): void {
    $service = new LoanProvisioningService();
    $totals = $service->getTotalProvisions();

    expect($totals)->toHaveKeys(['standard', 'substandard', 'doubtful', 'loss', 'total']);
});

it('getTotalProvisions sums amounts correctly', function (): void {
    $service = new LoanProvisioningService();

    // Standard: 1000 * 1% = 10.00
    $service->classifyLoan((string) Str::uuid(), 10, '1000.00');
    // Loss: 2000 * 100% = 2000.00
    $service->classifyLoan((string) Str::uuid(), 400, '2000.00');

    $totals = $service->getTotalProvisions();

    expect((float) $totals['standard'])->toBe(10.00);
    expect((float) $totals['loss'])->toBe(2000.00);
    expect((float) $totals['total'])->toBe(2010.00);
});
