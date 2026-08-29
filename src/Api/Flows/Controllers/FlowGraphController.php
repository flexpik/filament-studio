<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Controllers;

use Flexpik\FilamentStudio\Api\Flows\Requests\PublishFlowRequest;
use Flexpik\FilamentStudio\Api\Flows\Requests\SaveGraphRequest;
use Flexpik\FilamentStudio\Api\Flows\Resources\FlowVersionResource;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidFlowGraphException;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\HydrateDraftFromPublished;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\RollbackFlowVersion;
use Flexpik\FilamentStudio\Flows\Services\SaveFlowDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FlowGraphController
{
    public function show(Request $request, string $flowId): JsonResponse
    {
        $flow = $this->scopedFlow($request, $flowId);
        app(HydrateDraftFromPublished::class)->hydrate($flow);
        $flow->refresh();

        return response()->json([
            'data' => [
                'flow_id' => $flow->id,
                'draft_graph' => $flow->draft_graph,
                'draft_updated_at' => $flow->draft_updated_at,
                'published_version' => $flow->publishedVersion
                    ? new FlowVersionResource($flow->publishedVersion)
                    : null,
            ],
        ]);
    }

    public function save(SaveGraphRequest $request, string $flowId, SaveFlowDraft $service): JsonResponse
    {
        $flow = $this->scopedFlow($request, $flowId);
        $service->save($flow, $request->input('graph'));
        $flow->refresh();

        return response()->json([
            'data' => [
                'draft_graph' => $flow->draft_graph,
                'draft_updated_at' => $flow->draft_updated_at,
            ],
        ]);
    }

    public function publish(PublishFlowRequest $request, string $flowId, PublishFlowVersion $service): FlowVersionResource|JsonResponse
    {
        $flow = $this->scopedFlow($request, $flowId);

        try {
            $version = $service->publish(
                $flow,
                $request->input('change_summary'),
                (string) ($request->attributes->get('studio_api_key_id') ?? 'api'),
            );
        } catch (InvalidFlowGraphException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'graph' => [$e->getMessage()],
                ],
            ], 422);
        }

        return new FlowVersionResource($version);
    }

    public function versions(Request $request, string $flowId): AnonymousResourceCollection
    {
        $flow = $this->scopedFlow($request, $flowId);

        return FlowVersionResource::collection(
            $flow->versions()->orderByDesc('version')->get()
        );
    }

    public function showVersion(Request $request, string $flowId, string $versionId): FlowVersionResource
    {
        $flow = $this->scopedFlow($request, $flowId);
        $version = $flow->versions()->findOrFail($versionId);

        return new FlowVersionResource($version);
    }

    public function rollback(Request $request, string $flowId, string $versionId, RollbackFlowVersion $service): FlowVersionResource
    {
        $flow = $this->scopedFlow($request, $flowId);
        $version = $flow->versions()->findOrFail($versionId);

        return new FlowVersionResource(
            $service->rollback($flow, $version, (string) ($request->attributes->get('studio_api_key_id') ?? 'api')),
        );
    }

    private function scopedFlow(Request $request, string $id): StudioFlow
    {
        $tenantId = $request->attributes->get('studio_api_key_tenant_id');

        return StudioFlow::query()
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->findOrFail($id);
    }
}
