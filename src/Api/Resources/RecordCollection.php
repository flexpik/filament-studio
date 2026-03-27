<?php

namespace Flexpik\FilamentStudio\Api\Resources;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RecordCollection extends ResourceCollection
{
    public $collects = RecordResource::class;

    protected ?StudioCollection $studioCollection = null;

    public function setCollection(StudioCollection $studioCollection): static
    {
        $this->studioCollection = $studioCollection;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->studioCollection) {
            $this->resource->each(function (RecordResource $resource) {
                $resource->setCollection($this->studioCollection);
            });
        }

        return parent::toArray($request);
    }
}
