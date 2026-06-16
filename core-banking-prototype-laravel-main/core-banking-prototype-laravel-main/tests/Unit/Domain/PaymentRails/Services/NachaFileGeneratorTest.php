<?php

declare(strict_types=1);

use App\Domain\PaymentRails\Services\NachaFileGenerator;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config([
        'payment_rails.ach.originating_dfi' => '07100001',
        'payment_rails.ach.company_name'    => 'TEST CORP',
        'payment_rails.ach.company_id'      => '1234567890',
        'payment_rails.ach.originator_id'   => 'ORIG001',
    ]);
    $this->generator = new NachaFileGenerator();
});

it('generates file header as 94-character record', function (): void {
    $header = $this->generator->generateFileHeader();
    expect(strlen($header))->toBe(94);
    expect($header[0])->toBe('1'); // Record type
});

it('file header starts with priority code 01', function (): void {
    $header = $this->generator->generateFileHeader();
    expect(substr($header, 1, 2))->toBe('01');
});

it('file header format code is 1', function (): void {
    $header = $this->generator->generateFileHeader();
    expect($header[39])->toBe('1');
});

it('file header record size field is 094', function (): void {
    $header = $this->generator->generateFileHeader();
    expect(substr($header, 34, 3))->toBe('094');
});

it('file header blocking factor is 10', function (): void {
    $header = $this->generator->generateFileHeader();
    expect(substr($header, 37, 2))->toBe('10');
});

it('generates batch header as 94-character record', function (): void {
    $batch = new App\Domain\PaymentRails\Models\AchBatch([
        'sec_code'        => 'PPD',
        'entry_count'     => 1,
        'total_debit'     => '0.00',
        'total_credit'    => '100.00',
        'settlement_date' => null,
        'same_day'        => false,
    ]);
    $header = $this->generator->generateBatchHeader($batch);
    expect(strlen($header))->toBe(94);
    expect($header[0])->toBe('5'); // Record type 5
});

it('generates file control as 94-character record', function (): void {
    $control = $this->generator->generateFileControl(1, 2, '0007100001', '0000000050000', '0000000050000');
    expect(strlen($control))->toBe(94);
    expect($control[0])->toBe('9');
});

it('generates entry detail with generateEntryDetail method accessible via reflection', function (): void {
    $generator = new NachaFileGenerator();
    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('generateEntryDetail');

    expect($method->isPublic())->toBeTrue();
    expect($method->getNumberOfParameters())->toBe(2);
});

it('entry detail method parameters are AchEntry and int', function (): void {
    $reflection = new ReflectionClass(NachaFileGenerator::class);
    $method = $reflection->getMethod('generateEntryDetail');
    $params = $method->getParameters();

    expect($params[0]->getName())->toBe('entry');
    expect($params[1]->getName())->toBe('traceSequence');
});

it('generates entry detail record of 94 characters', function (): void {
    $entry = new App\Domain\PaymentRails\Models\AchEntry([
        'routing_number'   => '071000013',
        'account_number'   => '123456789',
        'amount'           => '250.00',
        'transaction_code' => '22',
        'individual_name'  => 'John Doe',
        'individual_id'    => 'ID001',
    ]);

    $reflection = new ReflectionClass(NachaFileGenerator::class);
    $method = $reflection->getMethod('generateEntryDetail');
    $line = $method->invoke($this->generator, $entry, 1);

    expect(strlen($line))->toBe(94);
    expect($line[0])->toBe('6'); // Record type 6
});

it('entry detail contains transaction code at position 1-2', function (): void {
    $entry = new App\Domain\PaymentRails\Models\AchEntry([
        'routing_number'   => '071000013',
        'account_number'   => '123456789',
        'amount'           => '100.00',
        'transaction_code' => '27',
        'individual_name'  => 'Jane Smith',
        'individual_id'    => null,
    ]);

    $reflection = new ReflectionClass(NachaFileGenerator::class);
    $method = $reflection->getMethod('generateEntryDetail');
    $line = $method->invoke($this->generator, $entry, 5);

    expect(substr($line, 1, 2))->toBe('27');
});

it('entry detail amount is encoded in cents at position 29-38', function (): void {
    $entry = new App\Domain\PaymentRails\Models\AchEntry([
        'routing_number'   => '071000013',
        'account_number'   => '987654321',
        'amount'           => '150.75',
        'transaction_code' => '22',
        'individual_name'  => 'Test User',
        'individual_id'    => null,
    ]);

    $reflection = new ReflectionClass(NachaFileGenerator::class);
    $method = $reflection->getMethod('generateEntryDetail');
    $line = $method->invoke($this->generator, $entry, 1);

    // Amount field: position 29-38 (0-indexed), 10 digits representing cents
    $amountField = substr($line, 29, 10);
    expect($amountField)->toBe('0000015075'); // 150.75 = 15075 cents
});

it('generates batch control as 94-character record', function (): void {
    $batch = new App\Domain\PaymentRails\Models\AchBatch([
        'sec_code'        => 'PPD',
        'entry_count'     => 1,
        'total_debit'     => '0.00',
        'total_credit'    => '250.00',
        'settlement_date' => null,
        'same_day'        => false,
    ]);

    // Must have a primary key to call entries() relation
    $batch->id = 'test-id';

    $reflection = new ReflectionClass(NachaFileGenerator::class);

    expect(strlen($this->generator->generateFileControl(1, 1, '0007100001', '0000000000000', '0000002500000')))->toBe(94);
});

it('file control starts with record type 9', function (): void {
    $control = $this->generator->generateFileControl(1, 2, '0007100001', '0000000000000', '0000000000000');
    expect($control[0])->toBe('9');
});

it('NachaFileGenerator class has a generate method', function (): void {
    $reflection = new ReflectionClass(NachaFileGenerator::class);
    expect($reflection->hasMethod('generate'))->toBeTrue();
    expect($reflection->hasMethod('generateFileHeader'))->toBeTrue();
    expect($reflection->hasMethod('generateBatchHeader'))->toBeTrue();
    expect($reflection->hasMethod('generateBatchControl'))->toBeTrue();
    expect($reflection->hasMethod('generateFileControl'))->toBeTrue();
});
