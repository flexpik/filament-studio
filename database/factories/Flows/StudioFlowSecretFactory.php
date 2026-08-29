<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Database\Factories\Flows;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowSecret;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioFlowSecret>
 */
class StudioFlowSecretFactory extends Factory
{
    protected $model = StudioFlowSecret::class;

    public function definition(): array
    {
        return [
            'flow_id' => StudioFlow::factory(),
            'key' => $this->faker->unique()->slug(2),
            'value' => $this->faker->sha256(),
        ];
    }
}
