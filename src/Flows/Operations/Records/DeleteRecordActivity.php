<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Records;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;

class DeleteRecordActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $config = $context->config();
        $slug = (string) ($config['collection'] ?? '');
        $collection = StudioCollection::query()->where('slug', $slug)->firstOrFail();

        $tenantIdStr = $context->tenantId();
        $tenantId = $tenantIdStr !== '' ? (int) $tenantIdStr : null;

        $deleted = StudioRecord::query()
            ->where('collection_id', $collection->id)
            ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('id', $config['id'])
            ->delete();

        return OperationResult::success(['deleted' => (int) $deleted, 'id' => $config['id']]);
    }
}
