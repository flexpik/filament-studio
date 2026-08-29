<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Database\Factories\Flows;

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioFlowRunStep>
 */
class StudioFlowRunStepFactory extends Factory
{
    protected $model = StudioFlowRunStep::class;

    public function definition(): array
    {
        return [
            'flow_run_id' => StudioFlowRun::factory(),
            'operation_key' => 'op_'.$this->faker->randomNumber(3),
            'operation_type' => 'log_message',
            'attempt_number' => 1,
            'status' => FlowRunStepStatus::Pending,
            'input' => null,
            'output' => null,
            'error_message' => null,
            'error_trace' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
