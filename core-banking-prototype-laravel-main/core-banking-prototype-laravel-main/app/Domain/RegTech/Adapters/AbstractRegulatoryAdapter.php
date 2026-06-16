<?php

declare(strict_types=1);

namespace App\Domain\RegTech\Adapters;

use App\Domain\RegTech\Contracts\RegulatoryFilingAdapterInterface;
use App\Domain\RegTech\Enums\Jurisdiction;
use Illuminate\Support\Str;
use Throwable;

/**
 * Base adapter with shared demo/sandbox behaviour.
 * Concrete adapters override jurisdiction-specific details.
 */
abstract class AbstractRegulatoryAdapter implements RegulatoryFilingAdapterInterface
{
    protected bool $sandboxMode;

    protected string $apiEndpoint;

    public function __construct()
    {
        $regulator = strtolower($this->getRegulatorKey());

        $this->sandboxMode = true;
        $this->apiEndpoint = "https://sandbox.{$regulator}.demo/api/v1";

        try {
            $this->sandboxMode = (bool) config('regtech.demo_mode', true);
            $this->apiEndpoint = (string) config(
                "regtech.api_endpoints.{$regulator}.sandbox",
                $this->apiEndpoint
            );
        } catch (Throwable) {
            // Config not available outside Laravel context (e.g. unit tests)
        }
    }

    abstract protected function getRegulatorKey(): string;

    /**
     * @return array{success: bool, reference: string|null, errors: array<string>, response: array<string, mixed>}
     */
    public function submitReport(string $reportType, array $reportData, array $metadata = []): array
    {
        if (! in_array($reportType, $this->getSupportedReportTypes(), true)) {
            return [
                'success'   => false,
                'reference' => null,
                'errors'    => ["Unsupported report type: {$reportType}"],
                'response'  => [],
            ];
        }

        $validation = $this->validateReport($reportType, $reportData);
        if (! $validation['valid']) {
            return [
                'success'   => false,
                'reference' => null,
                'errors'    => $validation['errors'],
                'response'  => [],
            ];
        }

        // Demo/sandbox: generate a simulated reference
        $reference = strtoupper($this->getRegulatorKey()) . '-' . now()->format('Ymd') . '-' . Str::random(8);

        return [
            'success'   => true,
            'reference' => $reference,
            'errors'    => [],
            'response'  => [
                'submission_id'    => $reference,
                'submitted_at'     => now()->toIso8601String(),
                'jurisdiction'     => $this->getJurisdiction()->value,
                'report_type'      => $reportType,
                'sandbox'          => $this->sandboxMode,
                'estimated_review' => '24-48 hours',
            ],
        ];
    }

    /**
     * @return array{status: string, message: string, details: array<string, mixed>}
     */
    public function checkStatus(string $reference): array
    {
        // Demo mode: simulate processing status
        return [
            'status'  => 'accepted',
            'message' => "Report {$reference} has been accepted for processing.",
            'details' => [
                'reference'    => $reference,
                'jurisdiction' => $this->getJurisdiction()->value,
                'adapter'      => $this->getName(),
                'checked_at'   => now()->toIso8601String(),
                'sandbox'      => $this->sandboxMode,
            ],
        ];
    }

    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    public function isAvailable(): bool
    {
        return true; // Demo adapters are always available
    }

    public function isSandboxMode(): bool
    {
        return $this->sandboxMode;
    }

    /**
     * Validate common report fields shared across jurisdictions.
     *
     * @param  array<string, mixed>  $data
     * @return array<string>
     */
    protected function validateCommonFields(array $data): array
    {
        $errors = [];

        if (empty($data['entity_name'])) {
            $errors[] = 'entity_name is required.';
        }

        if (empty($data['reporting_date'])) {
            $errors[] = 'reporting_date is required.';
        }

        return $errors;
    }
}
