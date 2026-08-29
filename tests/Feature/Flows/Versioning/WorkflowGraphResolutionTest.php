<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowWorkflow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;

it('uses inline_graph when flow_version_id is null', function () {
    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => null,
        'inline_graph' => ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []],
        'status' => 'pending',
    ]);

    app(FlowWorkflow::class)->run($run->id);

    expect($run->fresh()->status->value)->toBe('completed');
});

it('uses version graph when flow_version_id is set', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $flow->published_version_id,
        'inline_graph' => null,
        'status' => 'pending',
    ]);

    app(FlowWorkflow::class)->run($run->id);
    expect($run->fresh()->status->value)->toBe('completed');
});
