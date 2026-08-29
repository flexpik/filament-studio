<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Exceptions\InvalidFlowGraphException;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;

class FlowGraphValidator
{
    public function __construct(
        private readonly OperationRegistry $operations,
        private readonly TriggerRegistry $triggers,
    ) {}

    /** @param array<string, mixed> $graph */
    public function validate(array $graph): void
    {
        $errors = [];
        $nodes = $graph['nodes'] ?? [];

        foreach ($nodes as $node) {
            $nodeId = $node['id'] ?? 'unknown';
            $type = $node['type'] ?? null;

            if ($type === 'operation') {
                $opKey = $node['data']['operationType'] ?? null;

                if ($opKey === null) {
                    $errors[$nodeId] = 'missing operationType';

                    continue;
                }

                if (! $this->operations->has($opKey)) {
                    $errors[$nodeId] = "unknown operation '{$opKey}'";

                    continue;
                }

                $configClass = $this->operations->configFor($opKey);
                if ($configClass !== null) {
                    $config = $node['data']['config'] ?? [];
                    $configInstance = app($configClass);
                    $mergedConfig = array_merge(
                        $configInstance->defaults(),
                        is_array($config) ? $config : [],
                    );

                    try {
                        $configInstance->validate($mergedConfig);
                    } catch (InvalidOperationConfigException $e) {
                        $errors[$nodeId] = $e->getMessage();
                    }
                }
            } elseif ($type === 'trigger') {
                $triggerKey = $node['data']['triggerType'] ?? null;

                if ($triggerKey !== null && ! $this->triggers->has($triggerKey)) {
                    $errors[$nodeId] = "unknown trigger '{$triggerKey}'";
                }
            }
        }

        if ($errors !== []) {
            throw new InvalidFlowGraphException($errors);
        }
    }
}
