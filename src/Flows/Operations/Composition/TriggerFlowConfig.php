<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Composition;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

class TriggerFlowConfig implements FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'flow_id', 'type' => 'text', 'label' => 'Flow ID'],
                ['name' => 'payload', 'type' => 'code', 'label' => 'Payload'],
                ['name' => 'mode', 'type' => 'select', 'label' => 'Mode'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['mode' => 'async', 'payload' => []];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(array $config): void
    {
        if (empty($config['flow_id'] ?? '')) {
            throw new InvalidOperationConfigException(
                'flow_id is required',
                ['flow_id' => 'required'],
            );
        }
    }
}
