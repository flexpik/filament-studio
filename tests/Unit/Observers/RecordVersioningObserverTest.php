<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Observers\RecordVersioningObserver;
use Illuminate\Foundation\Auth\User;

it('creates version snapshot when record is updated with versioning enabled', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'Hello World',
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version)->not->toBeNull()
        ->and($version->snapshot)->toBe(['title' => 'Hello World']);
});

it('skips versioning when collection has versioning disabled', function () {
    $collection = StudioCollection::factory()->create([
        'enable_versioning' => false,
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);
});

it('skips duplicate snapshot if latest version is identical', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'status',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'draft',
    ]);

    $observer = new RecordVersioningObserver;

    // First snapshot
    $observer->updated($record);
    // Second identical snapshot should be skipped
    $observer->updated($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(1);
});

it('creates new version when data changes between snapshots', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'status',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    $value = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'draft',
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    // Change the value
    $value->update(['val_text' => 'published']);
    $observer->updated($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(2);
});

it('handles record with no values gracefully', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    // Empty snapshot should be skipped
    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);
});

it('skips versioning when collection does not exist', function () {
    $collection = StudioCollection::factory()->create(['enable_versioning' => false]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    // Delete the collection so StudioCollection::find() returns null
    $collection->delete();

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);
});

it('stores created_by from authenticated user', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'Test',
    ]);

    // Act as a specific user
    $user = new User;
    $user->id = 77;
    auth()->login($user);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->created_by)->toBe(77);

    auth()->logout();
});

it('stores created_at timestamp in version record', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'Test',
    ]);

    $this->travelTo(now()->parse('2026-03-22 10:00:00'));

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->created_at->toDateTimeString())->toBe('2026-03-22 10:00:00');
});

it('correctly snapshots integer field values', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'count',
        'eav_cast' => 'integer',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_integer' => 42,
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->snapshot)->toBe(['count' => 42]);
});

it('correctly snapshots decimal field values', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'price',
        'eav_cast' => 'decimal',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_decimal' => 19.99,
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->snapshot['price'])->toBe(19.99);
});

it('correctly snapshots boolean field values', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'is_active',
        'eav_cast' => 'boolean',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_boolean' => true,
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->snapshot['is_active'])->toBeTruthy();
});

it('correctly snapshots datetime field values', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'published_at',
        'eav_cast' => 'datetime',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_datetime' => '2026-01-15 10:00:00',
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->snapshot['published_at'])->toContain('2026-01-15');
});

it('correctly snapshots json field values', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'tags',
        'eav_cast' => 'json',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_json' => ['tag1', 'tag2'],
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->snapshot['tags'])->toBe(['tag1', 'tag2']);
});

it('calls captureSnapshot via updating hook', function () {
    $collection = StudioCollection::factory()->withVersioning()->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'Before Update',
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version)->not->toBeNull()
        ->and($version->snapshot)->toBe(['title' => 'Before Update']);
});

it('stores tenant_id and collection_id in version record', function () {
    $collection = StudioCollection::factory()->withVersioning()->create([
        'tenant_id' => 42,
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 42,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_text' => 'Test',
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updated($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->tenant_id)->toBe(42)
        ->and($version->collection_id)->toBe($collection->id);
});
