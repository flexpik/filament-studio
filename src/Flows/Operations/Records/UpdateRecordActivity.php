<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Records;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

class UpdateRecordActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $config = $context->config();
        $slug = (string) ($config['collection'] ?? '');
        $collection = StudioCollection::query()->where('slug', $slug)->firstOrFail();

        $tenantIdStr = $context->tenantId();
        $tenantId = $tenantIdStr !== '' ? (int) $tenantIdStr : null;

        // Verify record belongs to this tenant before updating
        $record = StudioRecord::query()
            ->where('collection_id', $collection->id)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('id', $config['id'])
            ->firstOrFail();

        EavQueryBuilder::for($collection)
            ->tenant($tenantId)
            ->update($record->id, (array) ($config['data'] ?? []));

        return OperationResult::success(['updated' => 1, 'id' => $record->id]);
    }
}
