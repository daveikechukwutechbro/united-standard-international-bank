<?php

declare(strict_types=1);

namespace App\Domain\AI\MCP\Tools\Transaction;

use App\Domain\AI\Contracts\MCPToolInterface;
use App\Domain\AI\Services\NaturalLanguageProcessorService;
use App\Domain\AI\Services\TransactionQueryAnalyzerService;
use App\Domain\AI\ValueObjects\ToolExecutionResult;
use Exception;

/**
 * MCP Tool for analyzing spending patterns across categories, merchants, and time periods.
 */
class SpendingAnalysisTool implements MCPToolInterface
{
    public function __construct(
        private readonly NaturalLanguageProcessorService $nlpService,
        private readonly TransactionQueryAnalyzerService $analyzerService
    ) {
    }

    public function getName(): string
    {
        return 'transactions.spending_analysis';
    }

    public function getCategory(): string
    {
        return 'transaction';
    }

    public function getDescription(): string
    {
        return 'Analyze spending patterns by category, merchant, and time period. '
            . 'Returns breakdowns, trends, and actionable insights.';
    }

    /** @return array<string, mixed> */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'query' => [
                    'type'        => 'string',
                    'description' => 'Natural language query (e.g., "What did I spend on groceries last month?")',
                ],
                'account_uuid' => [
                    'type'        => 'string',
                    'description' => 'Optional account UUID',
                    'pattern'     => '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$',
                ],
                'date_from' => [
                    'type'        => 'string',
                    'description' => 'Start date (ISO 8601)',
                ],
                'date_to' => [
                    'type'        => 'string',
                    'description' => 'End date (ISO 8601)',
                ],
                'category' => [
                    'type'        => 'string',
                    'description' => 'Focus on a specific spending category',
                ],
                'asset_code' => [
                    'type'        => 'string',
                    'description' => 'Asset/currency code',
                    'pattern'     => '^[A-Z]{3,10}$',
                ],
            ],
            'required' => [],
        ];
    }

    /** @return array<string, mixed> */
    public function getOutputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'period' => [
                    'type'       => 'object',
                    'properties' => [
                        'from' => ['type' => 'string'],
                        'to'   => ['type' => 'string'],
                    ],
                ],
                'total_spent'  => ['type' => 'number'],
                'total_earned' => ['type' => 'number'],
                'by_category'  => [
                    'type'                 => 'object',
                    'additionalProperties' => [
                        'type'       => 'object',
                        'properties' => [
                            'total' => ['type' => 'number'],
                            'count' => ['type' => 'integer'],
                        ],
                    ],
                ],
                'top_merchants' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'merchant' => ['type' => 'string'],
                            'total'    => ['type' => 'number'],
                            'count'    => ['type' => 'integer'],
                        ],
                    ],
                ],
                'trends' => [
                    'type'       => 'object',
                    'properties' => [
                        'direction'      => ['type' => 'string'],
                        'change_percent' => ['type' => 'number'],
                    ],
                ],
                'insights' => [
                    'type'  => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $parameters */
    public function execute(array $parameters, ?string $conversationId = null): ToolExecutionResult
    {
        try {
            $startTime = microtime(true);

            // Tool execution is logged by the MCP server layer

            $filters = [];

            // Parse natural language query if provided
            if (! empty($parameters['query'])) {
                $parsed = $this->nlpService->processQuery($parameters['query'], []);
                $filters = $this->analyzerService->buildFiltersFromEntities($parsed['entities'] ?? []);
            }

            // Merge explicit filters
            foreach (['date_from', 'date_to', 'category', 'asset_code'] as $key) {
                if (isset($parameters[$key])) {
                    $filters[$key] = $parameters[$key];
                }
            }

            $accountUuid = $parameters['account_uuid'] ?? null;
            $analysis = $this->analyzerService->analyzeSpending($filters, $accountUuid);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            return ToolExecutionResult::success($analysis, $durationMs);
        } catch (Exception $e) {
            // Error is propagated via ToolExecutionResult::failure

            return ToolExecutionResult::failure($e->getMessage());
        }
    }

    /** @return array<int, string> */
    public function getCapabilities(): array
    {
        return [
            'read',
            'natural-language',
            'category-analysis',
            'trend-analysis',
            'merchant-analysis',
            'insights',
        ];
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function getCacheTtl(): int
    {
        return 120; // Cache for 2 minutes
    }

    /** @param array<string, mixed> $parameters */
    public function validateInput(array $parameters): bool
    {
        if (empty($parameters)) {
            return false;
        }

        if (isset($parameters['account_uuid'])) {
            if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $parameters['account_uuid'])) {
                return false;
            }
        }

        if (isset($parameters['asset_code'])) {
            if (! preg_match('/^[A-Z]{3,10}$/', $parameters['asset_code'])) {
                return false;
            }
        }

        return true;
    }

    public function authorize(?string $userId): bool
    {
        return $userId !== null;
    }
}
