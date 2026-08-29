<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Composition;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;

class TriggerFlowActivity implements FlowOperation
{
    public function __construct(private FlowDispatcher $dispatcher) {}

    public function execute(OperationContext $context): OperationResult
    {
        $run = $context->run();
        $accountability = $run->accountability ?? [];
        $depth = (int) ($accountability['_call_depth'] ?? 0);
        $max = (int) config('filament-studio.flows.max_call_depth', 5);

        if ($depth >= $max) {
            return OperationResult::fail("Flow call depth {$depth} exceeds max depth {$max}");
        }

        $config = $context->config();
        $flow = StudioFlow::query()->findOrFail($config['flow_id']);
        $payload = (array) ($config['payload'] ?? []);

        $newAccountability = $accountability;
        $newAccountability['_call_depth'] = $depth + 1;
        $newAccountability['source'] = 'flow';

        if (($config['mode'] ?? 'async') === 'sync') {
            $childRun = $this->dispatcher->dispatchSync($flow, 'flow', $payload, $newAccountability);

            return OperationResult::success([
                'flow_run_id' => $childRun->id,
                'status' => $childRun->status->value,
                'dispatched' => false,
            ]);
        }

        $childRun = $this->dispatcher->dispatchAsync($flow, 'flow', $payload, $newAccountability);

        return OperationResult::success(['dispatched' => true, 'flow_run_id' => $childRun->id]);
    }
}
