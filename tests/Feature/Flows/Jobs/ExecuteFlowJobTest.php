<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowWorkflow;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

it('runs the workflow synchronously when handled', function () {
    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'op_a', 'type' => 'operation', 'data' => ['key' => 'a', 'operationType' => 'noop', 'config' => []]],
            ],
            'edges' => [['id' => 'e1', 'source' => 'trigger', 'target' => 'op_a', 'sourceHandle' => 'success']],
        ],
    ]);
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create(['flow_version_id' => $version->id]);

    (new ExecuteFlowJob($run->id))->handle(app(FlowWorkflow::class));

    expect($run->fresh()->status)->toBe(FlowRunStatus::Completed);
});
