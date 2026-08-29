<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Records;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use InvalidArgumentException;

class CreateRecordActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $config = $context->config();
        $slug = (string) ($config['collection'] ?? '');
        $collection = StudioCollection::query()->where('slug', $slug)->first();
        if ($collection === null) {
            throw new InvalidArgumentException("Unknown collection: {$slug}");
        }

        $tenantIdStr = $context->tenantId();
        $tenantId = $tenantIdStr !== '' ? (int) $tenantIdStr : null;

        $record = EavQueryBuilder::for($collection)
            ->tenant($tenantId)
            ->create((array) ($config['data'] ?? []));

        return OperationResult::success(['id' => $record->id, 'data' => $config['data'] ?? []]);
    }
}
