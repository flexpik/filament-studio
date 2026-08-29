<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\EventSubscriptionRegistry;

it('subscribes a flow to collection events', function () {
    $flow = StudioFlow::factory()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->create();

    $reg = new EventSubscriptionRegistry;
    $reg->subscribe($flow->id, 'people', ['created', 'updated'], $version->id);

    expect($reg->matching('people', 'created'))->toContain($flow->id);
    expect($reg->matching('people', 'deleted'))->not->toContain($flow->id);
    expect($reg->matching('orders', 'created'))->not->toContain($flow->id);
});

it('unsubscribes', function () {
    $reg = new EventSubscriptionRegistry;
    $reg->subscribe('flow-1', 'people', ['created'], 'v1');
    $reg->unsubscribe('flow-1');

    expect($reg->matching('people', 'created'))->toBe([]);
});

it('persists across instances via cache', function () {
    $reg = new EventSubscriptionRegistry;
    $reg->subscribe('flow-2', 'people', ['updated'], 'v2');

    expect((new EventSubscriptionRegistry)->matching('people', 'updated'))->toContain('flow-2');
});
