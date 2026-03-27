<?php

namespace Flexpik\FilamentStudio\Database\Factories;

use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StudioApiKey>
 */
class StudioApiKeyFactory extends Factory
{
    protected $model = StudioApiKey::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' key',
            'key' => hash('sha256', Str::random(64)),
            'permissions' => [],
            'is_active' => true,
            'tenant_id' => null,
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withPermissions(array $permissions): static
    {
        return $this->state(['permissions' => $permissions]);
    }

    public function fullAccess(): static
    {
        return $this->state([
            'permissions' => [
                '*' => ['index', 'show', 'store', 'update', 'destroy'],
            ],
        ]);
    }

    public function forTenant(int $tenantId): static
    {
        return $this->state(fn () => ['tenant_id' => $tenantId]);
    }
}
