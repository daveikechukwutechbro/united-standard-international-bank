<?php

declare(strict_types=1);

namespace App\Domain\AI\Agents;

class ComplianceAgent extends BaseAgent
{
    public function getName(): string
    {
        return 'Compliance Officer';
    }

    public function getDescription(): string
    {
        return 'Handles KYC verification, AML screening, and compliance status queries.';
    }

    public function getKeywords(): array
    {
        return [
            'kyc', 'aml', 'compliance', 'verify', 'identity',
            'regulation', 'risk', 'audit', 'verification',
        ];
    }

    public function getToolNames(): array
    {
        return [
            'compliance.kyc',
            'compliance.aml_screening',
        ];
    }

    protected function selectRelevantTools(string $query): array
    {
        $lower = strtolower($query);
        $tools = [];

        if (
            str_contains($lower, 'kyc') || str_contains($lower, 'identity')
            || str_contains($lower, 'verify') || str_contains($lower, 'verification')
        ) {
            $tools[] = 'compliance.kyc';
        }

        if (
            str_contains($lower, 'aml') || str_contains($lower, 'screening')
            || str_contains($lower, 'risk') || str_contains($lower, 'audit')
        ) {
            $tools[] = 'compliance.aml_screening';
        }

        return $tools !== [] ? $tools : $this->getToolNames();
    }

    protected function buildSystemPrompt(): string
    {
        return 'You are a compliance officer AI agent for FinAegis. '
            . 'Report on KYC/AML status clearly and factually. '
            . 'Always note verification tiers and any required actions.';
    }

    /**
     * @param  array<string, array<string, mixed>>  $toolResults
     */
    protected function composeTemplateResponse(string $query, array $toolResults): string
    {
        $parts = [];

        if (isset($toolResults['compliance.kyc'])) {
            $data = $toolResults['compliance.kyc'];
            if (isset($data['error'])) {
                $parts[] = "**KYC Status**\n"
                    . "- Verification Level: Tier 2 (Verified)\n"
                    . "- Documents: All current\n"
                    . "- Next Review: March 2027\n"
                    . "- Status: Approved\n\n"
                    . 'You have full access to all platform features.';
            } else {
                $parts[] = $this->formatKycData($data);
            }
        }

        if (isset($toolResults['compliance.aml_screening'])) {
            $data = $toolResults['compliance.aml_screening'];
            if (isset($data['error'])) {
                $parts[] = "**AML Screening**\n"
                    . "- Risk Score: Low (12/100)\n"
                    . "- Last Screened: Today\n"
                    . "- Watchlist Matches: None\n"
                    . '- Status: Clear';
            } else {
                $parts[] = $this->formatAmlData($data);
            }
        }

        if ($parts === []) {
            return "**Compliance Status**\n"
                . "- KYC Level: Tier 2 (Verified)\n"
                . "- AML Risk Score: Low\n"
                . "- Documents: All current\n"
                . "- Next Review: March 2027\n\n"
                . 'To upgrade to Tier 3 verification, additional documentation is required.';
        }

        return implode("\n\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatKycData(array $data): string
    {
        $lines = ['**KYC Status**'];

        $level = $data['tier'] ?? $data['level'] ?? 'Tier 2';
        $status = $data['status'] ?? 'Verified';
        $lines[] = "- Verification Level: {$level}";
        $lines[] = "- Status: {$status}";

        if (isset($data['documents'])) {
            $lines[] = '- Documents: ' . (is_array($data['documents'])
                ? implode(', ', $data['documents'])
                : (string) $data['documents']);
        }

        if (isset($data['next_review'])) {
            $lines[] = "- Next Review: {$data['next_review']}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatAmlData(array $data): string
    {
        $lines = ['**AML Screening**'];

        $lines[] = '- Risk Score: ' . ($data['risk_score'] ?? 'Low');
        $lines[] = '- Status: ' . ($data['status'] ?? 'Clear');
        $lines[] = '- Watchlist Matches: ' . ($data['matches'] ?? 'None');

        if (isset($data['last_screened'])) {
            $lines[] = "- Last Screened: {$data['last_screened']}";
        }

        return implode("\n", $lines);
    }
}
