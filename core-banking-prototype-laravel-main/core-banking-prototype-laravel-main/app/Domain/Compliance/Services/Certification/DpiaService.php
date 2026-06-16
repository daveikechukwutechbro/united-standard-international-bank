<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\DataProtectionAssessment;
use Illuminate\Support\Collection;

/**
 * Data Protection Impact Assessment (DPIA) — GDPR Article 35.
 */
class DpiaService
{
    /**
     * Get all assessments with optional status filter.
     *
     * @return Collection<int, DataProtectionAssessment>
     */
    public function getAssessments(?string $status = null): Collection
    {
        $query = DataProtectionAssessment::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Create a new DPIA.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAssessment(array $data): DataProtectionAssessment
    {
        $riskScore = $this->calculateRiskScore($data['risks'] ?? []);

        return DataProtectionAssessment::create(array_merge($data, [
            'risk_score' => $riskScore,
            'status'     => 'draft',
        ]));
    }

    /**
     * Update an existing DPIA.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAssessment(string $id, array $data): DataProtectionAssessment
    {
        $assessment = DataProtectionAssessment::findOrFail($id);

        if (isset($data['risks'])) {
            $data['risk_score'] = $this->calculateRiskScore($data['risks']);
        }

        $assessment->update($data);

        return $assessment->refresh();
    }

    /**
     * Approve a DPIA.
     */
    public function approveAssessment(string $id, string $reviewer): DataProtectionAssessment
    {
        $assessment = DataProtectionAssessment::findOrFail($id);
        $assessment->update([
            'status'      => 'approved',
            'reviewer'    => $reviewer,
            'approved_at' => now(),
        ]);

        return $assessment->refresh();
    }

    /**
     * Get DPIA summary statistics.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $assessments = DataProtectionAssessment::all();

        return [
            'total'         => $assessments->count(),
            'by_status'     => $assessments->groupBy('status')->map->count()->toArray(),
            'high_risk'     => $assessments->where('risk_score', '>=', 70)->count(),
            'average_score' => $assessments->count() > 0
                ? round($assessments->avg('risk_score'), 1)
                : 0,
            'pending_review' => $assessments->where('status', 'draft')->count(),
        ];
    }

    /**
     * Calculate risk score from risk array.
     *
     * @param  array<int, array<string, mixed>>  $risks
     */
    private function calculateRiskScore(array $risks): int
    {
        if (empty($risks)) {
            return 0;
        }

        $severityWeights = [
            'critical' => 100,
            'high'     => 75,
            'medium'   => 50,
            'low'      => 25,
        ];

        $totalScore = 0;

        foreach ($risks as $risk) {
            $severity = $risk['severity'] ?? 'low';
            $likelihood = $risk['likelihood'] ?? 'low';
            $severityScore = $severityWeights[$severity] ?? 25;
            $likelihoodMultiplier = match ($likelihood) {
                'very_likely' => 1.0,
                'likely'      => 0.75,
                'possible'    => 0.5,
                'unlikely'    => 0.25,
                default       => 0.25,
            };
            $totalScore += $severityScore * $likelihoodMultiplier;
        }

        return min(100, (int) round($totalScore / count($risks)));
    }

    /**
     * Get demo DPIA data.
     *
     * @return array<string, mixed>
     */
    public function getDemoSummary(): array
    {
        return [
            'total'          => 3,
            'by_status'      => ['approved' => 2, 'draft' => 1],
            'high_risk'      => 0,
            'average_score'  => 42.0,
            'pending_review' => 1,
            'assessments'    => [
                ['title' => 'Customer Data Processing DPIA', 'risk_score' => 35, 'status' => 'approved'],
                ['title' => 'Cross-Border Transfer DPIA', 'risk_score' => 55, 'status' => 'approved'],
                ['title' => 'AI/ML Fraud Detection DPIA', 'risk_score' => 45, 'status' => 'draft'],
            ],
        ];
    }
}
