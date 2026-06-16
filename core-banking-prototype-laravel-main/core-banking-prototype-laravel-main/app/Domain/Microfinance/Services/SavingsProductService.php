<?php

declare(strict_types=1);

namespace App\Domain\Microfinance\Services;

use App\Domain\Microfinance\Enums\ShareAccountStatus;
use App\Domain\Microfinance\Models\ShareAccount;
use Carbon\Carbon;
use InvalidArgumentException;

class SavingsProductService
{
    /**
     * Check whether a savings account is dormant.
     *
     * When $lastActivityDaysAgo is null the account is not dormant (no
     * activity data available — caller must supply a value to trigger the check).
     *
     * @return array{is_dormant: bool, days_inactive: int, threshold: int}
     */
    public function checkDormancy(string $accountId, ?int $lastActivityDaysAgo = null): array
    {
        $threshold = (int) config('microfinance.dormancy.days_until_dormant', 180);

        $daysInactive = $lastActivityDaysAgo ?? 0;
        $isDormant = $lastActivityDaysAgo !== null && $daysInactive >= $threshold;

        return [
            'is_dormant'    => $isDormant,
            'days_inactive' => $daysInactive,
            'threshold'     => $threshold,
        ];
    }

    /**
     * Batch-update accounts that have been inactive beyond the dormancy threshold.
     *
     * Returns the number of accounts updated.
     *
     * NOTE: This performs an in-memory classification because last_activity_date
     * is an application-layer concept.  Implementations that persist last-activity
     * timestamps on ShareAccount should adapt this to a DB-level update.
     */
    public function applyDormancyStatus(): int
    {
        $threshold = (int) config('microfinance.dormancy.days_until_dormant', 180);
        $cutoff = Carbon::now()->subDays($threshold);

        $updated = ShareAccount::where('status', ShareAccountStatus::ACTIVE->value)
            ->where('updated_at', '<=', $cutoff)
            ->update(['status' => ShareAccountStatus::DORMANT->value]);

        return $updated;
    }

    /**
     * Calculate simple interest.
     *
     * Formula: I = P * r * (t / 365)
     *
     * @param string $balance     Principal amount as a decimal string.
     * @param float  $annualRate  Annual interest rate as a decimal (e.g. 0.12 for 12 %).
     * @param int    $days        Number of days.
     *
     * @return string Interest amount rounded to 2 decimal places.
     */
    public function calculateInterest(string $balance, float $annualRate, int $days): string
    {
        if ($days < 0) {
            throw new InvalidArgumentException('Days must be non-negative.');
        }

        // I = P * r * d / 365  — using bcmath for precision
        /** @var numeric-string $rate */
        $rate = sprintf('%.10f', $annualRate);
        /** @var numeric-string $principal */
        $principal = $balance;
        $daysFrac = bcdiv((string) $days, '365', 10);
        $interest = bcmul($principal, $rate, 10);
        $interest = bcmul($interest, $daysFrac, 10);

        return bcadd($interest, '0', 2); // round to 2 dp
    }

    /**
     * Calculate compound interest.
     *
     * Formula: A = P * (1 + r/n)^(n*t) — interest = A - P
     *
     * Note: Uses float arithmetic for exponentiation (pow). This is suitable for simulation
     * and typical account balances. Production deployment should replace with bcmath or a
     * fixed-point library for amounts exceeding 15 significant digits.
     *
     * @param string $balance    Principal amount as a decimal string.
     * @param float  $annualRate Annual interest rate as a decimal (e.g. 0.12 for 12 %).
     * @param int    $days       Number of days.
     * @param string $frequency  Compounding frequency: 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'annually'.
     *
     * @return string Interest amount rounded to 2 decimal places.
     */
    public function compoundInterest(
        string $balance,
        float $annualRate,
        int $days,
        string $frequency = 'monthly',
    ): string {
        if ($days < 0) {
            throw new InvalidArgumentException('Days must be non-negative.');
        }

        $n = $this->compoundingPeriodsPerYear($frequency);

        // t = days / 365 (as float for exponentiation)
        $t = $days / 365.0;

        // A = P * (1 + r/n)^(n*t)
        $rPerN = $annualRate / $n;
        $power = pow(1.0 + $rPerN, $n * $t);

        $principal = (float) $balance;
        $amount = $principal * $power;
        $interest = $amount - $principal;

        return number_format($interest, 2, '.', '');
    }

    private function compoundingPeriodsPerYear(string $frequency): int
    {
        return match ($frequency) {
            'daily'     => 365,
            'weekly'    => 52,
            'monthly'   => 12,
            'quarterly' => 4,
            'annually'  => 1,
            default     => throw new InvalidArgumentException("Unknown compounding frequency: {$frequency}"),
        };
    }
}
