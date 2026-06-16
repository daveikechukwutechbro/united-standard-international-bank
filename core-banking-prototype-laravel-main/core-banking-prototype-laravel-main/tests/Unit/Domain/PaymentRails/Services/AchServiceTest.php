<?php

declare(strict_types=1);

use App\Domain\PaymentRails\Services\AchService;
use App\Domain\PaymentRails\Services\NachaFileGenerator;
use App\Domain\PaymentRails\Services\NachaFileParser;

uses(Tests\TestCase::class);

// ── Structural / reflection tests ────────────────────────────────────────────

it('AchService class exists', function (): void {
    expect(class_exists(AchService::class))->toBeTrue();
});

it('AchService can be instantiated with its dependencies', function (): void {
    $service = new AchService(new NachaFileGenerator(), new NachaFileParser());
    expect($service)->toBeInstanceOf(AchService::class);
});

it('AchService has originateCredit method', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    expect($reflection->hasMethod('originateCredit'))->toBeTrue();
});

it('originateCredit accepts correct parameter count', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    $method = $reflection->getMethod('originateCredit');

    // userId, routingNumber, accountNumber, amount, name, secCode (default)
    expect($method->getNumberOfParameters())->toBe(6);
    expect($method->getNumberOfRequiredParameters())->toBe(5);
});

it('AchService has originateDebit method', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    expect($reflection->hasMethod('originateDebit'))->toBeTrue();
});

it('originateDebit accepts correct parameter count', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    $method = $reflection->getMethod('originateDebit');

    expect($method->getNumberOfParameters())->toBe(6);
    expect($method->getNumberOfRequiredParameters())->toBe(5);
});

it('AchService has createBatch method', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    expect($reflection->hasMethod('createBatch'))->toBeTrue();
});

it('createBatch accepts entries array parameter', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    $method = $reflection->getMethod('createBatch');
    $params = $method->getParameters();

    $paramNames = array_map(fn (ReflectionParameter $p): string => $p->getName(), $params);
    expect($paramNames)->toContain('entries');
});

it('AchService has generateFile method', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    expect($reflection->hasMethod('generateFile'))->toBeTrue();
});

it('AchService has processReturns method', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    expect($reflection->hasMethod('processReturns'))->toBeTrue();
});

it('processReturns return type is array', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    $method = $reflection->getMethod('processReturns');
    $returnType = $method->getReturnType();

    expect($returnType)->not->toBeNull();
    assert($returnType instanceof ReflectionNamedType);
    expect($returnType->getName())->toBe('array');
});

it('AchService has getBatchStatus method', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    expect($reflection->hasMethod('getBatchStatus'))->toBeTrue();
});

it('getBatchStatus accepts a string batchId parameter', function (): void {
    $reflection = new ReflectionClass(AchService::class);
    $method = $reflection->getMethod('getBatchStatus');
    $params = $method->getParameters();

    expect($params[0]->getName())->toBe('batchId');
    assert($params[0]->getType() instanceof ReflectionNamedType);
    expect($params[0]->getType()->getName())->toBe('string');
});

// ── NachaFileParser structural tests ─────────────────────────────────────────

it('NachaFileParser class exists', function (): void {
    expect(class_exists(NachaFileParser::class))->toBeTrue();
});

it('NachaFileParser has parse method', function (): void {
    $reflection = new ReflectionClass(NachaFileParser::class);
    expect($reflection->hasMethod('parse'))->toBeTrue();
});

it('NachaFileParser has parseReturnEntries method', function (): void {
    $reflection = new ReflectionClass(NachaFileParser::class);
    expect($reflection->hasMethod('parseReturnEntries'))->toBeTrue();
});

it('NachaFileParser has extractReturnCode method', function (): void {
    $reflection = new ReflectionClass(NachaFileParser::class);
    expect($reflection->hasMethod('extractReturnCode'))->toBeTrue();
});

it('extractReturnCode returns null for invalid addenda', function (): void {
    $parser = new NachaFileParser();
    expect($parser->extractReturnCode('short'))->toBeNull();
});

it('extractReturnCode returns null when addenda type is not 99 or 98', function (): void {
    $parser = new NachaFileParser();
    $addenda = '705R01' . str_repeat(' ', 88);
    expect($parser->extractReturnCode($addenda))->toBeNull();
});

it('extractReturnCode returns R-code for type 99 addenda record', function (): void {
    $parser = new NachaFileParser();
    // Type 7, addenda type 99, return code R01
    $addenda = '799R01' . str_repeat(' ', 88);
    expect($parser->extractReturnCode($addenda))->toBe('R01');
});

it('extractReturnCode returns R-code for type 98 (NOC) addenda record', function (): void {
    $parser = new NachaFileParser();
    $addenda = '798R29' . str_repeat(' ', 88);
    expect($parser->extractReturnCode($addenda))->toBe('R29');
});

it('extractReturnCode returns null for malformed return code', function (): void {
    $parser = new NachaFileParser();
    $addenda = '799XXX' . str_repeat(' ', 88);
    expect($parser->extractReturnCode($addenda))->toBeNull();
});

it('parse returns structured array with file_header, batches, and file_control keys', function (): void {
    $parser = new NachaFileParser();
    $result = $parser->parse('');
    expect($result)->toHaveKeys(['file_header', 'batches', 'file_control']);
    expect($result['batches'])->toBeArray();
});

it('parseReturnEntries returns empty array for empty file', function (): void {
    $parser = new NachaFileParser();
    $returns = $parser->parseReturnEntries('');
    expect($returns)->toBeArray()->toBeEmpty();
});
