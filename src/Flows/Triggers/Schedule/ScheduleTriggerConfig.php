<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers\Schedule;

use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;

class ScheduleTriggerConfig implements FlowTriggerConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'cron_expression', 'type' => 'text', 'label' => 'Cron Expression'],
                ['name' => 'timezone', 'type' => 'text', 'label' => 'Timezone'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['cron_expression' => '0 * * * *', 'timezone' => 'UTC'];
    }

    /** @param array<string, mixed> $config */
    public function validate(array $config): void
    {
        // Cron expression validation happens at runtime in ScheduleTrigger::register()
    }
}
