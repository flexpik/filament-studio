<?php

namespace Flexpik\FilamentStudio\Database\Factories;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioCollection>
 */
class StudioCollectionFactory extends Factory
{
    protected $model = StudioCollection::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->slug(2);

        return [
            'tenant_id' => null,
            'name' => $name,
            'label' => str($name)->replace('-', ' ')->title()->toString(),
            'label_plural' => str($name)->replace('-', ' ')->title()->plural()->toString(),
            'slug' => $name,
            'icon' => 'heroicon-o-table-cells',
            'description' => $this->faker->optional()->sentence(),
            'is_singleton' => false,
            'is_hidden' => false,
            'api_enabled' => false,
            'sort_field' => null,
            'sort_direction' => 'asc',
            'enable_versioning' => false,
            'enable_soft_deletes' => false,
        ];
    }

    public function singleton(): static
    {
        return $this->state(fn () => ['is_singleton' => true]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_hidden' => true]);
    }

    public function withVersioning(): static
    {
        return $this->state(fn () => ['enable_versioning' => true]);
    }

    public function withSoftDeletes(): static
    {
        return $this->state(fn () => ['enable_soft_deletes' => true]);
    }

    public function apiEnabled(): static
    {
        return $this->state(fn () => ['api_enabled' => true]);
    }

    public function forTenant(int $tenantId): static
    {
        return $this->state(fn () => ['tenant_id' => $tenantId]);
    }
}
