<?php

declare(strict_types=1);

namespace App\Domain\RegTech\Services;

use App\Domain\RegTech\Enums\Jurisdiction;
use Throwable;

/**
 * MiFID II transaction reporting service.
 *
 * Handles transaction reporting requirements under RTS 25,
 * best execution analysis (RTS 27/28), and instrument reference data.
 */
class MifidReportingService
{
    public function __construct(
        private readonly JurisdictionConfigurationService $jurisdictionService,
        private readonly RegTechOrchestrationService $orchestrationService
    ) {
    }

    /**
     * Check if MiFID II reporting is enabled.
     */
    public function isEnabled(): bool
    {
        $config = $this->jurisdictionService->getMifidConfig();

        return (bool) ($config['enabled'] ?? true);
    }

    /**
     * Determine if a transaction requires MiFID II reporting.
     *
     * @param  array<string, mixed>  $transaction
     */
    public function requiresReporting(array $transaction): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $jurisdiction = $this->resolveJurisdiction($transaction);

        if (! $jurisdiction) {
            return false;
        }

        return $this->jurisdictionService->isMifidApplicable($jurisdiction);
    }

    /**
     * Generate a MiFID II transaction report.
     *
     * @param  array<string, mixed>  $transaction
     * @return array{success: bool, report: array<string, mixed>, errors: array<string>}
     */
    public function generateTransactionReport(array $transaction): array
    {
        $errors = $this->validateTransactionData($transaction);

        if ($errors !== []) {
            return [
                'success' => false,
                'report'  => [],
                'errors'  => $errors,
            ];
        }

        $jurisdiction = $this->resolveJurisdiction($transaction);

        $report = [
            'report_type'            => 'MiFID_Transaction',
            'reporting_entity'       => $transaction['executing_entity_id'] ?? '',
            'instrument_id'          => $transaction['instrument_id'] ?? '',
            'transaction_reference'  => $transaction['transaction_reference'] ?? '',
            'trading_date_time'      => $transaction['trading_date_time'] ?? now()->toIso8601String(),
            'quantity'               => (float) ($transaction['quantity'] ?? 0),
            'price'                  => (float) ($transaction['price'] ?? 0),
            'price_currency'         => $transaction['price_currency'] ?? ($jurisdiction?->currency() ?? 'EUR'),
            'venue'                  => $transaction['venue'] ?? 'XOFF',
            'buyer_id'               => $transaction['buyer_id'] ?? null,
            'seller_id'              => $transaction['seller_id'] ?? null,
            'buyer_decision_maker'   => $transaction['buyer_decision_maker'] ?? null,
            'seller_decision_maker'  => $transaction['seller_decision_maker'] ?? null,
            'transmission_indicator' => $transaction['transmission_indicator'] ?? false,
            'jurisdiction'           => $jurisdiction !== null ? $jurisdiction->value : 'EU',
            'reporting_deadline'     => $this->calculateReportingDeadline($transaction),
            'generated_at'           => now()->toIso8601String(),
        ];

        return [
            'success' => true,
            'report'  => $report,
            'errors'  => [],
        ];
    }

    /**
     * Submit a MiFID II transaction report to the relevant authority.
     *
     * @param  array<string, mixed>  $transaction
     * @param  array<string, mixed>  $metadata
     * @return array{success: bool, reference: string|null, errors: array<string>, details: array<string, mixed>}
     */
    public function submitTransactionReport(array $transaction, array $metadata = []): array
    {
        $reportResult = $this->generateTransactionReport($transaction);

        if (! $reportResult['success']) {
            return [
                'success'   => false,
                'reference' => null,
                'errors'    => $reportResult['errors'],
                'details'   => [],
            ];
        }

        $jurisdiction = $this->resolveJurisdiction($transaction);
        $jurisdictionValue = $jurisdiction ?? Jurisdiction::EU;

        $reportData = array_merge($reportResult['report'], [
            'entity_name'    => $transaction['entity_name'] ?? 'FinAegis',
            'reporting_date' => now()->toDateString(),
        ]);

        return $this->orchestrationService->submitReport(
            'MiFID_Transaction',
            $jurisdictionValue,
            $reportData,
            $metadata
        );
    }

    /**
     * Get best execution analysis (RTS 27/28).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getBestExecutionAnalysis(array $filters = []): array
    {
        $config = $this->jurisdictionService->getMifidConfig();

        return [
            'rts27_enabled' => $config['best_execution_rts27'] ?? true,
            'rts28_enabled' => $config['best_execution_rts28'] ?? true,
            'arm_provider'  => $config['arm_provider'] ?? 'internal',
            'analysis'      => [
                'period'          => $filters['period'] ?? 'Q' . ceil(now()->month / 3) . ' ' . now()->year,
                'total_orders'    => 1247,
                'venue_breakdown' => [
                    ['venue' => 'XNAS', 'percentage' => 42.3, 'avg_latency_ms' => 12],
                    ['venue' => 'XNYS', 'percentage' => 31.5, 'avg_latency_ms' => 15],
                    ['venue' => 'BATS', 'percentage' => 18.7, 'avg_latency_ms' => 8],
                    ['venue' => 'XOFF', 'percentage' => 7.5, 'avg_latency_ms' => 0],
                ],
                'execution_quality' => [
                    'price_improvement_rate' => 0.68,
                    'fill_rate'              => 0.94,
                    'average_slippage_bps'   => 1.2,
                ],
                'demo_mode' => $this->orchestrationService->isDemoMode(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get instrument reference data status.
     *
     * @return array<string, mixed>
     */
    public function getInstrumentReferenceDataStatus(): array
    {
        $config = $this->jurisdictionService->getMifidConfig();

        return [
            'firds_enabled'     => $config['instrument_reference_data']['firds_enabled'] ?? true,
            'anna_dsb_enabled'  => $config['instrument_reference_data']['anna_dsb_enabled'] ?? true,
            'last_sync'         => now()->subHours(2)->toIso8601String(),
            'instruments_count' => 45832,
            'status'            => 'healthy',
            'demo_mode'         => $this->orchestrationService->isDemoMode(),
        ];
    }

    /**
     * Validate transaction data for MiFID II compliance.
     *
     * @param  array<string, mixed>  $transaction
     * @return array<string>
     */
    private function validateTransactionData(array $transaction): array
    {
        $errors = [];

        if (empty($transaction['instrument_id'])) {
            $errors[] = 'instrument_id (ISIN) is required.';
        }

        if (empty($transaction['executing_entity_id'])) {
            $errors[] = 'executing_entity_id (LEI) is required.';
        }

        if (! isset($transaction['quantity']) || (float) $transaction['quantity'] <= 0) {
            $errors[] = 'quantity must be a positive number.';
        }

        if (! isset($transaction['price']) || (float) $transaction['price'] <= 0) {
            $errors[] = 'price must be a positive number.';
        }

        return $errors;
    }

    /**
     * Resolve jurisdiction from transaction data.
     *
     * @param  array<string, mixed>  $transaction
     */
    private function resolveJurisdiction(array $transaction): ?Jurisdiction
    {
        if (! empty($transaction['jurisdiction'])) {
            return Jurisdiction::tryFrom($transaction['jurisdiction']);
        }

        $currency = $transaction['price_currency'] ?? $transaction['currency'] ?? null;

        if ($currency) {
            return $this->jurisdictionService->getJurisdictionByCurrency($currency);
        }

        return Jurisdiction::EU;
    }

    /**
     * Calculate reporting deadline (T+1 for MiFID II).
     *
     * @param  array<string, mixed>  $transaction
     */
    private function calculateReportingDeadline(array $transaction): string
    {
        $tradingDate = now();

        if (! empty($transaction['trading_date_time'])) {
            try {
                $tradingDate = \Carbon\Carbon::parse($transaction['trading_date_time']);
            } catch (Throwable) {
                // Use current date on parse failure
            }
        }

        return $tradingDate->addWeekday()->toIso8601String();
    }
}
