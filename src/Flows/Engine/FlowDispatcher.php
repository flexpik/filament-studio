<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Engine;

use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use InvalidArgumentException;
use RuntimeException;

class FlowDispatcher
{
    public function __construct(private FlowWorkflow $workflow) {}

    /**
     * @param  array<string, mixed>|null  $inlineGraph
     * @param  array<string, mixed>  $options  Supported keys: dry_run (bool), step_through (bool), origin (string)
     */
    public function dispatchSync(
        StudioFlow $flow,
        string $triggerType,
        array $payload,
        array $accountability,
        ?array $inlineGraph = null,
        array $options = [],
    ): StudioFlowRun {
        if ($options['step_through'] ?? false) {
            if (($options['origin'] ?? '') !== 'canvas') {
                throw new InvalidArgumentException('step_through is only allowed for canvas origin');
            }

            $run = $this->createRun($flow, $triggerType, $payload, $accountability, $inlineGraph, $options);
            app(StepThroughExecutor::class)->startAndPause($run);

            return $run->fresh();
        }

        $run = $this->createRun($flow, $triggerType, $payload, $accountability, $inlineGraph, $options);
        $this->workflow->run($run->id);

        return $run->fresh();
    }

    /**
     * @param  array<string, mixed>|null  $inlineGraph
     * @param  array<string, mixed>  $options  Supported keys: dry_run (bool), step_through (bool), origin (string)
     */
    public function dispatchAsync(
        StudioFlow $flow,
        string $triggerType,
        array $payload,
        array $accountability,
        ?array $inlineGraph = null,
        array $options = [],
    ): StudioFlowRun {
        if ($options['step_through'] ?? false) {
            if (($options['origin'] ?? '') !== 'canvas') {
                throw new InvalidArgumentException('step_through is only allowed for canvas origin');
            }

            $run = $this->createRun($flow, $triggerType, $payload, $accountability, $inlineGraph, $options);
            app(StepThroughExecutor::class)->startAndPause($run);

            return $run->fresh();
        }

        $run = $this->createRun($flow, $triggerType, $payload, $accountability, $inlineGraph, $options);
        ExecuteFlowJob::dispatch($run->id)
            ->onConnection(config('filament-studio.flows.connection'))
            ->onQueue(config('filament-studio.flows.queue', 'default'));

        return $run;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function createRun(
        StudioFlow $flow,
        string $triggerType,
        array $payload,
        array $accountability,
        ?array $inlineGraph,
        array $options = [],
    ): StudioFlowRun {
        if ($inlineGraph === null && $flow->published_version_id === null) {
            throw new RuntimeException('no_published_version');
        }

        return StudioFlowRun::create([
            'flow_id' => $flow->id,
            'flow_version_id' => $inlineGraph === null ? $flow->published_version_id : null,
            'inline_graph' => $inlineGraph,
            'status' => FlowRunStatus::Pending,
            'trigger_type' => $triggerType,
            'trigger_payload' => $payload,
            'accountability' => $accountability,
            'dry_run' => (bool) ($options['dry_run'] ?? false),
        ]);
    }
}
