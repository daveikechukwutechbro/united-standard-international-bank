<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\FinancialInstitution\Models\FinancialInstitutionPartner;
use App\Domain\FinancialInstitution\Models\PartnerIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerIntegration>
 */
class PartnerIntegrationFactory extends Factory
{
    protected $model = PartnerIntegration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid'        => fake()->uuid(),
            'partner_id'  => FinancialInstitutionPartner::factory(),
            'category'    => fake()->randomElement(['payment_processors', 'identity_providers', 'kyc_providers', 'accounting', 'analytics']),
            'provider'    => fake()->randomElement(['stripe', 'okta', 'jumio', 'xero', 'mixpanel']),
            'status'      => 'pending',
            'config'      => ['api_key' => 'test_key_123'],
            'webhook_url' => fake()->url(),
            'error_count' => 0,
            'metadata'    => [],
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => 'disabled']);
    }
}
