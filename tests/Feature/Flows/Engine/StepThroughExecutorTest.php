<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Engine\FlowContext;
use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Engine\StepThroughExecutor;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Operations\NoOpActivity;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Facades\Cache;

// ---------------------------------------------------------------------------
// Helpers (reuse the same pattern as DryRunModeTest)
// ---------------------------------------------------------------------------

function stepFlowWithGraph(array $nodes, array $edges = []): StudioFlowRun
{
    $flow = StudioFlow::factory()->create(['logging_mode' => 'full']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => $nodes, 'edges' => $edges],
    ]);
    $flow->update(['published_version_id' => $version->id]);

    return StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $version->id,
    ]);
}

function stepTriggerNode(): array
{
    return ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']];
}

function stepOpNode(string $id, string $type = 'noop', array $config = []): array
{
    return ['id' => $id, 'type' => 'operation', 'data' => ['key' => $id, 'operationType' => $type, 'config' => $config]];
}

function stepEdge(string $from, string $to): array
{
    return ['id' => "se_{$from}_{$to}", 'source' => $from, 'target' => $to, 'sourceHandle' => 'success'];
}

// ---------------------------------------------------------------------------
// Ensure noop is registered before each test
// ---------------------------------------------------------------------------

beforeEach(function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);

    if (! $registry->has('noop')) {
        $registry->register('noop', 'No-op', NoOpActivity::class);
    }
});

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('pauses after the first operation and writes a resume token to cache', function () {
    $run = stepFlowWithGraph(
        [stepTriggerNode(), stepOpNode('op1'), stepOpNode('op2')],
        [stepEdge('trigger', 'op1'), stepEdge('op1', 'op2')],
    );

    $result = app(FlowDispatcher::class)->dispatchSync(
        $run->flow,
        'manual',
        [],
        [],
        options: ['step_through' => true, 'origin' => 'canvas'],
    );

    expect($result->status)->toBe(FlowRunStatus::Paused);
    expect(Cache::has("flow_step:{$result->id}"))->toBeTrue();
    expect($result->steps()->count())->toBe(1);
});

it('advances exactly one step on POST /runs/{id}/step', function () {
    StudioFlowsApiRouteRegistrar::register();

    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);

    $run = stepFlowWithGraph(
        [stepTriggerNode(), stepOpNode('op1'), stepOpNode('op2')],
        [stepEdge('trigger', 'op1'), stepEdge('op1', 'op2')],
    );

    // Start paused (op1 done, op2 queued)
    $paused = app(FlowDispatcher::class)->dispatchSync(
        $run->flow,
        'manual',
        [],
        [],
        options: ['step_through' => true, 'origin' => 'canvas'],
    );

    expect($paused->status)->toBe(FlowRunStatus::Paused);
    expect($paused->steps()->count())->toBe(1);

    // Advance one step (op2 executes)
    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$paused->id}/step")
        ->assertOk();

    expect($paused->fresh()->steps()->count())->toBe(2);
    // No more ops — should be completed now
    expect($paused->fresh()->status)->toBe(FlowRunStatus::Completed);
});

it('completes the run after the last step', function () {
    $run = stepFlowWithGraph(
        [stepTriggerNode(), stepOpNode('only')],
        [stepEdge('trigger', 'only')],
    );

    $result = app(FlowDispatcher::class)->dispatchSync(
        $run->flow,
        'manual',
        [],
        [],
        options: ['step_through' => true, 'origin' => 'canvas'],
    );

    // Only one op — queue is empty after first step
    expect($result->status)->toBe(FlowRunStatus::Completed);
    expect(Cache::has("flow_step:{$result->id}"))->toBeFalse();
    expect($result->steps()->count())->toBe(1);
});

it('remains paused when more ops remain after advancing', function () {
    // 3-op flow: trigger → op1 → op2 → op3
    $run = stepFlowWithGraph(
        [stepTriggerNode(), stepOpNode('op1'), stepOpNode('op2'), stepOpNode('op3')],
        [stepEdge('trigger', 'op1'), stepEdge('op1', 'op2'), stepEdge('op2', 'op3')],
    );

    $paused = app(FlowDispatcher::class)->dispatchSync(
        $run->flow,
        'manual',
        [],
        [],
        options: ['step_through' => true, 'origin' => 'canvas'],
    );

    expect($paused->status)->toBe(FlowRunStatus::Paused);
    expect($paused->steps()->count())->toBe(1);

    // Advance once — op2 runs, op3 still queued
    app(StepThroughExecutor::class)->advance($paused);

    expect($paused->fresh()->status)->toBe(FlowRunStatus::Paused);
    expect($paused->fresh()->steps()->count())->toBe(2);
    expect(Cache::has("flow_step:{$paused->id}"))->toBeTrue();
});

it('refuses step_through when origin is not canvas', function () {
    $run = stepFlowWithGraph([stepTriggerNode()]);

    expect(fn () => app(FlowDispatcher::class)->dispatchSync(
        $run->flow,
        'manual',
        [],
        [],
        options: ['step_through' => true, 'origin' => 'webhook'],
    ))->toThrow(InvalidArgumentException::class, 'step_through is only allowed for canvas origin');
});

it('aborts on POST /runs/{id}/step with action=abort', function () {
    StudioFlowsApiRouteRegistrar::register();

    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);

    $run = stepFlowWithGraph(
        [stepTriggerNode(), stepOpNode('op1'), stepOpNode('op2')],
        [stepEdge('trigger', 'op1'), stepEdge('op1', 'op2')],
    );

    $paused = app(FlowDispatcher::class)->dispatchSync(
        $run->flow,
        'manual',
        [],
        [],
        options: ['step_through' => true, 'origin' => 'canvas'],
    );

    expect($paused->status)->toBe(FlowRunStatus::Paused);

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$paused->id}/step", ['action' => 'abort'])
        ->assertOk();

    expect($paused->fresh()->status)->toBe(FlowRunStatus::Aborted);
    expect(Cache::has("flow_step:{$paused->id}"))->toBeFalse();
});

it('returns 409 when stepping a completed run', function () {
    StudioFlowsApiRouteRegistrar::register();

    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);

    $run = stepFlowWithGraph(
        [stepTriggerNode(), stepOpNode('only')],
        [stepEdge('trigger', 'only')],
    );

    // Single-op flow — completes immediately on first dispatch
    $completed = app(FlowDispatcher::class)->dispatchSync(
        $run->flow,
        'manual',
        [],
        [],
        options: ['step_through' => true, 'origin' => 'canvas'],
    );

    expect($completed->status)->toBe(FlowRunStatus::Completed);

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$completed->id}/step")
        ->assertStatus(409)
        ->assertJson(['error' => 'run_not_paused']);
});

it('returns 409 when stepping a cancelled run', function () {
    StudioFlowsApiRouteRegistrar::register();

    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);

    $run = stepFlowWithGraph(
        [stepTriggerNode(), stepOpNode('op1'), stepOpNode('op2')],
        [stepEdge('trigger', 'op1'), stepEdge('op1', 'op2')],
    );

    $paused = app(FlowDispatcher::class)->dispatchSync(
        $run->flow,
        'manual',
        [],
        [],
        options: ['step_through' => true, 'origin' => 'canvas'],
    );

    $paused->forceFill(['status' => FlowRunStatus::Cancelled, 'finished_at' => now()])->save();

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$paused->id}/step")
        ->assertStatus(409)
        ->assertJson(['error' => 'run_not_paused']);
});

it('Paused status is not terminal', function () {
    expect(FlowRunStatus::Paused->isTerminal())->toBeFalse();
});

it('Aborted status is terminal', function () {
    expect(FlowRunStatus::Aborted->isTerminal())->toBeTrue();
});

it('FlowContext toCache and fromCache round-trips context state', function () {
    $ctx = FlowContext::make(
        trigger: ['foo' => 'bar'],
        accountability: ['user_id' => 42],
        secrets: ['key' => 'secret'],
        dryRun: true,
    );
    $ctx->set('op1', ['result' => 'value']);

    $cached = $ctx->toCache();
    $restored = FlowContext::fromCache($cached);

    expect($restored->trigger())->toBe(['foo' => 'bar'])
        ->and($restored->accountability())->toBe(['user_id' => 42])
        ->and($restored->isDryRun())->toBeTrue()
        ->and($restored->get('op1'))->toBe(['result' => 'value'])
        ->and($restored->last())->toBe(['result' => 'value']);
});

it('FlowContext fromCache restores last to the value at mid-point, not the final output key', function () {
    // Simulate a context that was mid-run: op1 and op2 completed, last = op1's output
    // (as if op2 had been run but last was explicitly set from op1 before caching)
    $ctx = FlowContext::make(trigger: [], accountability: []);
    $ctx->set('op1', 'first');
    $ctx->set('op2', 'second');

    // Manually craft the cache payload so last differs from the final set() value
    $cachePayload = $ctx->toCache();
    $cachePayload['last'] = 'first'; // override: last was op1's output at pause point

    $restored = FlowContext::fromCache($cachePayload);

    // last() must reflect the stored last, not whatever set() wrote during restore
    expect($restored->last())->toBe('first')
        ->and($restored->get('op1'))->toBe('first')
        ->and($restored->get('op2'))->toBe('second');
});
