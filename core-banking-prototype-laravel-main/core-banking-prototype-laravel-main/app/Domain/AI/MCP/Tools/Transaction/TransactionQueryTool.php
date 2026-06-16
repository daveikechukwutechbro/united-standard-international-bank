<?php

declare(strict_types=1);

namespace App\Domain\AI\MCP\Tools\Transaction;

use App\Domain\AI\Contracts\MCPToolInterface;
use App\Domain\AI\Services\NaturalLanguageProcessorService;
use App\Domain\AI\Services\TransactionQueryAnalyzerService;
use App\Domain\AI\ValueObjects\ToolExecutionResult;
use Exception;

/**
 * MCP Tool for querying transactions using natural language.
 *
 * Accepts either structured filters or a natural language query string,
 * parses intents and entities, and returns matching transactions with summaries.
 */
class TransactionQueryTool implements MCPToolInterface
{
    public function __construct(
        private readonly NaturalLanguageProcessorService $nlpService,
        private readonly TransactionQueryAnalyzerService $analyzerService
    ) {
    }

    public function getName(): string
    {
        return 'transactions.query';
    }

    public function getCategory(): string
    {
        return 'transaction';
    }

    public function getDescription(): string
    {
        return 'Query transactions using natural language or structured filters. '
            . 'Supports date ranges, amount filters, categories, and merchant search.';
    }

    /** @return array<string, mixed> */
    public function getInputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'query' => [
                    'type'        => 'string',
                    'description' => 'Natural language query (e.g., "Show transactions over $100 last week")',
                ],
                'account_uuid' => [
                    'type'        => 'string',
                    'description' => 'Optional account UUID to scope the query',
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
                'amount_min' => [
                    'type'        => 'number',
                    'description' => 'Minimum transaction amount',
                ],
                'amount_max' => [
                    'type'        => 'number',
                    'description' => 'Maximum transaction amount',
                ],
                'category' => [
                    'type'        => 'string',
                    'description' => 'Transaction category filter',
                ],
                'asset_code' => [
                    'type'        => 'string',
                    'description' => 'Asset/currency code (e.g., USD, EUR, BTC)',
                    'pattern'     => '^[A-Z]{3,10}$',
                ],
                'limit' => [
                    'type'        => 'integer',
                    'description' => 'Maximum number of results (default 25)',
                    'minimum'     => 1,
                    'maximum'     => 100,
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
                'transactions' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'id'          => ['type' => 'string'],
                            'date'        => ['type' => 'string'],
                            'amount'      => ['type' => 'number'],
                            'asset'       => ['type' => 'string'],
                            'category'    => ['type' => 'string'],
                            'merchant'    => ['type' => 'string'],
                            'type'        => ['type' => 'string'],
                            'status'      => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                        ],
                    ],
                ],
                'summary' => [
                    'type'       => 'object',
                    'properties' => [
                        'total_inflow'        => ['type' => 'number'],
                        'total_outflow'       => ['type' => 'number'],
                        'net_change'          => ['type' => 'number'],
                        'transaction_count'   => ['type' => 'integer'],
                        'average_transaction' => ['type' => 'number'],
                    ],
                ],
                'explanation' => ['type' => 'string'],
                'total_count' => ['type' => 'integer'],
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
            $explanation = '';

            // If natural language query is provided, parse it
            if (! empty($parameters['query'])) {
                $parsed = $this->nlpService->processQuery($parameters['query'], []);

                $filters = $this->analyzerService->buildFiltersFromEntities($parsed['entities'] ?? []);
                $explanation = $parsed['explanation'] ?? '';

                // Parsed intent/confidence tracked in result metadata
            }

            // Merge explicit filters (override NL-parsed ones)
            foreach (['date_from', 'date_to', 'amount_min', 'amount_max', 'category', 'asset_code'] as $key) {
                if (isset($parameters[$key])) {
                    $filters[$key] = $parameters[$key];
                }
            }

            $accountUuid = $parameters['account_uuid'] ?? null;
            $queryResult = $this->analyzerService->executeQuery($filters, $accountUuid);

            // Generate natural language summary
            $nlSummary = $this->analyzerService->generateNaturalLanguageSummary(
                $queryResult,
                $parameters['query'] ?? 'transaction query'
            );

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $result = [
                'transactions' => $queryResult['transactions'],
                'summary'      => $queryResult['summary'],
                'explanation'  => $explanation ?: 'Query executed with the provided filters.',
                'nl_summary'   => $nlSummary,
                'total_count'  => $queryResult['total_count'],
                'filters_used' => $queryResult['filters'],
            ];

            return ToolExecutionResult::success($result, $durationMs);
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
            'date-range',
            'amount-filter',
            'category-filter',
            'aggregation',
        ];
    }

    public function isCacheable(): bool
    {
        return true;
    }

    public function getCacheTtl(): int
    {
        return 60; // Cache for 1 minute
    }

    /** @param array<string, mixed> $parameters */
    public function validateInput(array $parameters): bool
    {
        // At least one parameter required
        if (empty($parameters)) {
            return false;
        }

        // Validate account UUID format if provided
        if (isset($parameters['account_uuid'])) {
            if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $parameters['account_uuid'])) {
                return false;
            }
        }

        // Validate asset code format if provided
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
