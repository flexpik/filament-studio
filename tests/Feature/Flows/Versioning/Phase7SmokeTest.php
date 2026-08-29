<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Services\HydrateDraftFromPublished;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\RollbackFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\SaveFlowDraft;

it('save → publish → trigger v1 → edit → publish v2 → trigger v2 → rollback → trigger v3', function () {
    $flow = StudioFlow::factory()->create();

    $save = app(SaveFlowDraft::class);
    $publish = app(PublishFlowVersion::class);
    $rollback = app(RollbackFlowVersion::class);
    $dispatcher = app(FlowDispatcher::class);

    // Step 1: save draft + publish v1
    $triggerGraph = ['nodes' => [['id' => 't', 'type' => 'trigger', 'data' => []]], 'edges' => []];
    $save->save($flow, $triggerGraph);
    $v1 = $publish->publish($flow->fresh(), 'initial');
    expect($v1->version)->toBe(1);

    // Step 2: trigger fires v1
    $run1 = $dispatcher->dispatchAsync($flow->fresh(), 'manual', [], []);
    expect($run1->flow_version_id)->toBe($v1->id);

    // Step 3: edit (hydrate draft) and save changes
    app(HydrateDraftFromPublished::class)->hydrate($flow->fresh());
    $flow->refresh();
    $save->save($flow, ['nodes' => [
        ['id' => 't', 'type' => 'trigger', 'data' => []],
        ['id' => 'n2', 'type' => 'operation', 'data' => ['operationType' => 'noop']],
    ], 'edges' => []]);

    // Step 4: trigger fires before publish → still pinned to v1
    $run2 = $dispatcher->dispatchAsync($flow->fresh(), 'manual', [], []);
    expect($run2->flow_version_id)->toBe($v1->id);

    // Step 5: publish v2
    $v2 = $publish->publish($flow->fresh(), 'add noop');
    expect($v2->version)->toBe(2);

    // Step 6: trigger fires v2
    $run3 = $dispatcher->dispatchAsync($flow->fresh(), 'manual', [], []);
    expect($run3->flow_version_id)->toBe($v2->id);

    // Step 7: rollback to v1 → mints v3 with v1's graph
    $v3 = $rollback->rollback($flow->fresh(), $v1);
    expect($v3->version)->toBe(3)
        ->and($v3->graph)->toBe($v1->graph);

    // Step 8: trigger fires v3
    $run4 = $dispatcher->dispatchAsync($flow->fresh(), 'manual', [], []);
    expect($run4->flow_version_id)->toBe($v3->id);

    // Final ledger
    expect(StudioFlowRun::query()->where('flow_id', $flow->id)->count())->toBe(4);
});
