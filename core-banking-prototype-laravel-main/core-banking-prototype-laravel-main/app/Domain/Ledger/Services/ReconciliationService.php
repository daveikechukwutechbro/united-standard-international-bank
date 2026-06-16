<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Services;

use App\Domain\Ledger\Contracts\LedgerDriverInterface;
use App\Domain\Ledger\Enums\ReconciliationStatus;
use App\Domain\Ledger\Models\ReconciliationReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

final class ReconciliationService
{
    public function __construct(
        private readonly LedgerDriverInterface $driver,
    ) {
    }

    /**
     * Run reconciliation for a specific domain and GL account.
     */
    public function reconcile(
        string $domain,
        string $glAccountCode,
        string $domainBalance,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): ReconciliationReport {
        $glBalance = $this->driver->balance($glAccountCode, $periodEnd);
        $glAmount = bcadd(is_numeric($glBalance['amount']) ? $glBalance['amount'] : '0', '0', 4);
        $numericDomainBalance = bcadd(is_numeric($domainBalance) ? $domainBalance : '0', '0', 4);
        $variance = bcsub($glAmount, $numericDomainBalance, 4);
        $status = bccomp($variance, '0', 4) === 0
            ? ReconciliationStatus::MATCHED
            : ReconciliationStatus::DISCREPANCY;

        return ReconciliationReport::create([
            'period_start'   => $periodStart,
            'period_end'     => $periodEnd,
            'domain'         => $domain,
            'gl_balance'     => $glAmount,
            'domain_balance' => $domainBalance,
            'variance'       => $variance,
            'status'         => $status,
        ]);
    }

    /**
     * Get reconciliation history for a domain.
     *
     * @return Collection<int, ReconciliationReport>
     */
    public function getHistory(string $domain, int $limit = 30): Collection
    {
        return ReconciliationReport::where('domain', $domain)
            ->orderByDesc('period_end')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark a discrepancy as resolved.
     */
    public function resolveDiscrepancy(string $reportId, string $notes): ReconciliationReport
    {
        $report = ReconciliationReport::findOrFail($reportId);
        $report->update([
            'status' => ReconciliationStatus::RESOLVED,
            'notes'  => $notes,
        ]);

        return $report->refresh();
    }
}
