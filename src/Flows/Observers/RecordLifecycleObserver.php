<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Observers;

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Triggers\EventSubscriptionRegistry;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;

class RecordLifecycleObserver
{
    public function __construct(
        private EventSubscriptionRegistry $registry,
        private FlowDispatcher $dispatcher,
    ) {}

    public function created(StudioRecord $record): void
    {
        $this->dispatchFor($record, 'created');
    }

    public function updated(StudioRecord $record): void
    {
        $this->dispatchFor($record, 'updated');
    }

    public function deleted(StudioRecord $record): void
    {
        $this->dispatchFor($record, 'deleted');
    }

    private function dispatchFor(StudioRecord $record, string $event): void
    {
        $collection = StudioCollection::find($record->collection_id);
        if ($collection === null) {
            return;
        }

        $flowIds = $this->registry->matching($collection->slug, $event);
        if ($flowIds === []) {
            return;
        }

        $flows = StudioFlow::query()->whereIn('id', $flowIds)->where('status', FlowStatus::Active)->get();

        foreach ($flows as $flow) {
            $this->dispatcher->dispatchAsync(
                flow: $flow,
                triggerType: 'collection_event',
                payload: [
                    'event' => $event,
                    'collection' => $collection->slug,
                    'record' => ['id' => $record->id],
                    'tenant_id' => $record->tenant_id,
                ],
                accountability: ['user_id' => null, 'tenant_id' => $record->tenant_id, 'role' => null, 'source' => 'collection_event'],
            );
        }
    }
}
