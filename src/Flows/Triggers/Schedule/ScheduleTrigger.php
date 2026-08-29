<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers\Schedule;

use Cron\CronExpression;
use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Triggers\FlowTrigger;
use InvalidArgumentException;

class ScheduleTrigger implements FlowTrigger
{
    public function __construct(private FlowDispatcher $dispatcher) {}

    public function fireForFlow(StudioFlow $flow, array $payload = []): ?StudioFlowRun
    {
        if ($flow->published_version_id === null) {
            return StudioFlowRun::create([
                'flow_id' => $flow->id,
                'flow_version_id' => null,
                'inline_graph' => null,
                'status' => FlowRunStatus::Failed,
                'trigger_type' => 'schedule',
                'trigger_payload' => $payload,
                'accountability' => [
                    'user_id' => null,
                    'tenant_id' => $flow->tenant_id,
                    'role' => null,
                    'source' => 'schedule',
                    'refusal_reason' => 'no_published_version',
                ],
                'started_at' => now(),
                'finished_at' => now(),
                'duration_ms' => 0,
            ]);
        }

        return $this->dispatcher->dispatchAsync(
            flow: $flow,
            triggerType: 'schedule',
            payload: $payload,
            accountability: ['user_id' => null, 'tenant_id' => $flow->tenant_id, 'role' => null, 'source' => 'schedule'],
        );
    }

    public function register(StudioFlowVersion $version): void
    {
        $cron = $this->triggerConfig($version)['cron'] ?? '';
        if (! CronExpression::isValidExpression($cron)) {
            throw new InvalidArgumentException("Invalid cron expression: {$cron}");
        }
    }

    public function unregister(StudioFlowVersion $version): void
    {
        // Schedule trigger has no per-flow registration; the dispatcher polls.
    }

    private function triggerConfig(StudioFlowVersion $version): array
    {
        $node = collect($version->graph['nodes'] ?? [])->firstWhere('type', 'trigger');

        return $node['data']['config'] ?? [];
    }
}
