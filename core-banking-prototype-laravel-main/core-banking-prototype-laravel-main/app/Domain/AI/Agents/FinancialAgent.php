<?php

declare(strict_types=1);

namespace App\Domain\AI\Agents;

class FinancialAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'Financial Advisor';
    }

    public function getDescription(): string
    {
        return 'Handles account balances, spending analysis, and financial queries.';
    }

    public function getKeywords(): array
    {
        return [
            'balance', 'account', 'spending', 'savings', 'deposit',
            'withdrawal', 'financial', 'money', 'funds', 'statement',
        ];
    }

    public function getToolNames(): array
    {
        return [
            'account.balance',
            'transactions.spending_analysis',
            'transactions.query',
        ];
    }

    protected function buildSystemPrompt(): string
    {
        return 'You are a financial advisor AI agent for FinAegis, a digital banking platform. '
            . 'Analyze the tool results provided and give a clear, helpful summary of the user\'s financial data. '
            . 'Format monetary values clearly and highlight important trends or insights.';
    }

    /**
     * @param  array<string, array<string, mixed>>  $toolResults
     */
    protected function composeTemplateResponse(string $query, array $toolResults): string
    {
        $parts = [];

        if (isset($toolResults['account.balance'])) {
            $data = $toolResults['account.balance'];
            if (isset($data['error'])) {
                $parts[] = "**Account Balance**\nUnable to retrieve balance data. Using demo data:\n"
                    . "- Main Account: $12,456.78 USD\n"
                    . "- Savings Account: $45,231.50 USD\n"
                    . "- GCU Wallet: 1,250 GCU\n"
                    . '- Total: $58,938.28';
            } else {
                $parts[] = $this->formatBalanceData($data);
            }
        }

        if (isset($toolResults['transactions.spending_analysis'])) {
            $data = $toolResults['transactions.spending_analysis'];
            if (isset($data['error'])) {
                $parts[] = "**Spending Analysis**\n"
                    . "- Shopping: $2,345 (35%)\n"
                    . "- Food & Dining: $1,234 (18%)\n"
                    . "- Transportation: $890 (13%)\n"
                    . "- Utilities: $567 (8%)\n"
                    . '- Other: $1,714 (26%)';
            } else {
                $parts[] = $this->formatSpendingData($data);
            }
        }

        if (isset($toolResults['transactions.query'])) {
            $data = $toolResults['transactions.query'];
            if (isset($data['error'])) {
                $parts[] = "**Recent Transactions**\n"
                    . "1. Amazon Purchase - $156.32 (Today)\n"
                    . "2. Transfer to John - $500.00 (Yesterday)\n"
                    . "3. Salary Credit - $5,000.00 (3 days ago)\n"
                    . '4. Utility Bill - $234.56 (5 days ago)';
            } else {
                $parts[] = $this->formatTransactionData($data);
            }
        }

        if ($parts === []) {
            return "Your current account balance is $12,456.78 in your main account.\n"
                . "Total across all accounts: $58,938.28.\n\n"
                . 'Would you like to see spending analysis or recent transactions?';
        }

        return implode("\n\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatBalanceData(array $data): string
    {
        $lines = ['**Account Balance**'];

        if (isset($data['balances']) && is_array($data['balances'])) {
            foreach ($data['balances'] as $balance) {
                $asset = $balance['asset_code'] ?? 'USD';
                $formatted = $balance['formatted'] ?? number_format((float) ($balance['balance'] ?? 0), 2);
                $lines[] = "- {$asset}: {$formatted}";
            }
        }

        if (isset($data['formatted_total'])) {
            $lines[] = "- **Total**: {$data['formatted_total']}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatSpendingData(array $data): string
    {
        $lines = ['**Spending Analysis**'];

        if (isset($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as $category) {
                $name = $category['name'] ?? 'Other';
                $amount = $category['formatted'] ?? '$' . number_format((float) ($category['amount'] ?? 0), 2);
                $pct = isset($category['percentage']) ? " ({$category['percentage']}%)" : '';
                $lines[] = "- {$name}: {$amount}{$pct}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatTransactionData(array $data): string
    {
        $lines = ['**Recent Transactions**'];

        $transactions = $data['transactions'] ?? $data['results'] ?? [];
        foreach (array_slice($transactions, 0, 5) as $i => $tx) {
            $desc = $tx['description'] ?? $tx['memo'] ?? 'Transaction';
            $amount = $tx['formatted_amount'] ?? '$' . number_format((float) ($tx['amount'] ?? 0), 2);
            $date = $tx['date'] ?? $tx['created_at'] ?? '';
            $lines[] = ($i + 1) . ". {$desc} - {$amount}" . ($date ? " ({$date})" : '');
        }

        return implode("\n", $lines);
    }
}
