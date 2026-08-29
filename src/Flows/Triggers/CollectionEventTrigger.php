<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers;

use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;

class CollectionEventTrigger implements FlowTrigger
{
    public function __construct(private EventSubscriptionRegistry $registry) {}

    public function register(StudioFlowVersion $version): void
    {
        $config = $this->triggerConfig($version);
        $collection = (string) ($config['collection'] ?? '');
        $events = (array) ($config['events'] ?? []);

        if ($collection === '' || $events === []) {
            return;
        }

        $this->registry->subscribe($version->flow_id, $collection, $events, $version->id);
    }

    public function unregister(StudioFlowVersion $version): void
    {
        $this->registry->unsubscribe($version->flow_id);
    }

    private function triggerConfig(StudioFlowVersion $version): array
    {
        $node = collect($version->graph['nodes'] ?? [])->firstWhere('type', 'trigger');

        return $node['data']['config'] ?? [];
    }
}
