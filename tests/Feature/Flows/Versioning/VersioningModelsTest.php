<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

it('exposes publishedVersion as a BelongsTo and versions as HasMany', function () {
    $flow = StudioFlow::factory()->create();
    $v = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create(['version' => 1]);
    $flow->update(['published_version_id' => $v->id]);

    expect($flow->publishedVersion()->first()?->id)->toBe($v->id)
        ->and($flow->versions()->count())->toBe(1);
});

it('casts draft_graph to array on StudioFlow', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [['id' => 'n1']], 'edges' => []],
    ]);
    expect($flow->fresh()->draft_graph)->toBe(['nodes' => [['id' => 'n1']], 'edges' => []]);
});

it('persists published_by on StudioFlowVersion', function () {
    $v = StudioFlowVersion::factory()->published()->create(['published_by' => 'user-123']);
    expect($v->fresh()->published_by)->toBe('user-123');
});

it('exposes flowVersion BelongsTo and inline_graph cast on StudioFlowRun', function () {
    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create([
        'flow_version_id' => null,
        'inline_graph' => ['nodes' => [], 'edges' => []],
    ]);
    expect($run->fresh()->inline_graph)->toBe(['nodes' => [], 'edges' => []])
        ->and($run->flowVersion()->first())->toBeNull();
});
