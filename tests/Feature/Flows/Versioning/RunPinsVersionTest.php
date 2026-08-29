<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;

it('pins flow_version_id from published version at dispatch time', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $originalVersionId = $flow->published_version_id;

    $run = app(FlowDispatcher::class)->dispatchAsync($flow, 'manual', [], []);
    expect($run->flow_version_id)->toBe($originalVersionId);

    // Publish a new version — old run is unaffected.
    $flow->update(['draft_graph' => ['nodes' => [], 'edges' => []]]);
    app(PublishFlowVersion::class)->publish($flow);
    expect($run->fresh()->flow_version_id)->toBe($originalVersionId);
});

it('accepts an explicit inlineGraph override (for test runs)', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();

    $run = app(FlowDispatcher::class)->dispatchAsync(
        $flow, 'manual', [], [], inlineGraph: ['nodes' => [['id' => 'draft']], 'edges' => []],
    );

    expect($run->flow_version_id)->toBeNull()
        ->and($run->inline_graph)->toBe(['nodes' => [['id' => 'draft']], 'edges' => []]);
});

it('refuses to dispatch when there is no published version and no inline graph', function () {
    $flow = StudioFlow::factory()->create();
    expect(fn () => app(FlowDispatcher::class)->dispatchAsync($flow, 'manual', [], []))
        ->toThrow(RuntimeException::class, 'no_published_version');
});
