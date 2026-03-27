<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Observers\RecordVersioningObserver;

// -- 1. Soft delete --

it('sets deleted_at when a record is soft-deleted', function () {
    $collection = StudioCollection::factory()->withSoftDeletes()->create();
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    expect($record->deleted_at)->toBeNull();

    $record->delete();

    expect($record->trashed())->toBeTrue()
        ->and($record->deleted_at)->not->toBeNull();
});

it('preserves values when a record is soft-deleted', function () {
    $collection = StudioCollection::factory()->withSoftDeletes()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'eav_cast' => 'text',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('preserved-data')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    $record->delete();

    expect($record->trashed())->toBeTrue();

    $values = StudioValue::where('record_id', $record->id)->get();
    expect($values)->toHaveCount(1)
        ->and($values->first()->val_text)->toBe('preserved-data');
});

it('excludes soft-deleted records from normal queries', function () {
    $collection = StudioCollection::factory()->withSoftDeletes()->create();
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    $record->delete();

    expect(StudioRecord::where('id', $record->id)->exists())->toBeFalse()
        ->and(StudioRecord::withTrashed()->where('id', $record->id)->exists())->toBeTrue();
});

// -- 2. Restore --

it('clears deleted_at when a soft-deleted record is restored', function () {
    $collection = StudioCollection::factory()->withSoftDeletes()->create();
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    $record->delete();
    expect($record->trashed())->toBeTrue();

    $record->restore();

    expect($record->trashed())->toBeFalse()
        ->and($record->deleted_at)->toBeNull();
});

it('makes restored record appear in normal queries again', function () {
    $collection = StudioCollection::factory()->withSoftDeletes()->create();
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    $record->delete();
    expect(StudioRecord::where('id', $record->id)->exists())->toBeFalse();

    $record->restore();
    expect(StudioRecord::where('id', $record->id)->exists())->toBeTrue();
});

// -- 3. Re-delete after restore --

it('can be soft-deleted again after being restored', function () {
    $collection = StudioCollection::factory()->withSoftDeletes()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'eav_cast' => 'text',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('survives-cycle')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    // First soft-delete
    $record->delete();
    expect($record->trashed())->toBeTrue();

    // Restore
    $record->restore();
    expect($record->trashed())->toBeFalse()
        ->and(StudioRecord::where('id', $record->id)->exists())->toBeTrue();

    // Second soft-delete
    $record->delete();
    expect($record->trashed())->toBeTrue()
        ->and(StudioRecord::where('id', $record->id)->exists())->toBeFalse()
        ->and(StudioRecord::withTrashed()->where('id', $record->id)->exists())->toBeTrue();

    // Values still preserved through the cycle
    $values = StudioValue::where('record_id', $record->id)->get();
    expect($values)->toHaveCount(1)
        ->and($values->first()->val_text)->toBe('survives-cycle');
});

// -- 4. Force delete --

it('permanently removes the record on force delete', function () {
    $collection = StudioCollection::factory()->withSoftDeletes()->create();
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    $record->forceDelete();

    expect(StudioRecord::withTrashed()->where('id', $record->id)->exists())->toBeFalse();
});

it('cascade-deletes values when a record is force-deleted', function () {
    $collection = StudioCollection::factory()->withSoftDeletes()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'email',
        'eav_cast' => 'text',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('gone@example.com')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    expect(StudioValue::where('record_id', $record->id)->count())->toBe(1);

    $record->forceDelete();

    expect(StudioValue::where('record_id', $record->id)->count())->toBe(0);
});

// -- 5. enable_soft_deletes flag --

it('soft-deletes regardless of the enable_soft_deletes collection flag', function () {
    // StudioRecord ALWAYS uses the SoftDeletes trait.
    // The collection's enable_soft_deletes flag is a configuration marker only,
    // not a behavioral gate. Records can be soft-deleted even when the flag is false.
    $collectionWithFlag = StudioCollection::factory()->withSoftDeletes()->create();
    $collectionWithoutFlag = StudioCollection::factory()->create(['enable_soft_deletes' => false]);

    $recordWithFlag = StudioRecord::factory()->create([
        'collection_id' => $collectionWithFlag->id,
    ]);
    $recordWithoutFlag = StudioRecord::factory()->create([
        'collection_id' => $collectionWithoutFlag->id,
    ]);

    $recordWithFlag->delete();
    $recordWithoutFlag->delete();

    // Both records are soft-deleted regardless of the flag
    expect($recordWithFlag->trashed())->toBeTrue()
        ->and($recordWithoutFlag->trashed())->toBeTrue()
        ->and(StudioRecord::withTrashed()->where('id', $recordWithFlag->id)->exists())->toBeTrue()
        ->and(StudioRecord::withTrashed()->where('id', $recordWithoutFlag->id)->exists())->toBeTrue();
});

it('can restore records regardless of the enable_soft_deletes collection flag', function () {
    // The flag does not prevent restore either — it is purely a configuration marker.
    $collection = StudioCollection::factory()->create(['enable_soft_deletes' => false]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    $record->delete();
    expect($record->trashed())->toBeTrue();

    $record->restore();
    expect($record->trashed())->toBeFalse()
        ->and(StudioRecord::where('id', $record->id)->exists())->toBeTrue();
});

// -- 6. Versioning interaction --

it('does not create a version snapshot on soft delete', function () {
    // RecordVersioningObserver only hooks into updating() and updated(),
    // not deleting() or deleted(). Soft-deleting should not trigger versioning.
    $collection = StudioCollection::factory()->withVersioning()->withSoftDeletes()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'content',
        'eav_cast' => 'text',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('versioned-content')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    // Ensure no versions exist before the delete
    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);

    $record->delete();

    // No version snapshot should have been created by the delete
    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);
});

it('does not create a version snapshot on force delete', function () {
    $collection = StudioCollection::factory()->withVersioning()->withSoftDeletes()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'content',
        'eav_cast' => 'text',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('permanent-delete')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);

    $record->forceDelete();

    // Versions cascade-deleted with the record, but more importantly,
    // the observer should not have fired for delete events
    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);
});
