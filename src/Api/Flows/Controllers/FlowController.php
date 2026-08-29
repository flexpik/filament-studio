<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Controllers;

use Flexpik\FilamentStudio\Api\Flows\Requests\StoreFlowRequest;
use Flexpik\FilamentStudio\Api\Flows\Requests\UpdateFlowRequest;
use Flexpik\FilamentStudio\Api\Flows\Resources\FlowCollection;
use Flexpik\FilamentStudio\Api\Flows\Resources\FlowResource;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FlowController
{
    public function index(Request $request): FlowCollection
    {
        $tenantId = $request->attributes->get('studio_api_key_tenant_id');

        $flows = StudioFlow::query()
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('updated_at')
            ->paginate((int) $request->integer('per_page', 25));

        return new FlowCollection($flows);
    }

    public function store(StoreFlowRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('studio_api_key_tenant_id');

        $flow = StudioFlow::create(
            $request->validated() + ['tenant_id' => $tenantId]
        );

        StudioFlowVersion::create([
            'flow_id' => $flow->id,
            'version' => 1,
            'graph' => ['nodes' => [], 'edges' => []],
        ]);

        return (new FlowResource($flow))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $id): FlowResource
    {
        $flow = $this->scopedQuery($request)->findOrFail($id);

        return new FlowResource($flow);
    }

    public function update(UpdateFlowRequest $request, string $id): FlowResource
    {
        $flow = $this->scopedQuery($request)->findOrFail($id);
        $flow->update($request->validated());

        return new FlowResource($flow);
    }

    public function destroy(Request $request, string $id): Response
    {
        $flow = $this->scopedQuery($request)->findOrFail($id);

        $hasInflight = $flow->runs()->whereIn('status', [
            FlowRunStatus::Pending->value,
            FlowRunStatus::Running->value,
        ])->exists();

        if ($hasInflight) {
            abort(409, 'Cannot delete flow with active runs');
        }

        $flow->delete();

        return response()->noContent();
    }

    private function scopedQuery(Request $request): Builder
    {
        $tenantId = $request->attributes->get('studio_api_key_tenant_id');

        return StudioFlow::query()
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId));
    }
}
