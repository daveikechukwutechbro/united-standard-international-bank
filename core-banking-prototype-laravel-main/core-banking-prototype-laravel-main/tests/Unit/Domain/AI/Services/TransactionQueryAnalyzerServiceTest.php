<?php

declare(strict_types=1);

use App\Domain\AI\Services\NaturalLanguageProcessorService;
use App\Domain\AI\Services\TransactionQueryAnalyzerService;

describe('TransactionQueryAnalyzerService', function (): void {
    beforeEach(function (): void {
        $this->service = new TransactionQueryAnalyzerService();
    });

    describe('buildFiltersFromEntities', function (): void {
        it('builds date range filters from entities', function (): void {
            $entities = [
                ['type' => NaturalLanguageProcessorService::ENTITY_DATE_RANGE, 'value' => [
                    'start' => '2026-01-01',
                    'end'   => '2026-01-31',
                ]],
            ];

            $filters = $this->service->buildFiltersFromEntities($entities);

            expect($filters)->toHaveKey('date_from')
                ->and($filters)->toHaveKey('date_to')
                ->and($filters['date_from'])->toContain('2026-01-01');
        });

        it('builds amount filters from entities', function (): void {
            $entities = [
                ['type' => NaturalLanguageProcessorService::ENTITY_AMOUNT, 'value' => 100],
            ];

            $filters = $this->service->buildFiltersFromEntities($entities);

            expect($filters)->toHaveKey('amount_min')
                ->and($filters['amount_min'])->toBe(100.0);
        });

        it('builds currency filter from entities', function (): void {
            $entities = [
                ['type' => NaturalLanguageProcessorService::ENTITY_CURRENCY, 'value' => 'eur'],
            ];

            $filters = $this->service->buildFiltersFromEntities($entities);

            expect($filters)->toHaveKey('asset_code')
                ->and($filters['asset_code'])->toBe('EUR');
        });

        it('builds category filter from entities', function (): void {
            $entities = [
                ['type' => NaturalLanguageProcessorService::ENTITY_CATEGORY, 'value' => 'groceries'],
            ];

            $filters = $this->service->buildFiltersFromEntities($entities);

            expect($filters)->toHaveKey('category')
                ->and($filters['category'])->toBe('groceries');
        });

        it('sets default date range when no dates provided', function (): void {
            $filters = $this->service->buildFiltersFromEntities([]);

            expect($filters)->toHaveKey('date_from')
                ->and($filters)->toHaveKey('date_to');
        });
    });

    describe('executeQuery', function (): void {
        it('returns transactions and summary', function (): void {
            $filters = [
                'date_from' => '2026-01-01T00:00:00+00:00',
                'date_to'   => '2026-01-31T23:59:59+00:00',
            ];

            $result = $this->service->executeQuery($filters);

            expect($result)->toHaveKey('transactions')
                ->and($result)->toHaveKey('summary')
                ->and($result)->toHaveKey('total_count')
                ->and($result['transactions'])->toBeArray()
                ->and($result['total_count'])->toBeGreaterThan(0);
        });

        it('returns transactions with correct structure', function (): void {
            $result = $this->service->executeQuery([]);
            $tx = $result['transactions'][0];

            expect($tx)->toHaveKey('id')
                ->and($tx)->toHaveKey('date')
                ->and($tx)->toHaveKey('amount')
                ->and($tx)->toHaveKey('asset')
                ->and($tx)->toHaveKey('category')
                ->and($tx)->toHaveKey('merchant')
                ->and($tx)->toHaveKey('type')
                ->and($tx)->toHaveKey('status');
        });

        it('returns summary with statistics', function (): void {
            $result = $this->service->executeQuery([]);
            $summary = $result['summary'];

            expect($summary)->toHaveKey('total_inflow')
                ->and($summary)->toHaveKey('total_outflow')
                ->and($summary)->toHaveKey('net_change')
                ->and($summary)->toHaveKey('transaction_count')
                ->and($summary)->toHaveKey('average_transaction');
        });

        it('respects category filter', function (): void {
            $result = $this->service->executeQuery(['category' => 'groceries']);

            foreach ($result['transactions'] as $tx) {
                expect($tx['category'])->toBe('groceries');
            }
        });

        it('respects asset code filter', function (): void {
            $result = $this->service->executeQuery(['asset_code' => 'EUR']);

            foreach ($result['transactions'] as $tx) {
                expect($tx['asset'])->toBe('EUR');
            }
        });
    });

    describe('analyzeSpending', function (): void {
        it('returns spending analysis with categories', function (): void {
            $analysis = $this->service->analyzeSpending([]);

            expect($analysis)->toHaveKey('period')
                ->and($analysis)->toHaveKey('total_spent')
                ->and($analysis)->toHaveKey('by_category')
                ->and($analysis)->toHaveKey('top_merchants')
                ->and($analysis)->toHaveKey('trends')
                ->and($analysis)->toHaveKey('insights');
        });

        it('returns trend direction', function (): void {
            $analysis = $this->service->analyzeSpending([]);
            $trends = $analysis['trends'];

            expect($trends)->toHaveKey('direction')
                ->and($trends['direction'])->toBeIn(['increasing', 'decreasing', 'stable']);
        });

        it('returns actionable insights', function (): void {
            $analysis = $this->service->analyzeSpending([]);

            expect($analysis['insights'])->toBeArray()
                ->and($analysis['insights'])->not->toBeEmpty();
        });

        it('returns top merchants', function (): void {
            $analysis = $this->service->analyzeSpending([]);

            expect($analysis['top_merchants'])->toBeArray();
            if (! empty($analysis['top_merchants'])) {
                $merchant = $analysis['top_merchants'][0];
                expect($merchant)->toHaveKey('merchant')
                    ->and($merchant)->toHaveKey('total')
                    ->and($merchant)->toHaveKey('count');
            }
        });
    });

    describe('generateNaturalLanguageSummary', function (): void {
        it('generates summary for results with transactions', function (): void {
            $queryResult = [
                'total_count' => 5,
                'summary'     => [
                    'total_inflow'        => 500.00,
                    'total_outflow'       => 300.00,
                    'average_transaction' => 160.00,
                    'period'              => 'Jan 1 to Jan 31',
                ],
            ];

            $summary = $this->service->generateNaturalLanguageSummary($queryResult, 'Show transactions');

            expect($summary)->toContain('Found 5 transactions')
                ->and($summary)->toContain('Total spent');
        });

        it('generates message for zero results', function (): void {
            $queryResult = ['total_count' => 0, 'summary' => []];

            $summary = $this->service->generateNaturalLanguageSummary($queryResult, 'Show transactions');

            expect($summary)->toContain('No transactions found');
        });
    });
});
