<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Support\Facades\Bus;

it('dispatchSync runs in-process and returns the run', function () {
    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']]], 'edges' => []],
    ]);
    $flow->update(['published_version_id' => $version->id]);

    $run = app(FlowDispatcher::class)->dispatchSync(
        flow: $flow,
        triggerType: 'manual',
        payload: ['x' => 1],
        accountability: ['source' => 'manual'],
    );

    expect($run->status)->toBe(FlowRunStatus::Completed);
});

it('dispatchAsync queues an ExecuteFlowJob', function () {
    Bus::fake();
    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create();
    $flow->update(['published_version_id' => $version->id]);

    $run = app(FlowDispatcher::class)->dispatchAsync(
        flow: $flow,
        triggerType: 'manual',
        payload: [],
        accountability: ['source' => 'manual'],
    );

    Bus::assertDispatched(ExecuteFlowJob::class, fn (ExecuteFlowJob $job) => $job->flowRunId === $run->id);
});

it('dispatchAsync throws when flow has no published version', function () {
    $flow = StudioFlow::factory()->create();
    app(FlowDispatcher::class)->dispatchAsync($flow, 'manual', [], []);
})->throws(RuntimeException::class, 'no_published_version');
