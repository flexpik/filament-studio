<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

it('phase 1 smoke: register noop, build a published flow, dispatch sync, observe persisted run+steps', function () {

    $flow = StudioFlow::factory()->active()->create(['slug' => 'phase-1-smoke']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'op_a', 'type' => 'operation', 'data' => ['key' => 'a', 'operationType' => 'noop', 'config' => ['greeting' => 'hi {{ $trigger.name }}']]],
            ],
            'edges' => [['id' => 'e1', 'source' => 'trigger', 'target' => 'op_a', 'sourceHandle' => 'success']],
        ],
    ]);
    $flow->update(['published_version_id' => $version->id]);

    $run = app(FlowDispatcher::class)->dispatchSync(
        flow: $flow,
        triggerType: 'manual',
        payload: ['name' => 'Sera'],
        accountability: ['user_id' => null, 'tenant_id' => null, 'role' => null, 'source' => 'manual'],
    );

    expect($run->status)->toBe(FlowRunStatus::Completed);
    $step = $run->steps()->where('operation_key', 'a')->first();
    expect($step->output)->toBe(['greeting' => 'hi Sera']);
});
