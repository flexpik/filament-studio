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
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class FlowWorkflow
{
    /** Operation types that have real-world side effects and must be skipped in dry-run mode. */
    public const SIDE_EFFECT_TYPES = [
        'http_request',
        'send_email',
        'notification',
        'dispatch_job',
        'fire_event',
        'artisan',
    ];

    /** Operation types that mutate EAV data and should return synthetic results in dry-run mode. */
    public const DATA_OP_TYPES = [
        'create_record',
        'update_record',
        'delete_record',
        'upsert_record',
    ];

    public function __construct(
        private OperationRegistry $operations,
        private TemplateEngine $templates,
        private GraphWalker $walker,
        private MasksSensitiveValues $masker,
    ) {}

    public function run(string $flowRunId): void
    {
        /** @var StudioFlowRun $run */
        $run = StudioFlowRun::query()->with(['flow', 'flowVersion'])->findOrFail($flowRunId);
        $loggingMode = $run->flow->logging_mode;

        $run->forceFill([
            'status' => FlowRunStatus::Running,
            'started_at' => now(),
        ])->save();

        $dryRun = (bool) ($run->dry_run ?? false);

        $context = FlowContext::make(
            trigger: $run->trigger_payload ?? [],
            accountability: $run->accountability ?? [],
            dryRun: $dryRun,
        );

        $startedAtMs = microtime(true);

        $graph = $run->flowVersion?->graph ?? $run->inline_graph ?? ['nodes' => [], 'edges' => []];

        [$trigger, $nodes] = $this->walker->indexes($graph);

        if ($trigger === null) {
            $run->forceFill(['status' => FlowRunStatus::Completed, 'finished_at' => now(), 'duration_ms' => 0])->save();

            return;
        }

        $queue = $this->walker->successors($trigger['id'], 'success', $graph);
        $seen = [];

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
                $branch = $this->executeNode($run, $node, $context, $loggingMode, $dryRun);
            } catch (Throwable $e) {
                $run->forceFill([
                    'status' => FlowRunStatus::Failed,
                    'finished_at' => now(),
                    'duration_ms' => (int) ((microtime(true) - $startedAtMs) * 1000),
                ])->save();

                return;
            }

            foreach ($this->walker->successors($id, $branch, $graph) as $next) {
                $queue[] = $next;
            }
        }

        $run->forceFill([
            'status' => FlowRunStatus::Completed,
            'finished_at' => now(),
            'duration_ms' => (int) ((microtime(true) - $startedAtMs) * 1000),
        ])->save();
    }

    /** @param  array<string, mixed>  $node */
    private function executeNode(StudioFlowRun $run, array $node, FlowContext $context, LoggingMode $logging, bool $dryRun = false): string
    {
        $key = $node['data']['key'] ?? $node['id'];
        $type = $node['data']['operationType'] ?? null;
        if ($type === null) {
            throw new InvalidArgumentException("Node {$node['id']} missing operationType");
        }
        $rawConfig = $node['data']['config'] ?? [];
        $resolvedConfig = $this->templates->renderArray($rawConfig, $context);

        // --- Dry-run: side-effect ops are skipped entirely ---
        if ($dryRun && in_array($type, self::SIDE_EFFECT_TYPES, true)) {
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

        // --- Dry-run: data ops return synthetic results without touching the DB ---
        if ($dryRun && in_array($type, self::DATA_OP_TYPES, true)) {
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

        // This line is unreachable — the loop always returns or throws.
        // It satisfies static analysis for the return type.
        return 'success'; // @codeCoverageIgnore
    }

    /**
     * @return array<string, mixed>
     */
    private function extractOutputs(FlowContext $context): array
    {
        $all = $context->toArray();

        return array_filter(
            $all,
            fn ($k) => is_string($k) && ! str_starts_with($k, '$'),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
