<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers;

use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;

class ManualTriggerConfig implements FlowTriggerConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'input_schema', 'type' => 'code', 'label' => 'Input Schema'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['input_schema' => []];
    }

    /** @param array<string, mixed> $config */
    public function validate(array $config): void
    {
        // Any input schema is valid for manual triggers
    }
}
