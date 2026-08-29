<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Operations\Composition\TriggerFlowActivity;
use Illuminate\Support\Facades\Bus;

it('dispatches the target flow async and returns flow_run_id', function () {
    Bus::fake();

    $target = StudioFlow::factory()->create();
    $targetVersion = StudioFlowVersion::factory()->for($target, 'flow')->published()->create();
    $target->update(['published_version_id' => $targetVersion->id]);

    $ctx = makeOperationContext(
        config: ['flow_id' => $target->id, 'mode' => 'async', 'payload' => ['x' => 1]],
        accountability: ['source' => 'flow'],
    );

    $result = (new TriggerFlowActivity(app(FlowDispatcher::class)))->execute($ctx);

    expect($result->output()['dispatched'])->toBeTrue();
    expect($result->output())->toHaveKey('flow_run_id');
});

it('runs sync when mode=sync', function () {
    $target = StudioFlow::factory()->create();
    $targetVersion = StudioFlowVersion::factory()->for($target, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']]], 'edges' => []],
    ]);
    $target->update(['published_version_id' => $targetVersion->id]);

    $ctx = makeOperationContext(
        config: ['flow_id' => $target->id, 'mode' => 'sync', 'payload' => []],
        accountability: ['source' => 'flow', '_call_depth' => 0],
    );

    $result = (new TriggerFlowActivity(app(FlowDispatcher::class)))->execute($ctx);

    expect($result->output()['status'])->toBe('completed');
});

it('refuses recursion past max_call_depth', function () {
    config()->set('filament-studio.flows.max_call_depth', 2);
    $target = StudioFlow::factory()->create();
    $targetVersion = StudioFlowVersion::factory()->for($target, 'flow')->published()->create();
    $target->update(['published_version_id' => $targetVersion->id]);

    $ctx = makeOperationContext(
        config: ['flow_id' => $target->id, 'mode' => 'sync', 'payload' => []],
        accountability: ['_call_depth' => 5],
    );

    $result = (new TriggerFlowActivity(app(FlowDispatcher::class)))->execute($ctx);

    expect($result->isFailure())->toBeTrue();
    expect($result->message())->toContain('depth');
});
