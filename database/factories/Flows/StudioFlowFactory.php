<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Database\Factories\Flows;

use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Enums\LoggingMode;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StudioFlow>
 */
class StudioFlowFactory extends Factory
{
    protected $model = StudioFlow::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name' => $this->faker->words(2, true),
            'slug' => Str::slug($this->faker->unique()->words(3, true)),
            'description' => $this->faker->sentence(),
            'icon' => null,
            'color' => null,
            'status' => FlowStatus::Inactive,
            'logging_mode' => LoggingMode::Full,
            'webhook_secret' => null,
            'webhook_auth_mode' => 'hmac',
        ];
    }

    public function active(): self
    {
        return $this->state(['status' => FlowStatus::Active]);
    }

    public function withPublishedVersion(array $graph = ['nodes' => [], 'edges' => []]): self
    {
        return $this->afterCreating(function (StudioFlow $flow) use ($graph) {
            $version = StudioFlowVersion::factory()
                ->for($flow, 'flow')
                ->published('factory')
                ->create(['version' => 1, 'graph' => $graph]);
            $flow->forceFill(['published_version_id' => $version->id])->save();
        });
    }
}
