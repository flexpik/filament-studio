<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\CollectionEventTrigger;
use Flexpik\FilamentStudio\Flows\Triggers\EventSubscriptionRegistry;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['slug' => 'tickets']);
    $this->flow = StudioFlow::factory()->create(['status' => FlowStatus::Active]);
    $this->version = StudioFlowVersion::factory()->for($this->flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'collection_event',
            'config' => ['collection' => 'tickets', 'events' => ['created', 'updated']],
        ]]], 'edges' => []],
    ]);
    $this->flow->update(['published_version_id' => $this->version->id]);

    (new CollectionEventTrigger(app(EventSubscriptionRegistry::class)))->register($this->version);
});

it('dispatches an ExecuteFlowJob when a record is created on the subscribed collection', function () {
    Bus::fake();

    StudioRecord::factory()->for($this->collection, 'collection')->create();

    Bus::assertDispatched(ExecuteFlowJob::class);
});

it('does not dispatch for a different collection', function () {
    Bus::fake();
    $other = StudioCollection::factory()->create(['slug' => 'other']);

    StudioRecord::factory()->for($other, 'collection')->create();

    Bus::assertNotDispatched(ExecuteFlowJob::class);
});

it('does not dispatch when flow is inactive', function () {
    Bus::fake();
    $this->flow->forceFill(['status' => FlowStatus::Inactive])->save();

    StudioRecord::factory()->for($this->collection, 'collection')->create();

    Bus::assertNotDispatched(ExecuteFlowJob::class);
});

it('unregister removes the subscription', function () {
    Bus::fake();
    (new CollectionEventTrigger(app(EventSubscriptionRegistry::class)))->unregister($this->version);

    StudioRecord::factory()->for($this->collection, 'collection')->create();

    Bus::assertNotDispatched(ExecuteFlowJob::class);
});
