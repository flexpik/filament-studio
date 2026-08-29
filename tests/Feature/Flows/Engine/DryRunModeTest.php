<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowContext;
use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Operations\Communication\HttpRequestActivity;
use Flexpik\FilamentStudio\Flows\Operations\NoOpActivity;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Operations\Records\CreateRecordActivity;
use Flexpik\FilamentStudio\Flows\Operations\Records\DeleteRecordActivity;
use Flexpik\FilamentStudio\Flows\Operations\Records\UpdateRecordActivity;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Support\Facades\Http;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeFlowWithGraph(array $nodes, array $edges = []): StudioFlowRun
{
    $flow = StudioFlow::factory()->create(['logging_mode' => 'full']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => $nodes, 'edges' => $edges],
    ]);

    return StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => $version->id,
    ]);
}

function triggerNode(): array
{
    return ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']];
}

function opNode(string $id, string $type, array $config = []): array
{
    return ['id' => $id, 'type' => 'operation', 'data' => ['key' => $id, 'operationType' => $type, 'config' => $config]];
}

function edge(string $from, string $to): array
{
    return ['id' => "e_{$from}_{$to}", 'source' => $from, 'target' => $to, 'sourceHandle' => 'success'];
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('marks the run with dry_run=true when dispatched with dry_run option', function () {
    $run = makeFlowWithGraph([triggerNode()]);
    $flow = $run->flow;
    $flow->update(['published_version_id' => $run->flow_version_id]);

    $dispatcher = app(FlowDispatcher::class);
    $result = $dispatcher->dispatchSync($flow, 'manual', [], [], options: ['dry_run' => true]);

    expect($result->dry_run)->toBeTrue();
});

it('does not mark the run as dry_run when option is absent', function () {
    $run = makeFlowWithGraph([triggerNode()]);
    $flow = $run->flow;
    $flow->update(['published_version_id' => $run->flow_version_id]);

    $dispatcher = app(FlowDispatcher::class);
    $result = $dispatcher->dispatchSync($flow, 'manual', [], []);

    expect($result->dry_run)->toBeFalse();
});

it('skips side-effect operations (http_request) and logs dry-run message', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    if (! $registry->has('http_request')) {
        $registry->register('http_request', 'HTTP Request', HttpRequestActivity::class);
    }

    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $flow = StudioFlow::factory()->create(['logging_mode' => 'full']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                triggerNode(),
                opNode('req', 'http_request', ['url' => 'https://example.com', 'method' => 'GET']),
            ],
            'edges' => [edge('trigger', 'req')],
        ],
    ]);
    $flow->update(['published_version_id' => $version->id]);

    $dispatcher = app(FlowDispatcher::class);
    $result = $dispatcher->dispatchSync($flow, 'manual', [], [], options: ['dry_run' => true]);

    Http::assertNothingSent();

    $step = $result->steps()->where('operation_type', 'http_request')->first();
    expect($step)->not->toBeNull();
    expect($step->status)->toBe(FlowRunStepStatus::Skipped);
    expect($step->output['log'])->toContain('[dry-run] would have called http_request');
});

it('runs pure operations (noop) normally in dry-run mode', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    if (! $registry->has('noop')) {
        $registry->register('noop', 'No-op', NoOpActivity::class);
    }

    $flow = StudioFlow::factory()->create(['logging_mode' => 'full']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                triggerNode(),
                opNode('no', 'noop'),
            ],
            'edges' => [edge('trigger', 'no')],
        ],
    ]);
    $flow->update(['published_version_id' => $version->id]);

    $dispatcher = app(FlowDispatcher::class);
    $result = $dispatcher->dispatchSync($flow, 'manual', [], [], options: ['dry_run' => true]);

    $step = $result->steps()->where('operation_type', 'noop')->first();
    expect($step)->not->toBeNull();
    expect($step->status)->toBe(FlowRunStepStatus::Completed);
});

it('returns synthetic id for create_record without writing any record', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    if (! $registry->has('create_record')) {
        $registry->register('create_record', 'Create Record', CreateRecordActivity::class);
    }

    $flow = StudioFlow::factory()->create(['logging_mode' => 'full']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                triggerNode(),
                opNode('cr', 'create_record', ['collection_slug' => 'demo', 'values' => []]),
            ],
            'edges' => [edge('trigger', 'cr')],
        ],
    ]);
    $flow->update(['published_version_id' => $version->id]);

    $recordCount = StudioRecord::count();

    $dispatcher = app(FlowDispatcher::class);
    $result = $dispatcher->dispatchSync($flow, 'manual', [], [], options: ['dry_run' => true]);

    expect(StudioRecord::count())->toBe($recordCount);

    $step = $result->steps()->where('operation_type', 'create_record')->first();
    expect($step)->not->toBeNull();
    expect($step->status)->toBe(FlowRunStepStatus::Completed);
    expect($step->output['id'])->toStartWith('dry-run-');
});

it('returns synthetic result for update_record without touching database', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    if (! $registry->has('update_record')) {
        $registry->register('update_record', 'Update Record', UpdateRecordActivity::class);
    }

    $flow = StudioFlow::factory()->create(['logging_mode' => 'full']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                triggerNode(),
                opNode('ur', 'update_record', ['collection_slug' => 'demo', 'record_id' => 'some-uuid', 'values' => ['name' => 'Test']]),
            ],
            'edges' => [edge('trigger', 'ur')],
        ],
    ]);
    $flow->update(['published_version_id' => $version->id]);

    $dispatcher = app(FlowDispatcher::class);
    $result = $dispatcher->dispatchSync($flow, 'manual', [], [], options: ['dry_run' => true]);

    $step = $result->steps()->where('operation_type', 'update_record')->first();
    expect($step)->not->toBeNull();
    expect($step->status)->toBe(FlowRunStepStatus::Completed);
    expect($step->output['dry_run'])->toBeTrue();
});

it('returns synthetic result for delete_record without touching database', function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);
    if (! $registry->has('delete_record')) {
        $registry->register('delete_record', 'Delete Record', DeleteRecordActivity::class);
    }

    $flow = StudioFlow::factory()->create(['logging_mode' => 'full']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                triggerNode(),
                opNode('dr', 'delete_record', ['collection_slug' => 'demo', 'record_id' => 'target-uuid']),
            ],
            'edges' => [edge('trigger', 'dr')],
        ],
    ]);
    $flow->update(['published_version_id' => $version->id]);

    $dispatcher = app(FlowDispatcher::class);
    $result = $dispatcher->dispatchSync($flow, 'manual', [], [], options: ['dry_run' => true]);

    $step = $result->steps()->where('operation_type', 'delete_record')->first();
    expect($step)->not->toBeNull();
    expect($step->status)->toBe(FlowRunStepStatus::Completed);
    expect($step->output['deleted'])->toBeTrue();
    expect($step->output['dry_run'])->toBeTrue();
});

it('FlowContext::isDryRun returns true when constructed with dry_run=true', function () {
    $ctx = FlowContext::make(dryRun: true);
    expect($ctx->isDryRun())->toBeTrue();
});

it('FlowContext::isDryRun returns false by default', function () {
    $ctx = FlowContext::make();
    expect($ctx->isDryRun())->toBeFalse();
});
