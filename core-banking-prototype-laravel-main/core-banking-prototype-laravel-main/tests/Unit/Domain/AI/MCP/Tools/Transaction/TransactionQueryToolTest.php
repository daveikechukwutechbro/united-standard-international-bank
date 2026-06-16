<?php

declare(strict_types=1);

use App\Domain\AI\MCP\Tools\Transaction\TransactionQueryTool;
use App\Domain\AI\Services\NaturalLanguageProcessorService;
use App\Domain\AI\Services\TransactionQueryAnalyzerService;
use App\Domain\AI\ValueObjects\ToolExecutionResult;

describe('TransactionQueryTool', function (): void {
    beforeEach(function (): void {
        $this->nlpService = Mockery::mock(NaturalLanguageProcessorService::class);
        $this->analyzerService = new TransactionQueryAnalyzerService();
        $this->tool = new TransactionQueryTool($this->nlpService, $this->analyzerService);
    });

    afterEach(function (): void {
        Mockery::close();
    });

    describe('metadata', function (): void {
        it('has correct name', function (): void {
            expect($this->tool->getName())->toBe('transactions.query');
        });

        it('has correct category', function (): void {
            expect($this->tool->getCategory())->toBe('transaction');
        });

        it('has description', function (): void {
            expect($this->tool->getDescription())->toBeString()
                ->and($this->tool->getDescription())->not->toBeEmpty();
        });

        it('has input schema with query and account properties', function (): void {
            $schema = $this->tool->getInputSchema();
            expect($schema['type'])->toBe('object')
                ->and($schema['properties'])->toHaveKey('query')
                ->and($schema['properties'])->toHaveKey('account_uuid')
                ->and($schema['properties'])->toHaveKey('date_from')
                ->and($schema['properties'])->toHaveKey('amount_min');
        });

        it('has output schema with transactions and summary', function (): void {
            $schema = $this->tool->getOutputSchema();
            expect($schema['properties'])->toHaveKey('transactions')
                ->and($schema['properties'])->toHaveKey('summary');
        });

        it('has natural-language capability', function (): void {
            expect($this->tool->getCapabilities())->toContain('natural-language');
        });

        it('is cacheable with 60s TTL', function (): void {
            expect($this->tool->isCacheable())->toBeTrue()
                ->and($this->tool->getCacheTtl())->toBe(60);
        });
    });

    describe('validateInput', function (): void {
        it('rejects empty parameters', function (): void {
            expect($this->tool->validateInput([]))->toBeFalse();
        });

        it('accepts valid natural language query', function (): void {
            expect($this->tool->validateInput(['query' => 'Show transactions']))->toBeTrue();
        });

        it('rejects invalid account uuid', function (): void {
            expect($this->tool->validateInput(['account_uuid' => 'invalid']))->toBeFalse();
        });

        it('accepts valid account uuid', function (): void {
            expect($this->tool->validateInput([
                'account_uuid' => '12345678-1234-1234-1234-123456789abc',
            ]))->toBeTrue();
        });

        it('rejects invalid asset code', function (): void {
            expect($this->tool->validateInput([
                'query'      => 'test',
                'asset_code' => 'invalid',
            ]))->toBeFalse();
        });
    });

    describe('execute', function (): void {
        it('executes with natural language query', function (): void {
            $this->nlpService->shouldReceive('processQuery')
                ->once()
                ->andReturn([
                    'intent'   => 'transaction_query',
                    'entities' => [
                        ['type' => 'amount', 'value' => 100],
                    ],
                    'confidence'  => 0.85,
                    'explanation' => 'Looking for transactions with amounts around $100',
                ]);

            $result = $this->tool->execute(['query' => 'Show transactions over $100']);

            expect($result)->toBeInstanceOf(ToolExecutionResult::class)
                ->and($result->isSuccess())->toBeTrue()
                ->and($result->getData())->toHaveKey('transactions')
                ->and($result->getData())->toHaveKey('summary')
                ->and($result->getData())->toHaveKey('nl_summary');
        });

        it('executes with structured filters', function (): void {
            $result = $this->tool->execute([
                'date_from'  => '2026-01-01',
                'date_to'    => '2026-01-31',
                'asset_code' => 'USD',
            ]);

            expect($result->isSuccess())->toBeTrue()
                ->and($result->getData()['transactions'])->toBeArray();
        });

        it('merges explicit filters with NL-parsed filters', function (): void {
            $this->nlpService->shouldReceive('processQuery')
                ->once()
                ->andReturn([
                    'intent'      => 'transaction_query',
                    'entities'    => [],
                    'confidence'  => 0.7,
                    'explanation' => 'General query',
                ]);

            $result = $this->tool->execute([
                'query'      => 'Show my transactions',
                'asset_code' => 'EUR',
            ]);

            expect($result->isSuccess())->toBeTrue();
            $data = $result->getData();
            expect($data['filters_used'])->toHaveKey('asset_code')
                ->and($data['filters_used']['asset_code'])->toBe('EUR');
        });
    });

    describe('authorize', function (): void {
        it('requires user id', function (): void {
            expect($this->tool->authorize(null))->toBeFalse()
                ->and($this->tool->authorize('user-123'))->toBeTrue();
        });
    });
});
