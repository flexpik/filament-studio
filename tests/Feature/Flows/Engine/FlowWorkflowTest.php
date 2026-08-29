<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowWorkflow;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Tests\Support\Flows\BoomActivity;
use Flexpik\FilamentStudio\Tests\Support\Flows\FailingBranchActivity;

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
    $registry->register('boom', 'Boom', BoomActivity::class);

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

it('routes to success branch when operation returns result=true (noop returns config)', function () {
    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'cond', 'type' => 'operation', 'data' => ['key' => 'cond', 'operationType' => 'noop', 'config' => []]],
                ['id' => 'yes', 'type' => 'operation', 'data' => ['key' => 'yes', 'operationType' => 'noop', 'config' => []]],
                ['id' => 'no', 'type' => 'operation', 'data' => ['key' => 'no', 'operationType' => 'noop', 'config' => []]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger', 'target' => 'cond', 'sourceHandle' => 'success'],
                ['id' => 'e2', 'source' => 'cond', 'target' => 'yes', 'sourceHandle' => 'success'],
                ['id' => 'e3', 'source' => 'cond', 'target' => 'no', 'sourceHandle' => 'failure'],
            ],
        ],
    ]);
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Pending,
    ]);

    app(FlowWorkflow::class)->run($run->id);

    // noop returns [] (config) — no 'result' key, defaults to success branch
    expect($run->steps()->where('operation_key', 'yes')->exists())->toBeTrue();
    expect($run->steps()->where('operation_key', 'no')->exists())->toBeFalse();
    expect($run->fresh()->status)->toBe(FlowRunStatus::Completed);
});

it('routes to failure branch when operation returns result=false', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    $registry->register('branch_fail', 'Branch Fail', FailingBranchActivity::class);

    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'cond', 'type' => 'operation', 'data' => ['key' => 'cond', 'operationType' => 'branch_fail', 'config' => []]],
                ['id' => 'yes', 'type' => 'operation', 'data' => ['key' => 'yes', 'operationType' => 'noop', 'config' => []]],
                ['id' => 'no', 'type' => 'operation', 'data' => ['key' => 'no', 'operationType' => 'noop', 'config' => []]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger', 'target' => 'cond', 'sourceHandle' => 'success'],
                ['id' => 'e2', 'source' => 'cond', 'target' => 'yes', 'sourceHandle' => 'success'],
                ['id' => 'e3', 'source' => 'cond', 'target' => 'no', 'sourceHandle' => 'failure'],
            ],
        ],
    ]);
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Pending,
    ]);

    app(FlowWorkflow::class)->run($run->id);

    // branch_fail returns ['result' => false] -> failure branch -> 'no' runs, 'yes' does not
    expect($run->steps()->where('operation_key', 'no')->exists())->toBeTrue();
    expect($run->steps()->where('operation_key', 'yes')->exists())->toBeFalse();
    expect($run->fresh()->status)->toBe(FlowRunStatus::Completed);
});

it('deduplicates nodes in a diamond fan-join pattern', function () {
    // A diamond: trigger -> left -> join, trigger -> right -> join
    // Without deduplication, 'join' would execute twice
    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'left', 'type' => 'operation', 'data' => ['key' => 'left', 'operationType' => 'noop', 'config' => []]],
                ['id' => 'right', 'type' => 'operation', 'data' => ['key' => 'right', 'operationType' => 'noop', 'config' => []]],
                ['id' => 'join', 'type' => 'operation', 'data' => ['key' => 'join', 'operationType' => 'noop', 'config' => []]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger', 'target' => 'left', 'sourceHandle' => 'success'],
                ['id' => 'e2', 'source' => 'trigger', 'target' => 'right', 'sourceHandle' => 'success'],
                ['id' => 'e3', 'source' => 'left', 'target' => 'join', 'sourceHandle' => 'success'],
                ['id' => 'e4', 'source' => 'right', 'target' => 'join', 'sourceHandle' => 'success'],
            ],
        ],
    ]);
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Pending,
    ]);

    app(FlowWorkflow::class)->run($run->id);

    // 'join' must appear exactly once despite two paths converging on it
    expect($run->steps()->where('operation_key', 'join')->count())->toBe(1);
    expect($run->fresh()->status)->toBe(FlowRunStatus::Completed);
});
