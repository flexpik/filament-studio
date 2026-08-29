<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowWorkflow;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Operations\Logic\ConditionActivity;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Tests\Support\Flows\BoomActivity;
use Flexpik\FilamentStudio\Tests\Support\Flows\FlakyActivity;
use Flexpik\FilamentStudio\Tests\Support\Flows\SwitchPremiumActivity;

it('captures error_class and error_trace on failure', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    $registry->register('boom_class', 'Boom Class', BoomActivity::class);

    $flow = StudioFlow::factory()->create(['logging_mode' => 'full']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'op_a', 'type' => 'operation', 'data' => ['key' => 'a', 'operationType' => 'boom_class', 'config' => []]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger', 'target' => 'op_a', 'sourceHandle' => 'success'],
            ],
        ],
    ]);
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create(['flow_version_id' => $version->id]);

    app(FlowWorkflow::class)->run($run->id);

    $step = $run->steps()->first();
    expect($step->status)->toBe(FlowRunStepStatus::Failed);
    expect($step->error_class)->toBe(RuntimeException::class);
    expect($step->error_trace)->toContain('BoomActivity');
});

it('sets branch_taken for condition operations', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);

    if (! $registry->has('condition')) {
        $registry->register('condition', 'Condition', ConditionActivity::class);
    }

    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'cond', 'type' => 'operation', 'data' => [
                    'key' => 'cond',
                    'operationType' => 'condition',
                    'config' => ['filter' => ['logic' => 'and', 'rules' => []]],
                ]],
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

    $step = $run->steps()->where('operation_key', 'cond')->first();
    expect($step->status)->toBe(FlowRunStepStatus::Completed);
    expect($step->branch_taken)->toBe('success');
});

it('sets branch_taken for switch operations to the matched case key', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    $registry->register('switch_premium', 'Switch Premium', SwitchPremiumActivity::class);

    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'sw', 'type' => 'operation', 'data' => ['key' => 'sw', 'operationType' => 'switch_premium', 'config' => []]],
                ['id' => 'premium', 'type' => 'operation', 'data' => ['key' => 'premium', 'operationType' => 'noop', 'config' => []]],
                ['id' => 'other', 'type' => 'operation', 'data' => ['key' => 'other', 'operationType' => 'noop', 'config' => []]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger', 'target' => 'sw', 'sourceHandle' => 'success'],
                ['id' => 'e2', 'source' => 'sw', 'target' => 'premium', 'sourceHandle' => 'premium'],
                ['id' => 'e3', 'source' => 'sw', 'target' => 'other', 'sourceHandle' => 'other'],
            ],
        ],
    ]);
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Pending,
    ]);

    app(FlowWorkflow::class)->run($run->id);

    $step = $run->steps()->where('operation_key', 'sw')->first();
    expect($step->status)->toBe(FlowRunStepStatus::Completed);
    expect($step->branch_taken)->toBe('premium');
});

it('records attempt_number on retried steps', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    $registry->register('flaky', 'Flaky', FlakyActivity::class);

    FlakyActivity::reset(failTimes: 2);

    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'op_f', 'type' => 'operation', 'data' => [
                    'key' => 'f',
                    'operationType' => 'flaky',
                    'config' => ['retry_count' => 2],
                ]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger', 'target' => 'op_f', 'sourceHandle' => 'success'],
            ],
        ],
    ]);
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Pending,
    ]);

    app(FlowWorkflow::class)->run($run->id);

    expect($run->fresh()->status)->toBe(FlowRunStatus::Completed);

    $steps = $run->steps()->orderBy('attempt_number')->get();
    expect($steps)->toHaveCount(3);
    expect($steps[0]->attempt_number)->toBe(1);
    expect($steps[0]->status)->toBe(FlowRunStepStatus::Failed);
    expect($steps[1]->attempt_number)->toBe(2);
    expect($steps[1]->status)->toBe(FlowRunStepStatus::Failed);
    expect($steps[2]->attempt_number)->toBe(3);
    expect($steps[2]->status)->toBe(FlowRunStepStatus::Completed);
});
