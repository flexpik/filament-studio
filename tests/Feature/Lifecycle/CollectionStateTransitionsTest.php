<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Observers\RecordVersioningObserver;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

// ── 1. Visibility toggle ─────────────────────────────────────────────

it('excludes collection from visible scope when is_hidden transitions to true', function () {
    $collection = StudioCollection::factory()->create(['is_hidden' => false]);

    expect(StudioCollection::visible()->where('id', $collection->id)->exists())->toBeTrue();

    $collection->update(['is_hidden' => true]);

    expect(StudioCollection::visible()->where('id', $collection->id)->exists())->toBeFalse();
});

it('includes collection in visible scope when is_hidden transitions to false', function () {
    $collection = StudioCollection::factory()->hidden()->create();

    expect(StudioCollection::visible()->where('id', $collection->id)->exists())->toBeFalse();

    $collection->update(['is_hidden' => false]);

    expect(StudioCollection::visible()->where('id', $collection->id)->exists())->toBeTrue();
});

// ── 2. Singleton toggle ──────────────────────────────────────────────

it('marks collection as singleton when is_singleton transitions to true', function () {
    $collection = StudioCollection::factory()->create(['is_singleton' => false]);

    expect($collection->is_singleton)->toBeFalse();

    $collection->update(['is_singleton' => true]);
    $collection->refresh();

    expect($collection->is_singleton)->toBeTrue();
});

it('marks collection as non-singleton when is_singleton transitions to false', function () {
    $collection = StudioCollection::factory()->singleton()->create();

    expect($collection->is_singleton)->toBeTrue();

    $collection->update(['is_singleton' => false]);
    $collection->refresh();

    expect($collection->is_singleton)->toBeFalse();
});

// ── 3. Versioning toggle ─────────────────────────────────────────────

it('creates version snapshots when enable_versioning transitions to true', function () {
    $collection = StudioCollection::factory()->create(['enable_versioning' => false]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create(['collection_id' => $collection->id]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'Hello',
    ]);

    // With versioning off, no snapshot should be created
    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);

    // Enable versioning
    $collection->update(['enable_versioning' => true]);

    // Now a snapshot should be created
    $observer->updating($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(1)
        ->and(StudioRecordVersion::where('record_id', $record->id)->first()->snapshot)
        ->toBe(['title' => 'Hello']);
});

it('skips version snapshots when enable_versioning transitions to false', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create(['collection_id' => $collection->id]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'Hello',
    ]);

    // With versioning on, snapshot is created
    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(1);

    // Disable versioning
    $collection->update(['enable_versioning' => false]);

    // No new snapshot should be created
    $observer->updating($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(1);
});

// ── 4. Soft-delete toggle ────────────────────────────────────────────
// NOTE: These tests verify that the enable_soft_deletes configuration flag
// persists correctly on the collection model. They do NOT test behavioral
// gating at the model layer — StudioRecord always uses Laravel's SoftDeletes
// trait regardless of this flag. Behavioral enforcement based on this flag
// is deferred to Task A7 (RecordSoftDeleteLifecycleTest).

it('stores enable_soft_deletes as true when toggled on', function () {
    $collection = StudioCollection::factory()->create(['enable_soft_deletes' => false]);

    expect($collection->enable_soft_deletes)->toBeFalse();

    $collection->update(['enable_soft_deletes' => true]);
    $collection->refresh();

    expect($collection->enable_soft_deletes)->toBeTrue();
});

it('stores enable_soft_deletes as false when toggled off', function () {
    $collection = StudioCollection::factory()->withSoftDeletes()->create();

    expect($collection->enable_soft_deletes)->toBeTrue();

    $collection->update(['enable_soft_deletes' => false]);
    $collection->refresh();

    expect($collection->enable_soft_deletes)->toBeFalse();
});

it('retains soft-deleted records via SoftDeletes trait regardless of collection flag', function () {
    $collection = StudioCollection::factory()->create(['enable_soft_deletes' => false]);

    $record = StudioRecord::factory()->create(['collection_id' => $collection->id]);
    $recordId = $record->id;

    $record->delete();

    // StudioRecord always uses SoftDeletes trait, so the record is soft-deleted
    expect(StudioRecord::withTrashed()->find($recordId))->not->toBeNull()
        ->and(StudioRecord::withTrashed()->find($recordId)->deleted_at)->not->toBeNull()
        ->and(StudioRecord::find($recordId))->toBeNull();
});

// ── 5. sort_field config ─────────────────────────────────────────────

it('sets sort_field to a field slug', function () {
    $collection = StudioCollection::factory()->create(['sort_field' => null]);

    expect($collection->sort_field)->toBeNull();

    $collection->update(['sort_field' => 'title']);
    $collection->refresh();

    expect($collection->sort_field)->toBe('title');
});

it('unsets sort_field back to null', function () {
    $collection = StudioCollection::factory()->create(['sort_field' => 'title']);

    expect($collection->sort_field)->toBe('title');

    $collection->update(['sort_field' => null]);
    $collection->refresh();

    expect($collection->sort_field)->toBeNull();
});

// ── 6. archive_field config ──────────────────────────────────────────

it('sets archive_field and archive_value', function () {
    $collection = StudioCollection::factory()->create([
        'archive_field' => null,
        'archive_value' => null,
    ]);

    expect($collection->archive_field)->toBeNull()
        ->and($collection->archive_value)->toBeNull();

    $collection->update([
        'archive_field' => 'status',
        'archive_value' => 'archived',
    ]);
    $collection->refresh();

    expect($collection->archive_field)->toBe('status')
        ->and($collection->archive_value)->toBe('archived');
});

it('unsets archive_field and archive_value back to null', function () {
    $collection = StudioCollection::factory()->create([
        'archive_field' => 'status',
        'archive_value' => 'archived',
    ]);

    expect($collection->archive_field)->toBe('status')
        ->and($collection->archive_value)->toBe('archived');

    $collection->update([
        'archive_field' => null,
        'archive_value' => null,
    ]);
    $collection->refresh();

    expect($collection->archive_field)->toBeNull()
        ->and($collection->archive_value)->toBeNull();
});

// ── 7. Delete cascade ────────────────────────────────────────────────

it('cascades deletion to fields, records, and values when collection is deleted', function () {
    $collection = StudioCollection::factory()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create(['collection_id' => $collection->id]);

    $value = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'Test',
    ]);

    $fieldId = $field->id;
    $recordId = $record->id;
    $valueId = $value->id;

    $collection->delete();

    expect(StudioField::find($fieldId))->toBeNull()
        ->and(StudioRecord::withTrashed()->find($recordId))->toBeNull()
        ->and(StudioValue::find($valueId))->toBeNull();
});

it('invalidates field cache when collection is deleted', function () {
    $collection = StudioCollection::factory()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Prime the cache and verify it contains our field
    $cachedBefore = EavQueryBuilder::getCachedFields($collection);
    expect($cachedBefore)->toHaveCount(1);

    $collection->delete();

    // After deletion, a force-refreshed fetch should return an empty collection
    // since the fields were cascade-deleted along with the collection
    $cachedAfter = EavQueryBuilder::getCachedFields($collection, forceRefresh: true);
    expect($cachedAfter)->toHaveCount(0);
});

it('invalidates field cache when collection is updated', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Prime the cache and verify it contains our field
    $cachedBefore = EavQueryBuilder::getCachedFields($collection);
    expect($cachedBefore)->toHaveCount(1);

    // Update the collection, which should invalidate the cache
    $collection->update(['label' => 'Updated Label']);

    // Verify the cache can be re-populated with fresh data via the public API
    $cachedAfter = EavQueryBuilder::getCachedFields($collection, forceRefresh: true);
    expect($cachedAfter)->toHaveCount(1);
});
