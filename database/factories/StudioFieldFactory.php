<?php

namespace Flexpik\FilamentStudio\Database\Factories;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioField>
 */
class StudioFieldFactory extends Factory
{
    protected $model = StudioField::class;

    public function definition(): array
    {
        return [
            'collection_id' => StudioCollection::factory(),
            'tenant_id' => null,
            'column_name' => $this->faker->unique()->slug(1),
            'label' => $this->faker->words(2, true),
            'field_type' => 'text',
            'eav_cast' => 'text',
            'is_required' => false,
            'is_unique' => false,
            'is_nullable' => true,
            'is_indexed' => false,
            'is_system' => false,
            'sort_order' => 0,
            'width' => 'full',
        ];
    }

    public function required(): static
    {
        return $this->state(fn () => ['is_required' => true]);
    }

    public function system(): static
    {
        return $this->state(fn () => ['is_system' => true]);
    }

    public function ofType(string $fieldType, string $eavCast = 'text'): static
    {
        return $this->state(fn () => [
            'field_type' => $fieldType,
            'eav_cast' => $eavCast,
        ]);
    }
}
