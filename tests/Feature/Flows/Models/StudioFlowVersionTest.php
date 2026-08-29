<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

it('starts as draft (published_at null)', function () {
    $v = StudioFlowVersion::factory()->create();
    expect($v->isDraft())->toBeTrue();
    expect($v->isPublished())->toBeFalse();
});

it('publish() sets published_at and change_summary', function () {
    $v = StudioFlowVersion::factory()->create();
    $v->publish('initial release');
    expect($v->fresh()->published_at)->not->toBeNull();
    expect($v->fresh()->change_summary)->toBe('initial release');
});

it('belongs to a flow', function () {
    $flow = StudioFlow::factory()->create();
    $v = StudioFlowVersion::factory()->for($flow, 'flow')->create();
    expect($v->flow->is($flow))->toBeTrue();
});

it('stores graph as array via json cast', function () {
    $v = StudioFlowVersion::factory()->create([
        'graph' => ['nodes' => [['id' => 'a']], 'edges' => []],
    ]);
    expect($v->fresh()->graph)->toBe(['nodes' => [['id' => 'a']], 'edges' => []]);
});
