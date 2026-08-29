<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\Exceptions\CannotPublishDangerousFlowException;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;

$dangerousGraph = [
    'nodes' => [
        ['id' => 't', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
        ['id' => 'h', 'type' => 'operation', 'data' => ['operationType' => 'http_request', 'config' => ['url' => 'https://evil.example', 'method' => 'GET']]],
    ],
    'edges' => [['source' => 't', 'target' => 'h']],
];

$safeGraph = [
    'nodes' => [
        ['id' => 't', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
        ['id' => 'l', 'type' => 'operation', 'data' => ['operationType' => 'log_message', 'config' => ['level' => 'info', 'message' => 'hello']]],
    ],
    'edges' => [['source' => 't', 'target' => 'l']],
];

it('blocks users without run_dangerous_operations from publishing dangerous graphs', function () use ($dangerousGraph) {
    $editor = $this->makeUserWith(['publish_flow']);
    $this->actingAs($editor);

    $flow = StudioFlow::factory()->create(['draft_graph' => $dangerousGraph]);

    $service = app(PublishFlowVersion::class);
    expect(fn () => $service->publish($flow))
        ->toThrow(CannotPublishDangerousFlowException::class);
});

it('allows users with run_dangerous_operations to publish dangerous graphs', function () use ($dangerousGraph) {
    $admin = $this->makeUserWith(['publish_flow', 'run_dangerous_operations']);
    $this->actingAs($admin);

    $flow = StudioFlow::factory()->create(['draft_graph' => $dangerousGraph]);

    $service = app(PublishFlowVersion::class);
    $version = $service->publish($flow);
    expect($version)->toBeInstanceOf(StudioFlowVersion::class);
});

it('allows publishing safe graphs without special permissions', function () use ($safeGraph) {
    $editor = $this->makeUserWith(['publish_flow']);
    $this->actingAs($editor);

    $flow = StudioFlow::factory()->create(['draft_graph' => $safeGraph]);

    $service = app(PublishFlowVersion::class);
    $version = $service->publish($flow);
    expect($version)->toBeInstanceOf(StudioFlowVersion::class);
});
