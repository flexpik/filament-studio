<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Exceptions\InvalidFlowGraphException;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;

it('publish rejects unknown operation key', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [
            ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
            ['id' => 'node_bad', 'type' => 'operation', 'data' => ['operationType' => 'ghost_op', 'config' => []]],
        ], 'edges' => []],
    ]);

    app(PublishFlowVersion::class)->publish($flow);
})->throws(InvalidFlowGraphException::class);

it('publish succeeds for a valid graph with noop operation', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [
            ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
            ['id' => 'node_noop', 'type' => 'operation', 'data' => ['operationType' => 'noop', 'config' => []]],
        ], 'edges' => [
            ['id' => 'e1', 'source' => 'trigger_1', 'target' => 'node_noop', 'sourceHandle' => 'success'],
        ]],
    ]);

    $version = app(PublishFlowVersion::class)->publish($flow);

    expect($version)->not->toBeNull();
    expect($version->published_at)->not->toBeNull();
});

it('publish rejects invalid config for log_message operation', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [
            ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
            ['id' => 'node_log', 'type' => 'operation', 'data' => ['operationType' => 'log_message', 'config' => ['level' => 'bad_level']]],
        ], 'edges' => []],
    ]);

    app(PublishFlowVersion::class)->publish($flow);
})->throws(InvalidFlowGraphException::class);

it('publish succeeds with valid log_message config', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [
            ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
            ['id' => 'node_log', 'type' => 'operation', 'data' => ['operationType' => 'log_message', 'config' => ['level' => 'info', 'message' => 'test']]],
        ], 'edges' => [
            ['id' => 'e1', 'source' => 'trigger_1', 'target' => 'node_log', 'sourceHandle' => 'success'],
        ]],
    ]);

    $version = app(PublishFlowVersion::class)->publish($flow);

    expect($version)->not->toBeNull();
});
