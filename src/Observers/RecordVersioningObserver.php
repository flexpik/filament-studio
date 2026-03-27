<?php

namespace Flexpik\FilamentStudio\Observers;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Models\StudioValue;

class RecordVersioningObserver
{
    /**
     * Capture the pre-update snapshot (old values before EAV write).
     */
    public function updating(StudioRecord $record): void
    {
        $this->captureSnapshot($record);
    }

    /**
     * Capture the post-update snapshot (new values after EAV write).
     */
    public function updated(StudioRecord $record): void
    {
        $this->captureSnapshot($record);
    }

    protected function captureSnapshot(StudioRecord $record): void
    {
        $collection = StudioCollection::find($record->collection_id);

        if (! $collection || ! $collection->enable_versioning) {
            return;
        }

        $snapshot = $this->buildSnapshot($record);

        if (empty($snapshot)) {
            return;
        }

        // Skip if the latest version already has an identical snapshot
        $latest = StudioRecordVersion::query()
            ->where('record_id', $record->id)
            ->orderByDesc('created_at')
            ->first();

        if ($latest && $latest->snapshot === $snapshot) {
            return;
        }

        StudioRecordVersion::create([
            'record_id' => $record->id,
            'collection_id' => $collection->id,
            'tenant_id' => $record->tenant_id,
            'snapshot' => $snapshot,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSnapshot(StudioRecord $record): array
    {
        $fieldsTable = config('filament-studio.table_prefix', 'studio_').'fields';
        $valuesTable = config('filament-studio.table_prefix', 'studio_').'values';

        $values = StudioValue::query()
            ->join($fieldsTable, "{$fieldsTable}.id", '=', "{$valuesTable}.field_id")
            ->where("{$valuesTable}.record_id", $record->id)
            ->select([
                "{$fieldsTable}.column_name",
                "{$fieldsTable}.eav_cast",
                "{$valuesTable}.val_text",
                "{$valuesTable}.val_integer",
                "{$valuesTable}.val_decimal",
                "{$valuesTable}.val_boolean",
                "{$valuesTable}.val_datetime",
                "{$valuesTable}.val_json",
            ])
            ->get();

        $snapshot = [];
        /** @var StudioValue&object{eav_cast: string, column_name: string} $value */
        foreach ($values as $value) {
            $column = match ($value->eav_cast) {
                'text' => 'val_text',
                'integer' => 'val_integer',
                'decimal' => 'val_decimal',
                'boolean' => 'val_boolean',
                'datetime' => 'val_datetime',
                'json' => 'val_json',
                default => 'val_text',
            };

            $snapshot[$value->column_name] = $value->{$column};
        }

        return $snapshot;
    }
}
