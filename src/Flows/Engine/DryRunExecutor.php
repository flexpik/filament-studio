<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Engine;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;

/**
 * Thin wrapper around FlowDispatcher that forces dry_run=true on every dispatch.
 *
 * In dry-run mode the engine applies three strategies per operation type:
 *  - Side-effect ops  (http_request, send_email, …): skipped, step recorded as "skipped"
 *  - Data ops         (create_record, update_record, delete_record, upsert_record): synthetic output returned, DB not touched
 *  - Pure ops         (noop, condition, transform_payload, …): executed normally
 *
 * The concrete lists live as public constants on FlowWorkflow so canvas code can
 * read them without depending on this class.
 */
class DryRunExecutor
{
    public function __construct(private FlowDispatcher $dispatcher) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $accountability
     * @param  array<string, mixed>|null  $inlineGraph
     */
    public function execute(
        StudioFlow $flow,
        string $triggerType = 'manual',
        array $payload = [],
        array $accountability = [],
        ?array $inlineGraph = null,
    ): StudioFlowRun {
        return $this->dispatcher->dispatchSync(
            flow: $flow,
            triggerType: $triggerType,
            payload: $payload,
            accountability: $accountability,
            inlineGraph: $inlineGraph,
            options: ['dry_run' => true],
        );
    }
}
