<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\EventSubscriptionRegistry;

it('publishing a draft calls trigger.register', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'collection_event',
            'config' => ['collection' => 'tickets', 'events' => ['created']],
        ]]], 'edges' => []],
    ]);

    app(PublishFlowVersion::class)->publish($flow, 'first publish');

    $flow->refresh();
    $published = StudioFlowVersion::find($flow->published_version_id);
    expect($published)->not->toBeNull();
    expect($published->published_at)->not->toBeNull();
    expect(app(EventSubscriptionRegistry::class)->matching('tickets', 'created'))->toContain($flow->id);
});

it('unregisters the previous published version when re-publishing', function () {
    $flow = StudioFlow::factory()->create();
    $v1 = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'version' => 1,
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'collection_event', 'config' => ['collection' => 'old', 'events' => ['created']],
        ]]], 'edges' => []],
    ]);
    $flow->forceFill(['published_version_id' => $v1->id])->save();
    app(EventSubscriptionRegistry::class)->subscribe($flow->id, 'old', ['created'], $v1->id);

    $flow->update([
        'draft_graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'collection_event', 'config' => ['collection' => 'new', 'events' => ['updated']],
        ]]], 'edges' => []],
    ]);

    app(PublishFlowVersion::class)->publish($flow->fresh());

    expect(app(EventSubscriptionRegistry::class)->matching('old', 'created'))->not->toContain($flow->id);
    expect(app(EventSubscriptionRegistry::class)->matching('new', 'updated'))->toContain($flow->id);
});
