<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Database\Factories\Flows;

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioFlowRun>
 */
class StudioFlowRunFactory extends Factory
{
    protected $model = StudioFlowRun::class;

    public function definition(): array
    {
        return [
            'flow_id' => StudioFlow::factory(),
            'flow_version_id' => StudioFlowVersion::factory(),
            'status' => FlowRunStatus::Pending,
            'trigger_type' => 'manual',
            'trigger_payload' => [],
            'accountability' => ['user_id' => null, 'tenant_id' => null, 'role' => null, 'source' => 'manual'],
            'started_at' => null,
            'finished_at' => null,
            'duration_ms' => null,
        ];
    }
}
