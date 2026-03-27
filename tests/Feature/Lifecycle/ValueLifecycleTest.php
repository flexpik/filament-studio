<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Observers\RecordVersioningObserver;

// -- 1. Value update triggers versioning --

it('creates a version snapshot when a record is updated and versioning is enabled', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'eav_cast' => 'text',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('Hello')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    $versions = StudioRecordVersion::where('record_id', $record->id)->get();

    expect($versions)->toHaveCount(1)
        ->and($versions->first()->snapshot)->toBe(['title' => 'Hello']);
});

it('does not create a version snapshot when versioning is disabled', function () {
    $collection = StudioCollection::factory()->create(['enable_versioning' => false]);
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'eav_cast' => 'text',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('Hello')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    $versions = StudioRecordVersion::where('record_id', $record->id)->get();

    expect($versions)->toHaveCount(0);
});

// -- 2. Field type change impact --

it('retains old typed column data when field eav_cast changes', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'age',
        'eav_cast' => 'text',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    $value = StudioValue::factory()->withText('forty-two')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    // Change the field's eav_cast from text to integer
    $field->update(['eav_cast' => 'integer']);
    $field->refresh();

    // The StudioValue row should retain its old val_text data
    $value->refresh();
    expect($value->val_text)->toBe('forty-two')
        ->and($value->val_integer)->toBeNull();
});

it('does not auto-populate the new cast column after eav_cast change', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'count',
        'eav_cast' => 'integer',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    $value = StudioValue::factory()->withInteger(99)->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    // Change field from integer to text
    $field->update(['eav_cast' => 'text']);
    $field->refresh();

    $value->refresh();
    expect($value->val_integer)->toBe(99)
        ->and($value->val_text)->toBeNull();
});

// -- 3. Cascade on record delete --

it('cascade-deletes value rows when a record is force-deleted', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('ephemeral')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    expect(StudioValue::where('record_id', $record->id)->count())->toBe(1);

    $record->forceDelete();

    expect(StudioValue::where('record_id', $record->id)->count())->toBe(0);
});

it('retains value rows when a record is soft-deleted', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('preserved')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    $record->delete(); // soft delete

    expect($record->trashed())->toBeTrue()
        ->and(StudioValue::where('record_id', $record->id)->count())->toBe(1);
});

// -- 4. Cascade on field delete --

it('cascade-deletes value rows when a field is deleted', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('doomed')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    expect(StudioValue::where('field_id', $field->id)->count())->toBe(1);

    $field->delete();

    expect(StudioValue::where('field_id', $field->id)->count())->toBe(0);
});

it('cascade-deletes only values for the deleted field, not others', function () {
    $collection = StudioCollection::factory()->create();
    $fieldA = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
    ]);
    $fieldB = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'email',
    ]);
    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);
    StudioValue::factory()->withText('Alice')->create([
        'record_id' => $record->id,
        'field_id' => $fieldA->id,
    ]);
    StudioValue::factory()->withText('alice@example.com')->create([
        'record_id' => $record->id,
        'field_id' => $fieldB->id,
    ]);

    expect(StudioValue::where('record_id', $record->id)->count())->toBe(2);

    $fieldA->delete();

    $remaining = StudioValue::where('record_id', $record->id)->get();
    expect($remaining)->toHaveCount(1)
        ->and($remaining->first()->val_text)->toBe('alice@example.com');
});
