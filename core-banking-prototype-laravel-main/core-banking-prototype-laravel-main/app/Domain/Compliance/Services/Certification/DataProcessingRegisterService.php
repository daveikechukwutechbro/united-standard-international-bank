<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Services\Certification;

use App\Domain\Compliance\Models\ProcessingActivity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * GDPR Article 30 — Records of Processing Activities (ROPA).
 */
class DataProcessingRegisterService
{
    /**
     * Get all processing activities.
     *
     * @return Collection<int, ProcessingActivity>
     */
    public function getActivities(?string $status = null): Collection
    {
        $query = ProcessingActivity::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Create a new processing activity.
     *
     * @param  array<string, mixed>  $data
     */
    public function createActivity(array $data): ProcessingActivity
    {
        return ProcessingActivity::create(array_merge($data, [
            'controller_name'    => $data['controller_name'] ?? Config::get('app.name'),
            'controller_contact' => $data['controller_contact'] ?? Config::get('compliance-certification.gdpr.dpo_email'),
            'dpo_contact'        => $data['dpo_contact'] ?? Config::get('compliance-certification.gdpr.dpo_email'),
        ]));
    }

    /**
     * Update an existing processing activity.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateActivity(string $id, array $data): ProcessingActivity
    {
        $activity = ProcessingActivity::findOrFail($id);
        $activity->update($data);

        return $activity->refresh();
    }

    /**
     * Export the register in the configured format.
     *
     * @return array<string, mixed>
     */
    public function exportRegister(): array
    {
        $activities = ProcessingActivity::all();

        return [
            'register_name'    => 'GDPR Article 30 — Records of Processing Activities',
            'generated_at'     => now()->toIso8601String(),
            'controller'       => Config::get('app.name', 'FinAegis'),
            'dpo_contact'      => Config::get('compliance-certification.gdpr.dpo_email'),
            'total_activities' => $activities->count(),
            'activities'       => $activities->map(fn (ProcessingActivity $a) => [
                'name'                    => $a->name,
                'purpose'                 => $a->purpose,
                'legal_basis'             => $a->legal_basis,
                'data_categories'         => $a->data_categories,
                'data_subjects'           => $a->data_subjects,
                'recipients'              => $a->recipients,
                'retention_period'        => $a->retention_period,
                'international_transfers' => $a->international_transfers,
                'security_measures'       => $a->security_measures,
                'status'                  => $a->status,
            ])->toArray(),
        ];
    }

    /**
     * Check register completeness.
     *
     * @return array<string, mixed>
     */
    public function checkCompleteness(): array
    {
        $activities = ProcessingActivity::all();
        $complete = $activities->filter(fn (ProcessingActivity $a) => $a->isComplete())->count();
        $incomplete = $activities->count() - $complete;

        return [
            'total'             => $activities->count(),
            'complete'          => $complete,
            'incomplete'        => $incomplete,
            'completeness_rate' => $activities->count() > 0
                ? round(($complete / $activities->count()) * 100, 1)
                : 0,
            'by_legal_basis' => $activities->groupBy('legal_basis')->map->count()->toArray(),
            'by_status'      => $activities->groupBy('status')->map->count()->toArray(),
        ];
    }

    /**
     * Get demo register data.
     *
     * @return array<string, mixed>
     */
    public function getDemoRegister(): array
    {
        return [
            'register_name'    => 'GDPR Article 30 — Records of Processing Activities',
            'generated_at'     => now()->toIso8601String(),
            'controller'       => 'FinAegis',
            'dpo_contact'      => 'dpo@finaegis.org',
            'total_activities' => 5,
            'activities'       => [
                ['name' => 'Customer Onboarding', 'purpose' => 'Account creation and identity verification', 'legal_basis' => 'contract', 'status' => 'active'],
                ['name' => 'Transaction Processing', 'purpose' => 'Execute financial transactions', 'legal_basis' => 'contract', 'status' => 'active'],
                ['name' => 'KYC/AML Screening', 'purpose' => 'Regulatory compliance verification', 'legal_basis' => 'legal_obligation', 'status' => 'active'],
                ['name' => 'Marketing Communications', 'purpose' => 'Promotional emails and updates', 'legal_basis' => 'consent', 'status' => 'active'],
                ['name' => 'Analytics', 'purpose' => 'Service improvement analytics', 'legal_basis' => 'legitimate_interest', 'status' => 'active'],
            ],
            'completeness_rate' => 100.0,
        ];
    }
}
