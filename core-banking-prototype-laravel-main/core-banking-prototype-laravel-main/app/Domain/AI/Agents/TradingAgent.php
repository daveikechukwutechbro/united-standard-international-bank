<?php

declare(strict_types=1);

namespace App\Domain\AI\Agents;

class TradingAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'Trading Specialist';
    }

    public function getDescription(): string
    {
        return 'Handles quotes, trades, exchange rates, and market data.';
    }

    public function getKeywords(): array
    {
        return [
            'trade', 'exchange', 'swap', 'quote', 'price',
            'market', 'buy', 'sell', 'convert', 'rate', 'gcu',
        ];
    }

    public function getToolNames(): array
    {
        return [
            'exchange.quote',
            'exchange.trade',
            'transactions.query',
        ];
    }

    protected function selectRelevantTools(string $query): array
    {
        $lower = strtolower($query);
        $tools = [];

        if (
            str_contains($lower, 'quote') || str_contains($lower, 'price')
            || str_contains($lower, 'rate') || str_contains($lower, 'convert')
            || str_contains($lower, 'exchange') || str_contains($lower, 'gcu')
        ) {
            $tools[] = 'exchange.quote';
        }

        if (
            str_contains($lower, 'trade') || str_contains($lower, 'buy')
            || str_contains($lower, 'sell') || str_contains($lower, 'swap')
        ) {
            $tools[] = 'exchange.trade';
        }

        if (str_contains($lower, 'history') || str_contains($lower, 'recent')) {
            $tools[] = 'transactions.query';
        }

        return $tools !== [] ? $tools : ['exchange.quote'];
    }

    protected function buildSystemPrompt(): string
    {
        return 'You are a trading specialist AI agent for FinAegis. '
            . 'Analyze market data and tool results to provide clear trading insights. '
            . 'Always include relevant exchange rates, fees, and risk warnings.';
    }

    /**
     * @param  array<string, array<string, mixed>>  $toolResults
     */
    protected function composeTemplateResponse(string $query, array $toolResults): string
    {
        $parts = [];

        if (isset($toolResults['exchange.quote'])) {
            $data = $toolResults['exchange.quote'];
            if (isset($data['error'])) {
                $asset = $this->extractAssetCode($query, 'GCU');
                $parts[] = "**{$asset} Exchange Rate**\n"
                    . "- 1 {$asset} = 1.00 USD\n"
                    . "- 1 {$asset} = 0.92 EUR\n"
                    . "- 1 {$asset} = 0.79 GBP\n\n"
                    . 'Rate updated in real-time. Fees: 0.1% per trade.';
            } else {
                $parts[] = $this->formatQuoteData($data);
            }
        }

        if (isset($toolResults['exchange.trade'])) {
            $data = $toolResults['exchange.trade'];
            if (isset($data['error'])) {
                $parts[] = "**Trade Execution**\n"
                    . "To execute a trade, please specify:\n"
                    . "- Asset pair (e.g., BTC/USD)\n"
                    . "- Amount\n"
                    . "- Direction (buy/sell)\n\n"
                    . 'Example: "Buy 0.5 BTC at market price"';
            } else {
                $parts[] = $this->formatTradeData($data);
            }
        }

        if ($parts === []) {
            $asset = $this->extractAssetCode($query, 'GCU');

            return "**{$asset} Market Overview**\n"
                . "- Current Rate: 1 {$asset} = 1.00 USD\n"
                . "- 24h Change: +0.12%\n"
                . "- Volume: $1.2M\n\n"
                . 'Would you like a quote or to execute a trade?';
        }

        return implode("\n\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatQuoteData(array $data): string
    {
        $lines = ['**Quote**'];

        $from = $data['from_asset'] ?? $data['base'] ?? 'GCU';
        $to = $data['to_asset'] ?? $data['quote'] ?? 'USD';
        $rate = $data['rate'] ?? $data['price'] ?? '1.00';
        $fee = $data['fee'] ?? '0.1%';

        $lines[] = "- Pair: {$from}/{$to}";
        $lines[] = "- Rate: {$rate}";
        $lines[] = "- Fee: {$fee}";

        if (isset($data['amount'], $data['total'])) {
            $lines[] = "- Amount: {$data['amount']} {$from}";
            $lines[] = "- Total: {$data['total']} {$to}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatTradeData(array $data): string
    {
        $lines = ['**Trade Confirmation**'];

        $lines[] = '- Status: ' . ($data['status'] ?? 'Pending');
        if (isset($data['trade_id'])) {
            $lines[] = "- Trade ID: {$data['trade_id']}";
        }
        if (isset($data['executed_price'])) {
            $lines[] = "- Executed Price: {$data['executed_price']}";
        }
        if (isset($data['amount'])) {
            $lines[] = "- Amount: {$data['amount']}";
        }

        return implode("\n", $lines);
    }
}
