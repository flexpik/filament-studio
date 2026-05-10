<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\SavedFilters;

use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;
use Illuminate\Support\Facades\Validator;

class SaveSavedFilter
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(array $input, ?int $tenantId): StudioSavedFilter
    {
        $data = Validator::validate($input, [
            'id' => ['nullable', 'integer'],
            'collection_slug' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'filter' => ['required', 'array'],
            'is_shared' => ['nullable', 'boolean'],
        ]);

        $collection = StudioCollection::query()
            ->forTenant($tenantId)
            ->where('slug', $data['collection_slug'])
            ->first();

        if ($collection === null) {
            throw new StudioNotFoundException('collection', $data['collection_slug']);
        }

        // Validate filter tree shape by parsing it
        FilterGroup::fromArray($data['filter']);

        if (isset($data['id'])) {
            $filter = StudioSavedFilter::query()
                ->forTenant($tenantId)
                ->where('id', $data['id'])
                ->first();

            if ($filter === null) {
                throw new StudioNotFoundException('saved_filter', (string) $data['id']);
            }

            $filter->update([
                'collection_id' => $collection->id,
                'name' => $data['name'],
                'filter_tree' => $data['filter'],
                'is_shared' => $data['is_shared'] ?? $filter->is_shared,
            ]);

            return $filter->fresh();
        }

        return StudioSavedFilter::create([
            'tenant_id' => $tenantId,
            'collection_id' => $collection->id,
            'name' => $data['name'],
            'filter_tree' => $data['filter'],
            'is_shared' => $data['is_shared'] ?? false,
        ]);
    }
}
