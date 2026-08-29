<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Engine;

use Flexpik\FilamentStudio\Contracts\Flows\DataChain;
use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Flows\Engine\Templating\TemplateEngine;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Enums\LoggingMode;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Security\MasksSensitiveValues;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class StepThroughExecutor
{
    /** Cache TTL in seconds (30 minutes). */
    private const CACHE_TTL = 1800;

    public function __construct(
        private OperationRegistry $operations,
        private TemplateEngine $templates,
        private GraphWalker $walker,
        private MasksSensitiveValues $masker,
    ) {}

    /**
     * Start step-through: mark run Running, execute the first operation, then pause.
     */
    public function startAndPause(StudioFlowRun $run): StudioFlowRun
    {
        $run->forceFill([
            'status' => FlowRunStatus::Running,
            'started_at' => now(),
        ])->save();

        $loggingMode = $run->flow->logging_mode;
        $graph = $run->flowVersion?->graph ?? $run->inline_graph ?? ['nodes' => [], 'edges' => []];

        [$trigger, $nodes] = $this->walker->indexes($graph);

        if ($trigger === null) {
            $run->forceFill([
                'status' => FlowRunStatus::Completed,
                'finished_at' => now(),
                'duration_ms' => 0,
            ])->save();

            return $run->fresh();
        }

        $queue = $this->walker->successors($trigger['id'], 'success', $graph);
        $seen = [];

        $dryRun = (bool) ($run->dry_run ?? false);
        $context = FlowContext::make(
            trigger: $run->trigger_payload ?? [],
            accountability: $run->accountability ?? [],
            dryRun: $dryRun,
        );

        $startedAtMs = microtime(true);

        // Execute one step
        [$queue, $seen, $context] = $this->executeOneStep($run, $queue, $seen, $nodes, $context, $graph, $loggingMode, $dryRun);

        if ($queue === []) {
            $run->forceFill([
                'status' => FlowRunStatus::Completed,
                'finished_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startedAtMs) * 1000),
            ])->save();

            return $run->fresh();
        }

        // Persist state and pause
        Cache::put($this->cacheKey($run->id), [
            'queue' => $queue,
            'seen' => $seen,
            'context' => $context->toCache(),
            'graph' => $graph,
            'started_at_ms' => $startedAtMs,
            'logging_mode' => $loggingMode->value,
        ], self::CACHE_TTL);

        $run->forceFill(['status' => FlowRunStatus::Paused])->save();

        return $run->fresh();
    }

    /**
     * Advance the paused run by one step. Returns the run (paused or completed).
     */
    public function advance(StudioFlowRun $run): StudioFlowRun
    {
        $state = Cache::get($this->cacheKey($run->id));

        if ($state === null) {
            throw new RuntimeException("No step-through state found for run {$run->id}");
        }

        $queue = $state['queue'];
        $seen = $state['seen'];
        $context = FlowContext::fromCache($state['context']);
        $graph = $state['graph'];
        $startedAtMs = $state['started_at_ms'];
        $loggingMode = LoggingMode::from($state['logging_mode']);
        $dryRun = (bool) ($state['context']['dry_run'] ?? false);

        [$nodes] = [$this->walker->indexes($graph)[1]];

        // Execute one step
        [$queue, $seen, $context] = $this->executeOneStep($run, $queue, $seen, $nodes, $context, $graph, $loggingMode, $dryRun);

        if ($queue === []) {
            Cache::forget($this->cacheKey($run->id));

            $run->forceFill([
                'status' => FlowRunStatus::Completed,
                'finished_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startedAtMs) * 1000),
            ])->save();

            return $run->fresh();
        }

        // Update cache with remaining queue
        Cache::put($this->cacheKey($run->id), array_merge($state, [
            'queue' => $queue,
            'seen' => $seen,
            'context' => $context->toCache(),
        ]), self::CACHE_TTL);

        $run->forceFill(['status' => FlowRunStatus::Paused])->save();

        return $run->fresh();
    }

    /**
     * Abort the paused run: delete cache state, set status to Aborted.
     */
    public function abort(StudioFlowRun $run): StudioFlowRun
    {
        Cache::forget($this->cacheKey($run->id));

        $run->forceFill([
            'status' => FlowRunStatus::Aborted,
            'finished_at' => now(),
        ])->save();

        return $run->fresh();
    }

    /**
     * Execute one node from the queue. Skips non-operation nodes and already-seen nodes.
     *
     * @param  string[]  $queue
     * @param  array<string, bool>  $seen
     * @param  array<string, array<string, mixed>>  $nodes
     * @return array{0: string[], 1: array<string, bool>, 2: FlowContext}
     */
    private function executeOneStep(
        StudioFlowRun $run,
        array $queue,
        array $seen,
        array $nodes,
        FlowContext $context,
        array $graph,
        LoggingMode $logging,
        bool $dryRun,
    ): array {
        while ($queue !== []) {
            $id = array_shift($queue);

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $node = $nodes[$id] ?? null;

            if ($node === null || $node['type'] !== 'operation') {
                continue;
            }

            try {
                $branch = $this->executeNode($run, $node, $context, $logging, $dryRun);
            } catch (Throwable $e) {
                $run->forceFill([
                    'status' => FlowRunStatus::Failed,
                    'finished_at' => now(),
                ])->save();

                // Return empty queue to signal termination
                return [[], $seen, $context];
            }

            foreach ($this->walker->successors($id, $branch, $graph) as $next) {
                $queue[] = $next;
            }

            // Executed one operation — stop here
            return [$queue, $seen, $context];
        }

        return [$queue, $seen, $context];
    }

    /** @param array<string, mixed> $node */
    private function executeNode(StudioFlowRun $run, array $node, FlowContext $context, LoggingMode $logging, bool $dryRun = false): string
    {
        $key = $node['data']['key'] ?? $node['id'];
        $type = $node['data']['operationType'] ?? null;

        if ($type === null) {
            throw new InvalidArgumentException("Node {$node['id']} missing operationType");
        }

        $rawConfig = $node['data']['config'] ?? [];
        $resolvedConfig = $this->templates->renderArray($rawConfig, $context);

        // Dry-run: side-effect ops are skipped entirely
        if ($dryRun && in_array($type, FlowWorkflow::SIDE_EFFECT_TYPES, true)) {
            $step = StudioFlowRunStep::create([
                'flow_run_id' => $run->id,
                'operation_key' => $key,
                'operation_type' => $type,
                'attempt_number' => 1,
                'status' => FlowRunStepStatus::Skipped,
                'input' => $logging === LoggingMode::Disabled ? null : $this->masker->mask((array) $resolvedConfig),
                'output' => ['log' => "[dry-run] would have called {$type}"],
                'branch_taken' => 'success',
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $context->set($key, $step->output);

            return 'success';
        }

        // Dry-run: data ops return synthetic results without touching the DB
        if ($dryRun && in_array($type, FlowWorkflow::DATA_OP_TYPES, true)) {
            $syntheticOutput = match ($type) {
                'create_record' => ['id' => 'dry-run-'.Str::uuid(), 'dry_run' => true] + (array) ($resolvedConfig['values'] ?? []),
                'update_record' => array_merge((array) ($resolvedConfig['values'] ?? []), ['dry_run' => true]),
                'delete_record', 'upsert_record' => ['deleted' => true, 'id' => $resolvedConfig['record_id'] ?? null, 'dry_run' => true],
                default => ['dry_run' => true],
            };

            StudioFlowRunStep::create([
                'flow_run_id' => $run->id,
                'operation_key' => $key,
                'operation_type' => $type,
                'attempt_number' => 1,
                'status' => FlowRunStepStatus::Completed,
                'input' => $logging === LoggingMode::Disabled ? null : $this->masker->mask((array) $resolvedConfig),
                'output' => $logging === LoggingMode::Disabled ? null : $syntheticOutput,
                'branch_taken' => 'success',
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $context->set($key, $syntheticOutput);

            return 'success';
        }

        $retryCount = max(0, min(5, (int) ($resolvedConfig['retry_count'] ?? 0)));
        $maxAttempts = $retryCount + 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $step = StudioFlowRunStep::create([
                'flow_run_id' => $run->id,
                'operation_key' => $key,
                'operation_type' => $type,
                'attempt_number' => $attempt,
                'status' => FlowRunStepStatus::Running,
                'input' => $logging === LoggingMode::Disabled ? null : $this->masker->mask((array) $resolvedConfig),
                'started_at' => now(),
            ]);

            try {
                /** @var FlowOperation $activity */
                $activity = $this->operations->resolve($type);

                $chain = new DataChain(
                    trigger: $context->trigger(),
                    outputs: $this->extractOutputs($context),
                    last: $context->last(),
                );

                $opCtx = new OperationContext(
                    flow: $run->flow,
                    run: $run,
                    dataChain: $chain,
                    config: (array) $resolvedConfig,
                    tenantId: (string) ($run->accountability['tenant_id'] ?? ''),
                );

                $result = $activity->execute($opCtx);

                if ($result->isFailure()) {
                    throw new RuntimeException($result->message() ?? 'Operation failed', 0, $result->previous());
                }

                $output = $result->output();
                $context->set($key, $output);

                $branch = $result->branch() ?? 'success';

                $step->forceFill([
                    'status' => FlowRunStepStatus::Completed,
                    'output' => $logging === LoggingMode::Disabled ? null : $this->masker->mask($output),
                    'branch_taken' => $branch,
                    'finished_at' => now(),
                ])->save();

                return $branch;
            } catch (Throwable $e) {
                $step->forceFill([
                    'status' => FlowRunStepStatus::Failed,
                    'error_class' => $e::class,
                    'error_message' => substr($e->getMessage(), 0, 255),
                    'error_trace' => $logging === LoggingMode::Full ? $e->getTraceAsString() : null,
                    'finished_at' => now(),
                ])->save();

                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
            }
        }

        return 'success'; // @codeCoverageIgnore
    }

    /** @return array<string, mixed> */
    private function extractOutputs(FlowContext $context): array
    {
        $all = $context->toArray();

        return array_filter(
            $all,
            fn ($k) => is_string($k) && ! str_starts_with($k, '$'),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function cacheKey(string $runId): string
    {
        return "flow_step:{$runId}";
    }
}
