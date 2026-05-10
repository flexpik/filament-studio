<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Fields;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioValue;
use Illuminate\Support\Facades\DB;

class DeleteField
{
    public function __invoke(string $collectionSlug, string $columnName, ?int $tenantId): void
    {
        $collection = StudioCollection::query()
            ->forTenant($tenantId)
            ->where('slug', $collectionSlug)
            ->first();

        if ($collection === null) {
            throw new StudioNotFoundException('collection', $collectionSlug);
        }

        $field = StudioField::query()
            ->where('collection_id', $collection->id)
            ->where('column_name', $columnName)
            ->first();

        if ($field === null) {
            throw new StudioNotFoundException('field', "{$collectionSlug}.{$columnName}");
        }

        DB::transaction(function () use ($field) {
            StudioValue::query()->where('field_id', $field->id)->delete();
            $field->options()->delete();
            $field->delete();
        });
    }
}
