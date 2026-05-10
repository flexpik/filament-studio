<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Collections;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Illuminate\Support\Facades\DB;

class DeleteCollection
{
    public function __invoke(string $slug, ?int $tenantId): void
    {
        $collection = StudioCollection::query()
            ->forTenant($tenantId)
            ->where('slug', $slug)
            ->first();

        if ($collection === null) {
            throw new StudioNotFoundException('collection', $slug);
        }

        DB::transaction(function () use ($collection) {
            $recordIds = StudioRecord::query()
                ->where('collection_id', $collection->id)
                ->pluck('id');

            if ($recordIds->isNotEmpty()) {
                StudioValue::query()->whereIn('record_id', $recordIds)->delete();
            }

            StudioRecord::query()->where('collection_id', $collection->id)->delete();

            // Load fields within the transaction
            $fields = $collection->fields()->get();
            foreach ($fields as $field) {
                $field->options()->delete();
                $field->delete();
            }

            $collection->delete();
        });
    }
}
