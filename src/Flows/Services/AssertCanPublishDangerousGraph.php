<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Services\Exceptions\CannotPublishDangerousFlowException;
use Illuminate\Contracts\Auth\Authenticatable;

class AssertCanPublishDangerousGraph
{
    public function __construct(private OperationRegistry $registry) {}

    /**
     * Walk the graph nodes, check if any operation has DANGEROUS=true.
     * If actor lacks run_dangerous_operations permission, throw.
     *
     * @param  array<string, mixed>  $graph
     */
    public function assert(array $graph, ?Authenticatable $actor): void
    {
        $nodes = $graph['nodes'] ?? [];

        foreach ($nodes as $node) {
            if (($node['type'] ?? null) !== 'operation') {
                continue;
            }

            $operationKey = $node['data']['operationType'] ?? null;

            if ($operationKey === null || ! $this->registry->has($operationKey)) {
                continue;
            }

            $activityClass = $this->registry->activityClass($operationKey);

            if (! defined("{$activityClass}::DANGEROUS") || ! constant("{$activityClass}::DANGEROUS")) {
                continue;
            }

            if ($actor === null || ! method_exists($actor, 'can') || ! $actor->can('run_dangerous_operations')) {
                throw new CannotPublishDangerousFlowException(
                    'Flow contains dangerous operations. Actor lacks run_dangerous_operations permission.'
                );
            }
        }
    }
}
