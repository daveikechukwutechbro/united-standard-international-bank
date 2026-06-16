<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Services;

use App\Domain\Microfinance\Enums\ProvisionCategory;
use App\Domain\Microfinance\Models\LoanProvision;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoanProvisioningService
{
    /**
     * Classify a loan and create or update its provision record.
     *
     * The outstanding amount is passed as a parameter to keep this service
     * decoupled from the Lending domain.
     *
     * @throws RuntimeException
     */
    public function classifyLoan(
        string $loanId,
        int $daysOverdue,
        string $outstandingAmount,
    ): LoanProvision {
        $category = $this->determineCategoryFromDays($daysOverdue);

        /** @var numeric-string $outstanding */
        $outstanding = $outstandingAmount;
        /** @var numeric-string $rateStr */
        $rateStr = sprintf('%.10f', $category->rate());
        $provisionAmount = bcmul($outstanding, $rateStr, 2);

        $provision = LoanProvision::where('loan_id', $loanId)->first();

        if ($provision !== null) {
            $provision->update([
                'category'         => $category,
                'provision_amount' => $provisionAmount,
                'days_overdue'     => $daysOverdue,
                'review_date'      => Carbon::today(),
            ]);

            return $provision->fresh() ?? $provision;
        }

        return LoanProvision::create([
            'loan_id'          => $loanId,
            'category'         => $category,
            'provision_amount' => $provisionAmount,
            'days_overdue'     => $daysOverdue,
            'review_date'      => Carbon::today(),
        ]);
    }

    /**
     * Batch reclassify all active provisions.
     *
     * Returns the count of provisions reclassified.
     */
    public function reclassifyAll(): int
    {
        $count = 0;

        LoanProvision::chunkById(100, function (Collection $provisions) use (&$count): void {
            foreach ($provisions as $provision) {
                $category = $this->determineCategoryFromDays($provision->days_overdue);
                $provision->update([
                    'category'    => $category,
                    'review_date' => Carbon::today(),
                ]);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Write off a loan provision, marking it as LOSS category.
     *
     * @throws RuntimeException
     */
    public function writeOff(string $provisionId, string $reason): LoanProvision
    {
        $provision = LoanProvision::find($provisionId);

        if ($provision === null) {
            throw new RuntimeException("Loan provision not found: {$provisionId}");
        }

        $provision->update([
            'category'    => ProvisionCategory::LOSS,
            'notes'       => $reason,
            'review_date' => Carbon::today(),
        ]);

        return $provision->fresh() ?? $provision;
    }

    /**
     * Get all provisions for a given category.
     *
     * @return Collection<int, LoanProvision>
     */
    public function getProvisionsByCategory(string $category): Collection
    {
        $provisionCategory = ProvisionCategory::from($category);

        return LoanProvision::where('category', $provisionCategory->value)->get();
    }

    /**
     * Get total provisions grouped by category.
     *
     * @return array{standard: string, substandard: string, doubtful: string, loss: string, total: string}
     */
    public function getTotalProvisions(): array
    {
        $totals = [
            'standard'    => '0.00',
            'substandard' => '0.00',
            'doubtful'    => '0.00',
            'loss'        => '0.00',
            'total'       => '0.00',
        ];

        /** @var array<object{category: string, total: string}> $rows */
        $rows = DB::table('mfi_loan_provisions')
            ->selectRaw('category, SUM(provision_amount) as total')
            ->groupBy('category')
            ->get()
            ->all();

        foreach ($rows as $row) {
            $cat = $row->category;
            if (isset($totals[$cat])) {
                /** @var numeric-string $rowTotal */
                $rowTotal = (string) $row->total;
                /** @var numeric-string $catTotal */
                $catTotal = $totals[$cat];
                $totals[$cat] = bcadd($catTotal, $rowTotal, 2);
            }
        }

        $totals['total'] = bcadd(
            bcadd(bcadd($totals['standard'], $totals['substandard'], 2), $totals['doubtful'], 2),
            $totals['loss'],
            2,
        );

        return $totals;
    }

    private function determineCategoryFromDays(int $daysOverdue): ProvisionCategory
    {
        $lossThreshold = (int) config('microfinance.provisioning.loss_days', 365);
        $doubtfulThreshold = (int) config('microfinance.provisioning.doubtful_days', 180);
        $substandardThreshold = (int) config('microfinance.provisioning.substandard_days', 90);
        $standardThreshold = (int) config('microfinance.provisioning.standard_days', 30);

        if ($daysOverdue >= $lossThreshold) {
            return ProvisionCategory::LOSS;
        }

        if ($daysOverdue >= $doubtfulThreshold) {
            return ProvisionCategory::DOUBTFUL;
        }

        if ($daysOverdue >= $substandardThreshold) {
            return ProvisionCategory::SUBSTANDARD;
        }

        if ($daysOverdue >= $standardThreshold) {
            return ProvisionCategory::STANDARD;
        }

        // Less than standard_days still gets standard classification
        return ProvisionCategory::STANDARD;
    }
}
