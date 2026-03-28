<?php

namespace Flexpik\FilamentStudio\Api;

use Flexpik\FilamentStudio\Api\Resources\RecordCollection;
use Flexpik\FilamentStudio\Api\Resources\RecordResource;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;

class StudioApiController extends Controller
{
    protected function resolveCollection(Request $request, string $slug): StudioCollection
    {
        $query = StudioCollection::query()
            ->where('slug', $slug)
            ->where('api_enabled', true);

        $apiKey = $this->getApiKey($request);

        if ($apiKey && $apiKey->tenant_id !== null) {
            $query->forTenant($apiKey->tenant_id);
        }

        return $query->firstOrFail();
    }

    protected function getApiKey(Request $request): ?StudioApiKey
    {
        return $request->attributes->get('studio_api_key');
    }

    public function index(Request $request, string $collection_slug): RecordCollection
    {
        $collection = $this->resolveCollection($request, $collection_slug);
        $perPage = min((int) $request->query('per_page', 25), 100);

        $paginator = StudioRecord::query()
            ->where('collection_id', $collection->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $resourceCollection = new RecordCollection($paginator);
        $resourceCollection->setCollection($collection);

        return $resourceCollection;
    }

    public function show(Request $request, string $collection_slug, string $uuid): RecordResource
    {
        $collection = $this->resolveCollection($request, $collection_slug);

        $record = StudioRecord::query()
            ->where('collection_id', $collection->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $resource = new RecordResource($record);
        $resource->setCollection($collection);

        return $resource;
    }

    public function store(Request $request, string $collection_slug): JsonResponse
    {
        $collection = $this->resolveCollection($request, $collection_slug);
        $fields = EavQueryBuilder::getCachedFields($collection);

        $rules = $this->buildStoreRules($fields);
        $validated = $request->validate($rules);

        $data = $validated['data'] ?? [];

        $record = EavQueryBuilder::for($collection)->create($data);

        $resource = new RecordResource($record);
        $resource->setCollection($collection);

        return $resource->response()->setStatusCode(201);
    }

    public function update(Request $request, string $collection_slug, string $uuid): RecordResource
    {
        $collection = $this->resolveCollection($request, $collection_slug);

        $record = StudioRecord::query()
            ->where('collection_id', $collection->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        $fields = EavQueryBuilder::getCachedFields($collection);
        $rules = $this->buildUpdateRules($fields);
        $validated = $request->validate($rules);

        $data = $validated['data'] ?? [];

        EavQueryBuilder::for($collection)->update($record->id, $data);

        $resource = new RecordResource($record->fresh());
        $resource->setCollection($collection);

        return $resource;
    }

    public function destroy(Request $request, string $collection_slug, string $uuid): JsonResponse
    {
        $collection = $this->resolveCollection($request, $collection_slug);

        $record = StudioRecord::query()
            ->where('collection_id', $collection->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        EavQueryBuilder::for($collection)->delete($record->id);

        return response()->json(null, 204);
    }

    /**
     * @return array<string, array<string>>
     */
    protected function buildStoreRules(Collection $fields): array
    {
        $rules = [];

        /** @var StudioField $field */
        foreach ($fields as $field) {
            if ($field->is_system) {
                continue;
            }

            $fieldRules = $field->is_required ? ['required'] : ['nullable'];
            $fieldRules = array_merge($fieldRules, $this->typeRules($field));

            if (! empty($field->validation_rules)) {
                $fieldRules = array_merge($fieldRules, $field->validation_rules);
            }

            $rules['data.'.$field->column_name] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string, array<string>>
     */
    protected function buildUpdateRules(Collection $fields): array
    {
        $rules = [];

        /** @var StudioField $field */
        foreach ($fields as $field) {
            if ($field->is_system) {
                continue;
            }

            $fieldRules = ['sometimes'];
            $fieldRules = array_merge($fieldRules, $this->typeRules($field));

            if (! empty($field->validation_rules)) {
                $fieldRules = array_merge($fieldRules, $field->validation_rules);
            }

            $rules['data.'.$field->column_name] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @return array<string>
     */
    protected function typeRules(StudioField $field): array
    {
        return match ($field->eav_cast->value) {
            'text' => ['string'],
            'integer' => ['integer'],
            'decimal' => ['numeric'],
            'boolean' => ['boolean'],
            'datetime' => ['date'],
            'json' => ['array'],
            default => ['string'],
        };
    }
}
