<?php

declare(strict_types=1);

namespace App\Domain\AI\Agents;

class GeneralAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'General Assistant';
    }

    public function getDescription(): string
    {
        return 'General-purpose assistant for platform information and help.';
    }

    public function getKeywords(): array
    {
        return [
            'help', 'what', 'how', 'capabilities', 'feature',
            'about', 'explain', 'can', 'do', 'hi', 'hello',
        ];
    }

    public function getToolNames(): array
    {
        return [];
    }

    protected function buildSystemPrompt(): string
    {
        return 'You are a general assistant for FinAegis, a comprehensive digital banking platform. '
            . 'Help users understand platform capabilities and guide them to the right features.';
    }

    protected function composeTemplateResponse(string $query, array $toolResults): string
    {
        $lower = strtolower($query);

        if (
            str_contains($lower, 'capabilities') || str_contains($lower, 'what can')
            || str_contains($lower, 'features') || str_contains($lower, 'what do')
        ) {
            return $this->capabilitiesResponse();
        }

        if (
            str_contains($lower, 'hello') || str_contains($lower, 'hi ')
            || $lower === 'hi' || str_contains($lower, 'hey')
        ) {
            return "Hello! I'm your AI financial assistant powered by FinAegis.\n\n"
                . "I can help you with:\n"
                . "- Checking account balances and transactions\n"
                . "- Getting exchange rate quotes and trading\n"
                . "- Sending money and checking payment status\n"
                . "- KYC/AML compliance status\n\n"
                . 'What would you like to do today?';
        }

        if (str_contains($lower, 'how') || str_contains($lower, 'explain')) {
            return "FinAegis is a comprehensive digital banking platform that provides:\n\n"
                . "**Core Banking**: Account management, transactions, multi-currency support\n"
                . "**Trading**: Real-time exchange rates, instant trades, multiple asset pairs\n"
                . "**Payments**: Domestic and cross-border transfers with low fees\n"
                . "**Compliance**: Built-in KYC/AML with multi-tier verification\n"
                . "**DeFi Integration**: Cross-chain swaps, yield optimization, liquidity pools\n"
                . "**AI Agents**: Intelligent assistants for financial operations\n\n"
                . 'Ask me anything about these features!';
        }

        return $this->capabilitiesResponse();
    }

    private function capabilitiesResponse(): string
    {
        return "**FinAegis AI Assistant Capabilities**\n\n"
            . "I'm connected to specialized AI agents that can help you with:\n\n"
            . "**Financial Advisor** — Account balances, spending analysis, transaction history\n"
            . "**Trading Specialist** — Exchange rates, quotes, trade execution\n"
            . "**Transfer Agent** — Send money, payment status, remittances\n"
            . "**Compliance Officer** — KYC status, AML screening, verification\n\n"
            . "**Try asking:**\n"
            . "- \"What's my balance?\"\n"
            . "- \"Get me a quote for BTC\"\n"
            . "- \"Check my KYC status\"\n"
            . "- \"Send 100 USDC to John\"\n\n"
            . 'Each query is routed to the most relevant agent automatically.';
    }
}
