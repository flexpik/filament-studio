<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Records;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

class DeleteRecord
{
    public function __invoke(string $collectionSlug, string $uuid, ?int $tenantId, bool $force = false): void
    {
        $collection = StudioCollection::query()->forTenant($tenantId)->where('slug', $collectionSlug)->first();
        if ($collection === null) {
            throw new StudioNotFoundException('collection', $collectionSlug);
        }

        $record = StudioRecord::withTrashed()
            ->where('collection_id', $collection->id)
            ->where('uuid', $uuid)
            ->first();
        if ($record === null) {
            throw new StudioNotFoundException('record', $uuid);
        }

        if ($force) {
            EavQueryBuilder::for($collection)->deleteWithIntegrity($uuid);

            return;
        }

        EavQueryBuilder::for($collection)->delete($record->id);
    }
}
