<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Controllers;

use Flexpik\FilamentStudio\Api\Flows\Requests\RunFlowRequest;
use Flexpik\FilamentStudio\Api\Flows\Resources\FlowRunDetailResource;
use Flexpik\FilamentStudio\Api\Flows\Resources\FlowRunResource;
use Flexpik\FilamentStudio\Api\Flows\Resources\RunExportResource;
use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Engine\StepThroughExecutor;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FlowRunController
{
    public function __construct(private FlowDispatcher $dispatcher) {}

    public function index(RunFlowRequest $request, string $flowId): AnonymousResourceCollection
    {
        $flow = $this->scopedFlow($request, $flowId);
        $runs = $flow->runs()->orderByDesc('started_at')->paginate(25);

        return FlowRunResource::collection($runs);
    }

    public function show(RunFlowRequest $request, string $flowId, string $runId): FlowRunDetailResource
    {
        $flow = $this->scopedFlow($request, $flowId);
        $run = $flow->runs()->where('id', $runId)->firstOrFail();

        return new FlowRunDetailResource($run);
    }

    public function export(RunFlowRequest $request, string $runId): RunExportResource
    {
        $tenantId = $request->attributes->get('studio_api_key_tenant_id');

        $run = StudioFlowRun::query()
            ->with('steps')
            ->when($tenantId !== null, function ($q) use ($tenantId) {
                $q->whereHas('flow', fn ($fq) => $fq->where('tenant_id', $tenantId));
            })
            ->findOrFail($runId);

        return new RunExportResource($run);
    }

    public function cancel(RunFlowRequest $request, string $runId): FlowRunResource
    {
        $run = StudioFlowRun::query()->findOrFail($runId);

        if ($run->status->isTerminal()) {
            abort(409, 'Run is already terminal');
        }

        $run->forceFill([
            'status' => FlowRunStatus::Cancelled,
            'finished_at' => now(),
        ])->save();

        return new FlowRunResource($run);
    }

    public function run(RunFlowRequest $request, string $flowId): JsonResponse
    {
        $flow = $this->scopedFlow($request, $flowId);
        if ($flow->status !== FlowStatus::Active) {
            abort(409, 'Flow is not active');
        }

        if ($flow->published_version_id === null) {
            return response()->json(['error' => 'no_published_version'], 409);
        }

        $run = $this->dispatcher->dispatchAsync(
            flow: $flow,
            triggerType: 'manual',
            payload: (array) $request->input('payload', []),
            accountability: $this->accountability($request),
        );

        return (new FlowRunResource($run))->response()->setStatusCode(202);
    }

    public function replay(RunFlowRequest $request, string $runId): JsonResponse
    {
        $run = StudioFlowRun::query()->findOrFail($runId);

        $flow = StudioFlow::query()
            ->with('publishedVersion')
            ->findOrFail($run->flow_id);

        if ($flow->published_version_id === null) {
            return response()->json([
                'errors' => ['replay' => ['No current published version']],
            ], 422);
        }

        $newRun = $this->dispatcher->dispatchAsync(
            flow: $flow,
            triggerType: $run->trigger_type,
            payload: (array) ($run->trigger_payload ?? []),
            accountability: $this->accountability($request),
        );

        return (new FlowRunResource($newRun))->response()->setStatusCode(201);
    }

    public function step(RunFlowRequest $request, string $runId): FlowRunResource|JsonResponse
    {
        $run = StudioFlowRun::query()->findOrFail($runId);

        if ($run->status !== FlowRunStatus::Paused) {
            return response()->json(['error' => 'run_not_paused'], 409);
        }

        if ($request->input('action') === 'abort') {
            app(StepThroughExecutor::class)->abort($run);
        } else {
            app(StepThroughExecutor::class)->advance($run);
        }

        return new FlowRunResource($run->fresh());
    }

    public function test(RunFlowRequest $request, string $flowId): FlowRunResource|JsonResponse
    {
        $flow = $this->scopedFlow($request, $flowId);
        if ($flow->draft_graph === null) {
            return response()->json(['error' => 'no_draft'], 422);
        }

        $dryRun = $request->boolean('dry_run', false);

        $run = $this->dispatcher->dispatchAsync(
            flow: $flow,
            triggerType: 'test',
            payload: (array) $request->input('payload', []),
            accountability: $this->accountability($request),
            inlineGraph: $flow->draft_graph,
            options: ['dry_run' => $dryRun],
        );

        return (new FlowRunResource($run))->response()->setStatusCode(200);
    }

    private function scopedFlow(RunFlowRequest $request, string $id): StudioFlow
    {
        $tenantId = $request->attributes->get('studio_api_key_tenant_id');

        return StudioFlow::query()
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);
    }

    private function accountability(RunFlowRequest $request): array
    {
        return [
            'user_id' => null,
            'tenant_id' => $request->attributes->get('studio_api_key_tenant_id'),
            'role' => null,
            'source' => 'manual',
        ];
    }
}
