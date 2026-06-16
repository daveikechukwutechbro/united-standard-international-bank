<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Services;

use App\Domain\Ledger\Models\PostingRule;

final class PostingRuleEngine
{
    /**
     * Find and execute posting rules for a given event.
     *
     * @param  array<string, mixed> $eventData — must contain 'amount' key at minimum
     * @return array<int, array{debit_account: string, credit_account: string, amount: string}>
     */
    public function resolveRules(string $eventName, array $eventData): array
    {
        $rules = PostingRule::active()->forEvent($eventName)->orderBy('priority')->get();
        $entries = [];

        foreach ($rules as $rule) {
            $amount = $this->resolveAmount($rule->amount_expression, $eventData);
            $numericAmount = bcadd(is_numeric($amount) ? $amount : '0', '0', 4);

            if (bccomp($numericAmount, '0', 4) > 0) {
                $entries[] = [
                    'debit_account'  => $rule->debit_account,
                    'credit_account' => $rule->credit_account,
                    'amount'         => $numericAmount,
                ];
            }
        }

        return $entries;
    }

    /**
     * Resolve an amount expression against event data.
     *
     * Supports: 'event.amount', 'event.fee', 'event.interest', or literal numeric strings.
     *
     * @param  array<string, mixed> $eventData
     */
    private function resolveAmount(string $expression, array $eventData): string
    {
        if (str_starts_with($expression, 'event.')) {
            $key = substr($expression, 6);

            return (string) ($eventData[$key] ?? '0');
        }

        // Literal numeric value
        if (is_numeric($expression)) {
            return $expression;
        }

        return '0';
    }
}
