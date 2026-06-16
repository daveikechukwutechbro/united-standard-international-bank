<?php

declare(strict_types=1);

namespace App\Domain\AI\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Analyzes natural language query results and transforms parsed intents/entities
 * into structured transaction query filters and summaries.
 */
class TransactionQueryAnalyzerService
{
    /**
     * Build query filters from parsed NLP entities.
     *
     * @param  array<string, mixed>  $entities
     * @return array<string, mixed>
     */
    public function buildFiltersFromEntities(array $entities): array
    {
        $filters = [];

        foreach ($entities as $entity) {
            $type = $entity['type'] ?? '';
            $value = $entity['value'] ?? null;

            match ($type) {
                NaturalLanguageProcessorService::ENTITY_AMOUNT     => $this->applyAmountFilter($filters, $value),
                NaturalLanguageProcessorService::ENTITY_CURRENCY   => $filters['asset_code'] = strtoupper((string) $value),
                NaturalLanguageProcessorService::ENTITY_DATE       => $this->applyDateFilter($filters, $value),
                NaturalLanguageProcessorService::ENTITY_DATE_RANGE => $this->applyDateRangeFilter($filters, $value),
                NaturalLanguageProcessorService::ENTITY_CATEGORY   => $filters['category'] = (string) $value,
                NaturalLanguageProcessorService::ENTITY_ACCOUNT    => $filters['account_type'] = (string) $value,
                NaturalLanguageProcessorService::ENTITY_RECIPIENT  => $filters['recipient'] = (string) $value,
                default                                            => null,
            };
        }

        // Default date range to last 30 days if no date specified
        if (! isset($filters['date_from']) && ! isset($filters['date_to'])) {
            $filters['date_from'] = Carbon::now()->subDays(30)->startOfDay()->toIso8601String();
            $filters['date_to'] = Carbon::now()->endOfDay()->toIso8601String();
        }

        return $filters;
    }

    /**
     * Generate demo transaction results matching the given filters.
     *
     * @param  array<string, mixed>  $filters
     * @param  string|null           $accountUuid
     * @return array<string, mixed>
     */
    public function executeQuery(array $filters, ?string $accountUuid = null): array
    {
        // Query execution is tracked in the controller/tool layer

        // Generate demo transactions matching filters
        $transactions = $this->generateDemoTransactions($filters);

        // Calculate summary statistics
        $summary = $this->calculateSummary($transactions, $filters);

        return [
            'transactions' => $transactions,
            'summary'      => $summary,
            'filters'      => $filters,
            'total_count'  => count($transactions),
            'query_time'   => now()->toIso8601String(),
        ];
    }

    /**
     * Generate a spending analysis from filters.
     *
     * @param  array<string, mixed>  $filters
     * @param  string|null           $accountUuid
     * @return array<string, mixed>
     */
    public function analyzeSpending(array $filters, ?string $accountUuid = null): array
    {
        $transactions = $this->generateDemoTransactions($filters);

        $byCategory = $this->groupByCategory($transactions);
        $byDay = $this->groupByDay($transactions);
        $topMerchants = $this->getTopMerchants($transactions);
        $trends = $this->calculateTrends($byDay);

        return [
            'period' => [
                'from' => $filters['date_from'] ?? Carbon::now()->subDays(30)->toIso8601String(),
                'to'   => $filters['date_to'] ?? now()->toIso8601String(),
            ],
            'total_spent'   => array_sum(array_map(fn (array $t): float => abs($t['amount']), $transactions)),
            'total_earned'  => array_sum(array_map(fn (array $t): float => $t['amount'] > 0 ? $t['amount'] : 0.0, $transactions)),
            'by_category'   => $byCategory,
            'by_day'        => $byDay,
            'top_merchants' => $topMerchants,
            'trends'        => $trends,
            'insights'      => $this->generateInsights($byCategory, $trends),
        ];
    }

    /**
     * Generate a natural-language summary for query results.
     *
     * @param  array<string, mixed>  $queryResult
     * @param  string                $originalQuery
     * @return string
     */
    public function generateNaturalLanguageSummary(array $queryResult, string $originalQuery): string
    {
        $count = $queryResult['total_count'] ?? 0;
        $summary = $queryResult['summary'] ?? [];

        if ($count === 0) {
            return "No transactions found matching your query: \"{$originalQuery}\".";
        }

        $totalSpent = number_format(abs($summary['total_outflow'] ?? 0), 2);
        $totalReceived = number_format($summary['total_inflow'] ?? 0, 2);
        $period = $summary['period'] ?? 'the selected period';

        $parts = ["Found {$count} transactions for {$period}."];

        if (($summary['total_outflow'] ?? 0) > 0) {
            $parts[] = "Total spent: \${$totalSpent}.";
        }

        if (($summary['total_inflow'] ?? 0) > 0) {
            $parts[] = "Total received: \${$totalReceived}.";
        }

        if (isset($summary['average_transaction'])) {
            $avg = number_format($summary['average_transaction'], 2);
            $parts[] = "Average transaction: \${$avg}.";
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  mixed                 $value
     */
    private function applyAmountFilter(array &$filters, mixed $value): void
    {
        if (is_numeric($value)) {
            $filters['amount_min'] = (float) $value;
        } elseif (is_array($value)) {
            if (isset($value['min'])) {
                $filters['amount_min'] = (float) $value['min'];
            }
            if (isset($value['max'])) {
                $filters['amount_max'] = (float) $value['max'];
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  mixed                 $value
     */
    private function applyDateFilter(array &$filters, mixed $value): void
    {
        try {
            $date = Carbon::parse((string) $value);
            $filters['date_from'] = $date->startOfDay()->toIso8601String();
            $filters['date_to'] = $date->endOfDay()->toIso8601String();
        } catch (Exception $e) {
            Log::debug('Failed to parse date filter', ['value' => $value, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  mixed                 $value
     */
    private function applyDateRangeFilter(array &$filters, mixed $value): void
    {
        if (is_array($value)) {
            if (isset($value['start'])) {
                try {
                    $filters['date_from'] = Carbon::parse((string) $value['start'])->startOfDay()->toIso8601String();
                } catch (Exception $e) {
                    Log::debug('Failed to parse date range start', ['value' => $value['start'], 'error' => $e->getMessage()]);
                }
            }
            if (isset($value['end'])) {
                try {
                    $filters['date_to'] = Carbon::parse((string) $value['end'])->endOfDay()->toIso8601String();
                } catch (Exception $e) {
                    Log::debug('Failed to parse date range end', ['value' => $value['end'], 'error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function generateDemoTransactions(array $filters): array
    {
        $categories = ['groceries', 'dining', 'transport', 'utilities', 'entertainment', 'healthcare', 'shopping', 'transfer'];
        $merchants = [
            'groceries'     => ['Fresh Market', 'Whole Foods', 'Trader Joe\'s'],
            'dining'        => ['Café Milano', 'Sushi Express', 'The Green Fork'],
            'transport'     => ['Uber', 'City Metro', 'Shell Gas'],
            'utilities'     => ['Electric Co.', 'Water Utility', 'Internet Plus'],
            'entertainment' => ['Netflix', 'Spotify', 'Cinema World'],
            'healthcare'    => ['City Pharmacy', 'Dr. Smith Clinic'],
            'shopping'      => ['Amazon', 'eBay', 'Target'],
            'transfer'      => ['Wire Transfer', 'P2P Transfer'],
        ];

        $dateFrom = isset($filters['date_from']) ? Carbon::parse($filters['date_from']) : Carbon::now()->subDays(30);
        $dateTo = isset($filters['date_to']) ? Carbon::parse($filters['date_to']) : Carbon::now();
        $daySpan = max(1, (int) $dateFrom->diffInDays($dateTo));

        $transactions = [];
        $count = min(25, max(5, $daySpan));

        for ($i = 0; $i < $count; $i++) {
            $category = $categories[array_rand($categories)];
            $merchantList = $merchants[$category];
            $merchant = $merchantList[array_rand($merchantList)];
            $isOutflow = $category !== 'transfer' || rand(0, 1) === 0;
            $amount = round(($isOutflow ? -1 : 1) * (rand(500, 50000) / 100), 2);
            $date = $dateFrom->copy()->addDays(rand(0, $daySpan));
            $asset = $filters['asset_code'] ?? 'USD';

            // Apply filters
            if (isset($filters['category']) && $category !== $filters['category']) {
                $category = $filters['category'];
                $merchantList = $merchants[$category] ?? [$filters['category']];
                $merchant = $merchantList[array_rand($merchantList)];
            }

            if (isset($filters['amount_min']) && abs($amount) < $filters['amount_min']) {
                $amount = ($isOutflow ? -1 : 1) * $filters['amount_min'] * (1 + rand(0, 100) / 100);
            }

            if (isset($filters['amount_max']) && abs($amount) > $filters['amount_max']) {
                $amount = ($isOutflow ? -1 : 1) * $filters['amount_max'] * (rand(50, 100) / 100);
            }

            $transactions[] = [
                'id'          => sprintf('txn_%s', substr(md5((string) $i . $merchant), 0, 12)),
                'date'        => $date->toIso8601String(),
                'amount'      => round($amount, 2),
                'asset'       => $asset,
                'category'    => $category,
                'merchant'    => $merchant,
                'type'        => $isOutflow ? 'debit' : 'credit',
                'status'      => 'completed',
                'description' => ($isOutflow ? 'Payment to ' : 'Received from ') . $merchant,
            ];
        }

        // Sort by date descending
        usort($transactions, fn (array $a, array $b): int => strcmp($b['date'], $a['date']));

        return $transactions;
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @param  array<string, mixed>              $filters
     * @return array<string, mixed>
     */
    private function calculateSummary(array $transactions, array $filters): array
    {
        $totalInflow = 0.0;
        $totalOutflow = 0.0;

        foreach ($transactions as $tx) {
            if ($tx['amount'] > 0) {
                $totalInflow += $tx['amount'];
            } else {
                $totalOutflow += abs($tx['amount']);
            }
        }

        $count = count($transactions);
        $amounts = array_map(fn (array $t): float => abs($t['amount']), $transactions);

        return [
            'total_inflow'         => round($totalInflow, 2),
            'total_outflow'        => round($totalOutflow, 2),
            'net_change'           => round($totalInflow - $totalOutflow, 2),
            'transaction_count'    => $count,
            'average_transaction'  => $count > 0 ? round(array_sum($amounts) / $count, 2) : 0.0,
            'largest_transaction'  => $amounts !== [] ? round(max($amounts), 2) : 0.0,
            'smallest_transaction' => $amounts !== [] ? round(min($amounts), 2) : 0.0,
            'period'               => $this->formatPeriod($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function formatPeriod(array $filters): string
    {
        $from = isset($filters['date_from']) ? Carbon::parse($filters['date_from'])->format('M j, Y') : '30 days ago';
        $to = isset($filters['date_to']) ? Carbon::parse($filters['date_to'])->format('M j, Y') : 'today';

        return "{$from} to {$to}";
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array<string, array<string, mixed>>
     */
    private function groupByCategory(array $transactions): array
    {
        $grouped = [];

        foreach ($transactions as $tx) {
            $cat = $tx['category'];
            if (! isset($grouped[$cat])) {
                $grouped[$cat] = ['total' => 0.0, 'count' => 0];
            }
            $grouped[$cat]['total'] = round($grouped[$cat]['total'] + abs($tx['amount']), 2);
            $grouped[$cat]['count']++;
        }

        arsort($grouped);

        return $grouped;
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array<string, array<string, mixed>>
     */
    private function groupByDay(array $transactions): array
    {
        $grouped = [];

        foreach ($transactions as $tx) {
            $day = Carbon::parse($tx['date'])->format('Y-m-d');
            if (! isset($grouped[$day])) {
                $grouped[$day] = ['total' => 0.0, 'count' => 0];
            }
            $grouped[$day]['total'] = round($grouped[$day]['total'] + abs($tx['amount']), 2);
            $grouped[$day]['count']++;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return array<int, array<string, mixed>>
     */
    private function getTopMerchants(array $transactions): array
    {
        $merchants = [];

        foreach ($transactions as $tx) {
            $name = $tx['merchant'];
            if (! isset($merchants[$name])) {
                $merchants[$name] = ['total' => 0.0, 'count' => 0];
            }
            $merchants[$name]['total'] = round($merchants[$name]['total'] + abs($tx['amount']), 2);
            $merchants[$name]['count']++;
        }

        arsort($merchants);

        $result = [];
        foreach (array_slice($merchants, 0, 5, true) as $name => $data) {
            $result[] = ['merchant' => $name, 'total' => $data['total'], 'count' => $data['count']];
        }

        return $result;
    }

    /**
     * @param  array<string, array<string, mixed>>  $byDay
     * @return array<string, mixed>
     */
    private function calculateTrends(array $byDay): array
    {
        $totals = array_column($byDay, 'total');
        $count = count($totals);

        if ($count < 2) {
            return ['direction' => 'stable', 'change_percent' => 0.0];
        }

        $midpoint = (int) floor($count / 2);
        $firstHalf = array_sum(array_slice($totals, 0, $midpoint));
        $secondHalf = array_sum(array_slice($totals, $midpoint));

        $change = $firstHalf > 0 ? round((($secondHalf - $firstHalf) / $firstHalf) * 100, 1) : 0.0;

        return [
            'direction'       => $change > 5 ? 'increasing' : ($change < -5 ? 'decreasing' : 'stable'),
            'change_percent'  => $change,
            'first_half_avg'  => $midpoint > 0 ? round($firstHalf / $midpoint, 2) : 0.0,
            'second_half_avg' => ($count - $midpoint) > 0 ? round($secondHalf / ($count - $midpoint), 2) : 0.0,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $byCategory
     * @param  array<string, mixed>                 $trends
     * @return array<int, string>
     */
    private function generateInsights(array $byCategory, array $trends): array
    {
        $insights = [];

        // Top spending category insight
        if (! empty($byCategory)) {
            $topCategory = array_key_first($byCategory);
            $topAmount = number_format($byCategory[$topCategory]['total'], 2);
            $insights[] = "Your largest spending category is {$topCategory} at \${$topAmount}.";
        }

        // Trend insight
        $direction = $trends['direction'] ?? 'stable';
        $changePct = abs($trends['change_percent'] ?? 0);
        if ($direction === 'increasing') {
            $insights[] = "Your spending has increased by {$changePct}% in the recent period.";
        } elseif ($direction === 'decreasing') {
            $insights[] = "Your spending has decreased by {$changePct}% - good progress on savings!";
        } else {
            $insights[] = 'Your spending has remained stable over this period.';
        }

        return $insights;
    }
}
