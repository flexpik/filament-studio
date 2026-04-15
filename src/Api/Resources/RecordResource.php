<?php

namespace Flexpik\FilamentStudio\Api\Resources;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Flexpik\FilamentStudio\Services\LocaleResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudioRecord
 */
class RecordResource extends JsonResource
{
    protected ?StudioCollection $collection = null;

    public function setCollection(StudioCollection $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $collection = $this->collection ?? $request->attributes->get('studio_collection');

        $locale = app(LocaleResolver::class)->resolve($collection);

        $data = EavQueryBuilder::for($collection)
            ->locale($locale)
            ->getRecordData($this->resource);

        return [
            'uuid' => $this->uuid,
            'data' => $data,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
