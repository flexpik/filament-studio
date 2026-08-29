<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Records;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use InvalidArgumentException;

class ReadRecordActivity implements FlowOperation
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

        $multiple = (bool) ($config['multiple'] ?? false);

        if (isset($config['id']) && ! $multiple) {
            $record = StudioRecord::query()
                ->where('collection_id', $collection->id)
                ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
                ->where('id', $config['id'])
                ->firstOrFail();

            return OperationResult::success(['record' => $this->serialize($record, $collection, $tenantId)]);
        }

        $builder = EavQueryBuilder::for($collection)->tenant($tenantId);

        if (isset($config['filter'])) {
            $builder->applyFilterTree(FilterGroup::fromArray($config['filter']));
        }

        $records = $builder->get();

        if (! $multiple) {
            $first = $records->first();

            return OperationResult::success(['record' => $first ? $this->serializeRow($first, $tenantId) : null]);
        }

        $serialized = $records->map(fn (\stdClass $r) => $this->serializeRow($r, $tenantId))->all();

        return OperationResult::success(['records' => $serialized, 'count' => count($serialized)]);
    }

    /**
     * Serialize a StudioRecord model (fetched by ID lookup) with its EAV data.
     */
    private function serialize(StudioRecord $record, StudioCollection $collection, ?int $tenantId): array
    {
        return [
            'id' => $record->id,
            'tenant_id' => $record->tenant_id,
            'data' => EavQueryBuilder::for($collection)->tenant($tenantId)->getRecordData($record),
        ];
    }

    /**
     * Serialize a stdClass row returned by EavQueryBuilder::get().
     * The row already contains assembled field values as properties.
     */
    private function serializeRow(\stdClass $row, ?int $tenantId): array
    {
        $data = (array) $row;
        unset($data['id'], $data['uuid'], $data['created_at'], $data['updated_at']);

        return [
            'id' => $row->id,
            'tenant_id' => $tenantId,
            'data' => $data,
        ];
    }
}
