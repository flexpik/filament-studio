<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Records;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Facades\Validator;

class UpdateRecord
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(array $input, int $tenantId): StudioRecord
    {
        $base = Validator::validate($input, [
            'collection_slug' => ['required', 'string'],
            'uuid' => ['required', 'string'],
            'data' => ['required', 'array'],
            'locale' => ['nullable', 'string'],
        ]);

        $collection = StudioCollection::query()
            ->forTenant($tenantId)
            ->where('slug', $base['collection_slug'])
            ->first();

        if ($collection === null) {
            throw new StudioNotFoundException('collection', $base['collection_slug']);
        }

        $record = StudioRecord::query()
            ->where('collection_id', $collection->id)
            ->where('uuid', $base['uuid'])
            ->first();

        if ($record === null) {
            throw new StudioNotFoundException('record', $base['uuid']);
        }

        $fields = EavQueryBuilder::getCachedFields($collection);
        $rules = (new CreateRecord)->rulesFor($fields, mode: 'update');
        Validator::validate($input, $rules);

        EavQueryBuilder::for($collection)
            ->locale($base['locale'] ?? null)
            ->update($record->id, $base['data'] ?? []);

        return $record->fresh();
    }
}
