<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Data;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;

class TransformPayloadConfig implements FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'payload', 'type' => 'code', 'label' => 'Payload'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['payload' => []];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(array $config): void
    {
        // any payload shape is valid
    }
}
