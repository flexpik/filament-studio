<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Controllers;

use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowMetaController
{
    public function operations(OperationRegistry $registry): JsonResponse
    {
        $data = collect($registry->all())
            ->map(fn ($entry, $key) => [
                'key' => $key,
                'label' => $entry['label'],
                'configSchema' => $entry['configSchema'],
            ])
            ->values();

        return response()->json(['data' => $data]);
    }

    public function triggers(TriggerRegistry $registry): JsonResponse
    {
        $data = collect($registry->all())
            ->map(fn ($entry, $key) => [
                'key' => $key,
                'label' => $entry['label'],
                'configSchema' => $entry['configSchema'],
            ])
            ->values();

        return response()->json(['data' => $data]);
    }

    public function collections(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('studio_api_key_tenant_id');

        $collections = StudioCollection::query()
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->with('fields')
            ->get()
            ->map(fn ($c) => [
                'slug' => $c->slug,
                'name' => $c->name,
                'fields' => $c->fields->map(fn ($f) => [
                    'key' => $f->column_name,
                    'name' => $f->label,
                    'field_type' => $f->field_type,
                ])->values(),
            ]);

        return response()->json(['data' => $collections]);
    }
}
