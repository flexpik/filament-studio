<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Database\Factories\Flows;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioFlowVersion>
 */
class StudioFlowVersionFactory extends Factory
{
    protected $model = StudioFlowVersion::class;

    public function definition(): array
    {
        return [
            'flow_id' => StudioFlow::factory(),
            'version' => 1,
            'graph' => ['nodes' => [], 'edges' => []],
            'published_at' => null,
            'change_summary' => null,
            'created_at' => now(),
        ];
    }

    public function published(?string $publishedBy = null): self
    {
        return $this->state([
            'published_at' => now(),
            'published_by' => $publishedBy,
        ]);
    }
}
