<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Commands;

use Carbon\CarbonImmutable;
use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Triggers\Schedule\CronEvaluator;
use Illuminate\Console\Command;

class DispatchScheduledFlowsCommand extends Command
{
    protected $signature = 'studio:flows:dispatch-scheduled';

    protected $description = 'Dispatch scheduled Studio flows whose CRON expressions are due';

    public function handle(CronEvaluator $cron, FlowDispatcher $dispatcher): int
    {
        $now = CarbonImmutable::now();

        StudioFlow::query()
            ->where('status', FlowStatus::Active)
            ->cursor()
            ->each(function (StudioFlow $flow) use ($cron, $dispatcher, $now) {
                $version = $flow->publishedVersion;
                if ($version === null) {
                    return;
                }

                $triggerNode = collect($version->graph['nodes'] ?? [])->firstWhere('type', 'trigger');
                if (($triggerNode['data']['triggerType'] ?? null) !== 'schedule') {
                    return;
                }

                $expr = $triggerNode['data']['config']['cron'] ?? null;
                $tz = $triggerNode['data']['config']['timezone'] ?? 'UTC';
                if ($expr === null || ! $cron->isDue($expr, $tz, $now)) {
                    return;
                }

                $hasInflight = StudioFlowRun::query()
                    ->where('flow_id', $flow->id)
                    ->whereIn('status', [FlowRunStatus::Pending, FlowRunStatus::Running])
                    ->exists();

                if ($hasInflight) {
                    return;
                }

                $dispatcher->dispatchAsync(
                    flow: $flow,
                    triggerType: 'schedule',
                    payload: ['scheduled_at' => $now->toIso8601String(), 'flow_id' => $flow->id, 'tenant_id' => $flow->tenant_id],
                    accountability: ['user_id' => null, 'tenant_id' => $flow->tenant_id, 'role' => null, 'source' => 'schedule'],
                );
            });

        return self::SUCCESS;
    }
}
