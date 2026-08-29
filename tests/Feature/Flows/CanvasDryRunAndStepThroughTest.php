<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Operations\Communication\HttpRequestActivity;
use Flexpik\FilamentStudio\Flows\Operations\NoOpActivity;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Operations\Records\CreateRecordActivity;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Support\Facades\Http;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function canvasTriggerNode(): array
{
    return ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']];
}

function canvasOpNode(string $id, string $type, array $config = []): array
{
    return ['id' => $id, 'type' => 'operation', 'data' => ['key' => $id, 'operationType' => $type, 'config' => $config]];
}

function canvasEdge(string $from, string $to): array
{
    return ['id' => "ce_{$from}_{$to}", 'source' => $from, 'target' => $to, 'sourceHandle' => 'success'];
}

function makeCanvasFlow(array $nodes, array $edges = []): StudioFlow
{
    $flow = StudioFlow::factory()->create(['logging_mode' => 'full']);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => ['nodes' => $nodes, 'edges' => $edges],
    ]);
    $flow->update(['published_version_id' => $version->id]);

    return $flow->fresh();
}

// ---------------------------------------------------------------------------
// Ensure operations are registered before each test
// ---------------------------------------------------------------------------

beforeEach(function () {
    /** @var OperationRegistry $registry */
    $registry = app(OperationRegistry::class);

    if (! $registry->has('http_request')) {
        $registry->register('http_request', 'HTTP Request', HttpRequestActivity::class);
    }

    if (! $registry->has('create_record')) {
        $registry->register('create_record', 'Create Record', CreateRecordActivity::class);
    }

    if (! $registry->has('noop')) {
        $registry->register('noop', 'No-op', NoOpActivity::class);
    }
});

// ---------------------------------------------------------------------------
// Task 16: Dry-run mode integration smoke
// ---------------------------------------------------------------------------

it('dry-run mode: no HTTP sent and no real records written', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $initialCount = StudioRecord::count();

    $flow = makeCanvasFlow(
        nodes: [
            canvasTriggerNode(),
            canvasOpNode('req', 'http_request', ['url' => 'https://example.com', 'method' => 'GET']),
            canvasOpNode('cr', 'create_record', ['collection_slug' => 'demo', 'values' => []]),
        ],
        edges: [
            canvasEdge('trigger', 'req'),
            canvasEdge('req', 'cr'),
        ],
    );

    $run = app(FlowDispatcher::class)->dispatchSync(
        $flow, 'manual', [], [], options: ['dry_run' => true],
    );

    Http::assertNothingSent();
    expect(StudioRecord::count())->toBe($initialCount);
    expect($run->dry_run)->toBeTrue();
    expect($run->status)->toBe(FlowRunStatus::Completed);

    $httpStep = $run->steps()->where('operation_type', 'http_request')->first();
    expect($httpStep)->not->toBeNull();
    expect($httpStep->status)->toBe(FlowRunStepStatus::Skipped);

    $createStep = $run->steps()->where('operation_type', 'create_record')->first();
    expect($createStep)->not->toBeNull();
    expect($createStep->output['id'])->toStartWith('dry-run-');
});

// ---------------------------------------------------------------------------
// Task 16: Step-through mode integration smoke
// ---------------------------------------------------------------------------

it('step-through mode: pauses after first op and advances via step endpoint', function () {
    StudioFlowsApiRouteRegistrar::register();

    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);

    $flow = makeCanvasFlow(
        nodes: [
            canvasTriggerNode(),
            canvasOpNode('op1', 'noop'),
            canvasOpNode('op2', 'noop'),
        ],
        edges: [
            canvasEdge('trigger', 'op1'),
            canvasEdge('op1', 'op2'),
        ],
    );

    $run = app(FlowDispatcher::class)->dispatchSync(
        $flow, 'manual', [], [], options: ['step_through' => true, 'origin' => 'canvas'],
    );

    expect($run->status)->toBe(FlowRunStatus::Paused);
    expect($run->steps()->count())->toBe(1);

    // Advance via API endpoint
    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$run->id}/step")
        ->assertOk();

    expect($run->fresh()->steps()->count())->toBe(2);
    expect($run->fresh()->status)->toBe(FlowRunStatus::Completed);
});
