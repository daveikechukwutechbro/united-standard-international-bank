<?php

declare(strict_types=1);

use App\Domain\AI\MCP\Tools\Transaction\SpendingAnalysisTool;
use App\Domain\AI\Services\NaturalLanguageProcessorService;
use App\Domain\AI\Services\TransactionQueryAnalyzerService;
use App\Domain\AI\ValueObjects\ToolExecutionResult;

describe('SpendingAnalysisTool', function (): void {
    beforeEach(function (): void {
        $this->nlpService = Mockery::mock(NaturalLanguageProcessorService::class);
        $this->analyzerService = new TransactionQueryAnalyzerService();
        $this->tool = new SpendingAnalysisTool($this->nlpService, $this->analyzerService);
    });

    afterEach(function (): void {
        Mockery::close();
    });

    describe('metadata', function (): void {
        it('has correct name', function (): void {
            expect($this->tool->getName())->toBe('transactions.spending_analysis');
        });

        it('has correct category', function (): void {
            expect($this->tool->getCategory())->toBe('transaction');
        });

        it('has insights capability', function (): void {
            expect($this->tool->getCapabilities())->toContain('insights')
                ->and($this->tool->getCapabilities())->toContain('category-analysis');
        });

        it('is cacheable with 2 min TTL', function (): void {
            expect($this->tool->isCacheable())->toBeTrue()
                ->and($this->tool->getCacheTtl())->toBe(120);
        });
    });

    describe('execute', function (): void {
        it('returns spending analysis with categories', function (): void {
            $result = $this->tool->execute(['date_from' => '2026-01-01']);

            expect($result)->toBeInstanceOf(ToolExecutionResult::class)
                ->and($result->isSuccess())->toBeTrue()
                ->and($result->getData())->toHaveKey('by_category')
                ->and($result->getData())->toHaveKey('top_merchants')
                ->and($result->getData())->toHaveKey('trends')
                ->and($result->getData())->toHaveKey('insights');
        });

        it('returns trend analysis', function (): void {
            $result = $this->tool->execute(['date_from' => '2026-01-01']);
            $trends = $result->getData()['trends'];

            expect($trends)->toHaveKey('direction')
                ->and($trends)->toHaveKey('change_percent')
                ->and($trends['direction'])->toBeIn(['increasing', 'decreasing', 'stable']);
        });

        it('executes with natural language query', function (): void {
            $this->nlpService->shouldReceive('processQuery')
                ->once()
                ->andReturn([
                    'intent'   => 'transaction_query',
                    'entities' => [
                        ['type' => 'category', 'value' => 'groceries'],
                    ],
                    'confidence' => 0.9,
                ]);

            $result = $this->tool->execute(['query' => 'What did I spend on groceries?']);

            expect($result->isSuccess())->toBeTrue()
                ->and($result->getData())->toHaveKey('total_spent');
        });

        it('returns period information', function (): void {
            $result = $this->tool->execute(['date_from' => '2026-01-01', 'date_to' => '2026-01-31']);
            $period = $result->getData()['period'];

            expect($period)->toHaveKey('from')
                ->and($period)->toHaveKey('to');
        });
    });

    describe('validateInput', function (): void {
        it('rejects empty parameters', function (): void {
            expect($this->tool->validateInput([]))->toBeFalse();
        });

        it('accepts query parameter', function (): void {
            expect($this->tool->validateInput(['query' => 'groceries']))->toBeTrue();
        });

        it('accepts date range parameters', function (): void {
            expect($this->tool->validateInput(['date_from' => '2026-01-01']))->toBeTrue();
        });
    });

    describe('authorize', function (): void {
        it('requires user id', function (): void {
            expect($this->tool->authorize(null))->toBeFalse()
                ->and($this->tool->authorize('user-123'))->toBeTrue();
        });
    });
});
