<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Observers\RecordVersioningObserver;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'slug' => 'articles',
        'enable_versioning' => true,
    ]);

    $this->nameField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $this->statusField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'status',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
});

it('creates a version snapshot when a record is updated', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->nameField->id,
        'val_text' => 'Original Name',
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->statusField->id,
        'val_text' => 'draft',
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    $versions = StudioRecordVersion::where('record_id', $record->id)->get();
    expect($versions)->toHaveCount(1)
        ->and($versions->first()->snapshot)->toBe([
            'name' => 'Original Name',
            'status' => 'draft',
        ])
        ->and($versions->first()->collection_id)->toBe($this->collection->id)
        ->and($versions->first()->tenant_id)->toBe(1);
});

it('does not create versions for non-versioned collections', function () {
    $nonVersionedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'slug' => 'tasks',
        'enable_versioning' => false,
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $nonVersionedCollection->id,
        'tenant_id' => 1,
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);
});

it('accumulates multiple versions on successive updates', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    $nameValue = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->nameField->id,
        'val_text' => 'Version 1',
    ]);

    $observer = new RecordVersioningObserver;

    $observer->updating($record);

    $nameValue->update(['val_text' => 'Version 2']);

    $observer->updating($record);

    $versions = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('id')
        ->get();

    expect($versions)->toHaveCount(2)
        ->and($versions->first()->snapshot['name'])->toBe('Version 1')
        ->and($versions->last()->snapshot['name'])->toBe('Version 2');
});

it('can restore a record from a version snapshot', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    $nameValue = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->nameField->id,
        'val_text' => 'Original',
    ]);
    $statusValue = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->statusField->id,
        'val_text' => 'draft',
    ]);

    $version = StudioRecordVersion::create([
        'record_id' => $record->id,
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'snapshot' => ['name' => 'Original', 'status' => 'draft'],
        'created_at' => now(),
    ]);

    $nameValue->update(['val_text' => 'Changed']);
    $statusValue->update(['val_text' => 'published']);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->restoreFromVersion($record->uuid, $version->id);

    $nameValue->refresh();
    $statusValue->refresh();

    expect($nameValue->val_text)->toBe('Original')
        ->and($statusValue->val_text)->toBe('draft');
});
