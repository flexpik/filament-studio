# Record Versioning

Filament Studio supports snapshot-based version history for records. When enabled, every update creates a version entry containing the complete record state before the change.

## Enabling Versioning

Versioning can be enabled globally or per collection.

### Global

```php
FilamentStudioPlugin::make()
    ->enableVersioning();
```

### Per Collection

Toggle `enable_versioning` on individual collections through the admin UI or programmatically:

```php
$collection->update(['enable_versioning' => true]);
```

## How It Works

The `RecordVersioningObserver` is registered on the `StudioRecord` model. When a record is updated:

1. The observer captures a snapshot of all field values before the update
2. If the snapshot differs from the most recent version, a new `StudioRecordVersion` is created
3. The version stores the full field-value map as JSON, along with the user who made the change

Identical consecutive edits (no actual data change) do not create new versions.

## Version Data

Each version record contains:

| Field | Description |
|-------|-------------|
| `record_id` | The record this version belongs to |
| `collection_id` | The collection (for efficient querying) |
| `snapshot` | JSON object of all field values at that point in time |
| `created_by` | User who triggered the update |
| `created_at` | When the version was created |
| `tenant_id` | Tenant scope |

## Viewing & Restoring Versions

In the record edit page, version history is displayed when versioning is enabled. Users can view previous snapshots and restore a record to a prior state.

## Soft Deletes

Soft deletes are a separate feature that can be enabled alongside versioning:

```php
FilamentStudioPlugin::make()
    ->enableVersioning()
    ->enableSoftDeletes();
```

When soft deletes are enabled, deleted records are marked with a `deleted_at` timestamp rather than being permanently removed. They can be restored through the admin interface.
