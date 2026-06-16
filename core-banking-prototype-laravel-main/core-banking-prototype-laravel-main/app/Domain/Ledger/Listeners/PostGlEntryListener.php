<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Listeners;

use App\Domain\Ledger\Services\LedgerService;
use App\Domain\Ledger\Services\PostingRuleEngine;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class PostGlEntryListener
{
    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly PostingRuleEngine $ruleEngine,
    ) {
    }

    /**
     * Handle domain events and auto-post GL entries.
     *
     * @param object $event Any domain event with a toArray() or public properties
     */
    public function handle(object $event): void
    {
        if (! config('ledger.auto_posting', true)) {
            return;
        }

        $eventName = class_basename($event);
        $eventData = method_exists($event, 'toArray') ? $event->toArray() : get_object_vars($event);

        $rules = $this->ruleEngine->resolveRules($eventName, $eventData);

        if (empty($rules)) {
            return;
        }

        $lines = [];
        foreach ($rules as $rule) {
            $amount = bcadd(is_numeric($rule['amount']) ? (string) $rule['amount'] : '0', '0', 4);
            $lines[] = [
                'account_code' => $rule['debit_account'],
                'debit'        => $amount,
                'credit'       => '0.0000',
            ];
            $lines[] = [
                'account_code' => $rule['credit_account'],
                'debit'        => '0.0000',
                'credit'       => $amount,
            ];
        }

        try {
            $this->ledgerService->post(
                "Auto: {$eventName}",
                $lines,
                $this->extractDomain($event),
                $eventData['id'] ?? null,
            );
        } catch (RuntimeException $e) {
            Log::error('GL auto-posting failed', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function extractDomain(object $event): string
    {
        $class = $event::class;
        // Extract domain from namespace: App\Domain\{DomainName}\Events\...
        if (preg_match('/App\\\\Domain\\\\(\w+)\\\\/', $class, $matches)) {
            return $matches[1];
        }

        return 'unknown';
    }
}
