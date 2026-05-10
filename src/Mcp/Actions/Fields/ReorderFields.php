<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Fields;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderFields
{
    /**
     * @param  array<int, string>  $columnNames
     */
    public function __invoke(string $collectionSlug, array $columnNames, ?int $tenantId): void
    {
        $collection = StudioCollection::query()
            ->forTenant($tenantId)
            ->where('slug', $collectionSlug)
            ->first();

        if ($collection === null) {
            throw new StudioNotFoundException('collection', $collectionSlug);
        }

        $existing = StudioField::query()
            ->where('collection_id', $collection->id)
            ->pluck('column_name')
            ->all();

        $sortedExisting = $existing;
        sort($sortedExisting);
        $sortedInput = $columnNames;
        sort($sortedInput);

        if ($sortedExisting !== $sortedInput) {
            throw ValidationException::withMessages([
                'column_names' => ['Provided list must contain every existing field exactly once.'],
            ]);
        }

        DB::transaction(function () use ($collection, $columnNames) {
            foreach ($columnNames as $i => $name) {
                StudioField::query()
                    ->where('collection_id', $collection->id)
                    ->where('column_name', $name)
                    ->update(['sort_order' => $i]);
            }
        });
    }
}
