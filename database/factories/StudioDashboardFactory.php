<?php

namespace Flexpik\FilamentStudio\Database\Factories;

use Flexpik\FilamentStudio\Models\StudioDashboard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioDashboard>
 */
class StudioDashboardFactory extends Factory
{
    protected $model = StudioDashboard::class;

    public function definition(): array
    {
        return [
            'tenant_id' => $this->faker->numberBetween(1, 100),
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(2),
            'icon' => null,
            'color' => null,
            'auto_refresh_interval' => null,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }

    public function forTenant(int $tenantId): static
    {
        return $this->state(fn () => ['tenant_id' => $tenantId]);
    }
}
