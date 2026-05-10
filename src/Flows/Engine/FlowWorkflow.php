<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Engine;

use Flexpik\FilamentStudio\Flows\Engine\Templating\TemplateEngine;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Enums\LoggingMode;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Throwable;

class FlowWorkflow
{
    public function __construct(
        private OperationRegistry $operations,
        private TemplateEngine $templates,
        private GraphWalker $walker,
        private LogMaskingService $masker,
    ) {}

    public function run(string $flowRunId): void
    {
        /** @var StudioFlowRun $run */
        $run = StudioFlowRun::query()->with(['flow', 'version'])->findOrFail($flowRunId);
        $loggingMode = $run->flow->logging_mode;

        $run->forceFill([
            'status' => FlowRunStatus::Running,
            'started_at' => now(),
        ])->save();

        $context = FlowContext::make(
            trigger: $run->trigger_payload ?? [],
            accountability: $run->accountability ?? [],
        );

        $startedAtMs = microtime(true);

        try {
            $orderedNodes = $this->walker->order($run->version->graph ?? ['nodes' => [], 'edges' => []]);

            foreach ($orderedNodes as $node) {
                $this->executeNode($run, $node, $context, $loggingMode);
            }

            $run->forceFill([
                'status' => FlowRunStatus::Completed,
                'finished_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startedAtMs) * 1000),
            ])->save();
        } catch (Throwable $e) {
            $run->forceFill([
                'status' => FlowRunStatus::Failed,
                'finished_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startedAtMs) * 1000),
            ])->save();
        }
    }

    /** @param  array<string, mixed>  $node */
    private function executeNode(StudioFlowRun $run, array $node, FlowContext $context, LoggingMode $logging): void
    {
        $key = $node['data']['key'] ?? $node['id'];
        $type = $node['data']['operationType'];
        $rawConfig = $node['data']['config'] ?? [];
        $resolvedConfig = $this->templates->renderArray($rawConfig, $context);

        $step = StudioFlowRunStep::create([
            'flow_run_id' => $run->id,
            'operation_key' => $key,
            'operation_type' => $type,
            'attempt' => 1,
            'status' => FlowRunStepStatus::Running,
            'input' => $logging === LoggingMode::Disabled ? null : $resolvedConfig,
            'started_at' => now(),
        ]);

        try {
            $activity = $this->operations->resolve($type);
            $output = $activity->execute($resolvedConfig, $context);
            $context->set($key, $output);

            $step->forceFill([
                'status' => FlowRunStepStatus::Completed,
                'output' => $logging === LoggingMode::Disabled ? null : $output,
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $step->forceFill([
                'status' => FlowRunStepStatus::Failed,
                'error_message' => substr($e->getMessage(), 0, 255),
                'error_trace' => $logging === LoggingMode::Full ? $e->getTraceAsString() : null,
                'finished_at' => now(),
            ])->save();

            throw $e;
        }
    }
}
