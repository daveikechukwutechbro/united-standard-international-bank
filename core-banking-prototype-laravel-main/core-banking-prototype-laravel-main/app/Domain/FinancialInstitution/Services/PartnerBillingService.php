<?php

declare(strict_types=1);

namespace App\Domain\FinancialInstitution\Services;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerInvoice;
use App\Domain\FinancialInstitution\Models\PartnerUsageRecord;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Manages partner billing, invoice generation, and billing cycle discounts.
 */
class PartnerBillingService
{
    public function __construct(
        private readonly PartnerTierService $tierService,
    ) {
    }

    /**
     * Generate an invoice for a partner's billing period.
     */
    public function generateInvoice(
        FinancialInstitutionPartner $partner,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): PartnerInvoice {
        $breakdown = $this->calculateBillingBreakdown($partner, $periodStart, $periodEnd);

        $invoice = PartnerInvoice::create([
            'uuid'                   => (string) \Illuminate\Support\Str::uuid(),
            'partner_id'             => $partner->id,
            'period_start'           => $periodStart->toDateString(),
            'period_end'             => $periodEnd->toDateString(),
            'billing_cycle'          => $partner->billing_cycle ?? 'monthly',
            'status'                 => 'pending',
            'tier'                   => $breakdown['tier'],
            'base_amount_usd'        => $breakdown['base_amount'],
            'discount_amount_usd'    => $breakdown['discount_amount'],
            'discount_reason'        => $breakdown['discount_reason'],
            'total_api_calls'        => $breakdown['total_api_calls'],
            'included_api_calls'     => $breakdown['included_api_calls'],
            'overage_api_calls'      => $breakdown['overage_api_calls'],
            'overage_amount_usd'     => $breakdown['overage_amount'],
            'line_items'             => $breakdown['line_items'],
            'additional_charges_usd' => 0,
            'subtotal_usd'           => 0,
            'tax_rate'               => 0,
            'tax_amount_usd'         => 0,
            'total_amount_usd'       => 0,
            'total_amount_display'   => 0,
            'display_currency'       => config('baas.billing.currency', 'USD'),
            'exchange_rate'          => 1.0,
            'due_date'               => now()->addDays((int) config('baas.billing.invoice_due_days', 30))->toDateString(),
        ]);

        $invoice->calculateTotals();

        Log::info('Partner invoice generated', [
            'partner_id' => $partner->id,
            'invoice_id' => $invoice->id,
            'total_usd'  => $invoice->total_amount_usd,
        ]);

        return $invoice->fresh() ?? $invoice;
    }

    /**
     * Calculate the billing breakdown for a partner's period.
     *
     * @return array<string, mixed>
     */
    public function calculateBillingBreakdown(
        FinancialInstitutionPartner $partner,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): array {
        $tier = $this->tierService->getPartnerTier($partner);
        $billingCycle = $partner->billing_cycle ?? 'monthly';

        $baseAmount = $tier->monthlyPrice();

        // Adjust base for billing cycle
        if ($billingCycle === 'quarterly') {
            $baseAmount *= 3;
        } elseif ($billingCycle === 'annually') {
            $baseAmount *= 12;
        }

        // Apply billing cycle discount
        $discountAmount = $this->applyBillingCycleDiscount($baseAmount, $billingCycle);
        $discountReason = $discountAmount > 0
            ? ucfirst($billingCycle) . ' billing discount'
            : null;

        // Calculate API usage
        $usageRecords = PartnerUsageRecord::where('partner_id', $partner->id)
            ->whereBetween('usage_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $totalApiCalls = (int) $usageRecords->sum('api_calls');
        $includedApiCalls = $tier->apiCallLimit();

        // Adjust included calls for billing cycle
        if ($billingCycle === 'quarterly') {
            $includedApiCalls *= 3;
        } elseif ($billingCycle === 'annually') {
            $includedApiCalls *= 12;
        }

        $overageApiCalls = max(0, $totalApiCalls - $includedApiCalls);
        $overageAmount = ($overageApiCalls / 1000) * $tier->overagePricePerThousand();

        $lineItems = [
            [
                'description' => $tier->label() . ' plan (' . $billingCycle . ')',
                'amount'      => $baseAmount,
            ],
        ];

        if ($discountAmount > 0) {
            $lineItems[] = [
                'description' => $discountReason,
                'amount'      => -$discountAmount,
            ];
        }

        if ($overageAmount > 0) {
            $lineItems[] = [
                'description' => "API overage: {$overageApiCalls} calls @ \${$tier->overagePricePerThousand()}/1000",
                'amount'      => round($overageAmount, 2),
            ];
        }

        return [
            'tier'               => $tier->value,
            'billing_cycle'      => $billingCycle,
            'base_amount'        => round($baseAmount, 2),
            'discount_amount'    => round($discountAmount, 2),
            'discount_reason'    => $discountReason,
            'total_api_calls'    => $totalApiCalls,
            'included_api_calls' => $includedApiCalls,
            'overage_api_calls'  => $overageApiCalls,
            'overage_amount'     => round($overageAmount, 2),
            'line_items'         => $lineItems,
            'estimated_total'    => round($baseAmount - $discountAmount + $overageAmount, 2),
        ];
    }

    /**
     * Generate invoices for all active partners due for billing.
     *
     * @return Collection<int, PartnerInvoice>
     */
    public function generateBatchInvoices(): Collection
    {
        $partners = FinancialInstitutionPartner::active()
            ->where(function ($query) {
                $query->whereNull('next_billing_date')
                    ->orWhere('next_billing_date', '<=', now()->toDateString());
            })
            ->get();

        $invoices = collect();

        foreach ($partners as $partner) {
            $periodEnd = now()->subDay();
            $periodStart = $this->calculatePeriodStart($partner, $periodEnd);

            try {
                $invoice = $this->generateInvoice($partner, $periodStart, $periodEnd);
                $invoices->push($invoice);

                // Update next billing date
                $partner->update([
                    'next_billing_date' => $this->calculateNextBillingDate($partner),
                ]);
            } catch (Exception $e) {
                Log::error('Failed to generate invoice for partner', [
                    'partner_id' => $partner->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return $invoices;
    }

    /**
     * Get total outstanding balance for a partner.
     */
    public function getOutstandingBalance(FinancialInstitutionPartner $partner): float
    {
        return (float) PartnerInvoice::where('partner_id', $partner->id)
            ->pending()
            ->sum('total_amount_usd');
    }

    /**
     * Calculate billing cycle discount amount.
     */
    public function applyBillingCycleDiscount(float $baseAmount, string $billingCycle): float
    {
        $discountPercentage = match ($billingCycle) {
            'quarterly' => (float) config('baas.billing.quarterly_discount_percentage', 5),
            'annually'  => (float) config('baas.billing.annual_discount_percentage', 15),
            default     => 0.0,
        };

        return round($baseAmount * ($discountPercentage / 100), 2);
    }

    /**
     * Calculate the period start date based on billing cycle.
     */
    private function calculatePeriodStart(FinancialInstitutionPartner $partner, Carbon $periodEnd): Carbon
    {
        $billingCycle = $partner->billing_cycle ?? 'monthly';

        return match ($billingCycle) {
            'quarterly' => $periodEnd->copy()->subMonths(3)->addDay(),
            'annually'  => $periodEnd->copy()->subYear()->addDay(),
            default     => $periodEnd->copy()->subMonth()->addDay(),
        };
    }

    /**
     * Calculate the next billing date.
     */
    private function calculateNextBillingDate(FinancialInstitutionPartner $partner): string
    {
        $billingCycle = $partner->billing_cycle ?? 'monthly';

        return match ($billingCycle) {
            'quarterly' => now()->addMonths(3)->toDateString(),
            'annually'  => now()->addYear()->toDateString(),
            default     => now()->addMonth()->toDateString(),
        };
    }
}
