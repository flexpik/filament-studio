<?php

namespace Flexpik\FilamentStudio\Database\Factories;

use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioFieldOption>
 */
class StudioFieldOptionFactory extends Factory
{
    protected $model = StudioFieldOption::class;

    public function definition(): array
    {
        return [
            'field_id' => StudioField::factory()->state(['field_type' => 'select']),
            'tenant_id' => null,
            'value' => $this->faker->unique()->slug(1),
            'label' => $this->faker->word(),
            'color' => null,
            'icon' => null,
            'sort_order' => 0,
        ];
    }
}
