<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers;

use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;

class WebhookTriggerConfig implements FlowTriggerConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'secret_key', 'type' => 'text', 'label' => 'Secret Key'],
                ['name' => 'expected_method', 'type' => 'select', 'label' => 'Expected Method'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['expected_method' => 'POST'];
    }

    /** @param array<string, mixed> $config */
    public function validate(array $config): void
    {
        // Any webhook config is valid; method validation happens at runtime
    }
}
