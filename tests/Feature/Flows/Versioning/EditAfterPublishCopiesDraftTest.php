<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\HydrateDraftFromPublished;

it('copies published graph into draft_graph the first time it is called', function () {
    $flow = StudioFlow::factory()->withPublishedVersion(['nodes' => [['id' => 'p']], 'edges' => []])->create();
    expect($flow->draft_graph)->toBeNull();

    app(HydrateDraftFromPublished::class)->hydrate($flow);

    expect($flow->fresh()->draft_graph)->toBe(['nodes' => [['id' => 'p']], 'edges' => []]);
});

it('does not overwrite an existing draft on subsequent calls', function () {
    $flow = StudioFlow::factory()->withPublishedVersion()->create();
    $flow->update(['draft_graph' => ['nodes' => [['id' => 'untouched']], 'edges' => []]]);

    app(HydrateDraftFromPublished::class)->hydrate($flow);

    expect($flow->fresh()->draft_graph)->toBe(['nodes' => [['id' => 'untouched']], 'edges' => []]);
});

it('is a no-op when there is no published version', function () {
    $flow = StudioFlow::factory()->create();
    app(HydrateDraftFromPublished::class)->hydrate($flow);
    expect($flow->fresh()->draft_graph)->toBeNull();
});
