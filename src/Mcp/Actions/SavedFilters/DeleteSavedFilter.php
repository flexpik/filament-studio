<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\SavedFilters;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;

class DeleteSavedFilter
{
    public function __invoke(int $id, int $tenantId): void
    {
        $filter = StudioSavedFilter::query()
            ->forTenant($tenantId)
            ->where('id', $id)
            ->first();

        if ($filter === null) {
            throw new StudioNotFoundException('saved_filter', (string) $id);
        }

        $filter->delete();
    }
}
