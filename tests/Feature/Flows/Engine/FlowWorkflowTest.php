<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowWorkflow;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Operations\NoOpActivity;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;

beforeEach(function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    $registry->register('noop', 'No-op', NoOpActivity::class);
});

it('walks a two-node DAG and writes step rows', function () {
    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'op_a', 'type' => 'operation', 'data' => ['key' => 'a', 'operationType' => 'noop', 'config' => ['v' => 1]]],
                ['id' => 'op_b', 'type' => 'operation', 'data' => ['key' => 'b', 'operationType' => 'noop', 'config' => ['v' => 2]]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger', 'target' => 'op_a', 'sourceHandle' => 'success'],
                ['id' => 'e2', 'source' => 'op_a', 'target' => 'op_b', 'sourceHandle' => 'success'],
            ],
        ],
    ]);
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Pending,
    ]);

    app(FlowWorkflow::class)->run($run->id);

    $run = $run->fresh();
    expect($run->status)->toBe(FlowRunStatus::Completed);
    expect($run->started_at)->not->toBeNull();
    expect($run->finished_at)->not->toBeNull();
    expect($run->duration_ms)->toBeInt()->toBeGreaterThanOrEqual(0);

    $steps = $run->steps()->orderBy('started_at')->get();
    expect($steps)->toHaveCount(2);
    expect($steps[0]->operation_key)->toBe('a');
    expect($steps[0]->status)->toBe(FlowRunStepStatus::Completed);
    expect($steps[0]->output)->toBe(['v' => 1]);
    expect($steps[1]->operation_key)->toBe('b');
});

it('marks run as failed when an operation throws', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    $registry->register('boom', 'Boom', \Flexpik\FilamentStudio\Tests\Support\Flows\BoomActivity::class);

    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'op_a', 'type' => 'operation', 'data' => ['key' => 'a', 'operationType' => 'boom', 'config' => []]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger', 'target' => 'op_a', 'sourceHandle' => 'success'],
            ],
        ],
    ]);
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create(['flow_version_id' => $version->id]);

    app(FlowWorkflow::class)->run($run->id);

    expect($run->fresh()->status)->toBe(FlowRunStatus::Failed);
    expect($run->steps()->first()->status)->toBe(FlowRunStepStatus::Failed);
    expect($run->steps()->first()->error_message)->toContain('boom');
});
