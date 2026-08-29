<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers;

use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;

class CollectionEventTriggerConfig implements FlowTriggerConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'collection_id', 'type' => 'text', 'label' => 'Collection ID'],
                ['name' => 'event', 'type' => 'select', 'label' => 'Event'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['event' => 'created'];
    }

    /** @param array<string, mixed> $config */
    public function validate(array $config): void
    {
        // Collection and event validation happens at runtime
    }
}
