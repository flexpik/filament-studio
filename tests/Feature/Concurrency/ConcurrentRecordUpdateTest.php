<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Observers\RecordVersioningObserver;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->withVersioning()->create([
        'tenant_id' => 1,
        'slug' => 'products',
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

    $this->priceField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
});

it('creates both version snapshots on sequential double-update with versioning', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    $nameValue = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->nameField->id,
        'val_text' => 'Original Product',
    ]);

    $statusValue = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->statusField->id,
        'val_text' => 'draft',
    ]);

    $observer = new RecordVersioningObserver;

    // First update: snapshot before changing name
    $observer->updating($record);
    $nameValue->update(['val_text' => 'Updated Product']);

    // Second update: snapshot before changing status
    $observer->updating($record);
    $statusValue->update(['val_text' => 'published']);

    $versions = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('id')
        ->get();

    expect($versions)->toHaveCount(2)
        ->and($versions[0]->snapshot)->toBe([
            'name' => 'Original Product',
            'status' => 'draft',
        ])
        ->and($versions[1]->snapshot)->toBe([
            'name' => 'Updated Product',
            'status' => 'draft',
        ]);
});

it('handles interleaved update pattern with two simulated concurrent readers', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    $nameValue = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->nameField->id,
        'val_text' => 'Initial Name',
    ]);

    $statusValue = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->statusField->id,
        'val_text' => 'draft',
    ]);

    $observer = new RecordVersioningObserver;

    // Two "concurrent" readers both see the initial state
    $readerOneState = $record->fresh();
    $readerTwoState = $record->fresh();

    // Reader one applies update 1: change name
    $observer->updating($readerOneState);
    $nameValue->update(['val_text' => 'Reader One Name']);

    // Reader two applies update 2: change status
    $observer->updating($readerTwoState);
    $statusValue->update(['val_text' => 'published']);

    // Verify final state reflects last-write-wins
    $nameValue->refresh();
    $statusValue->refresh();

    expect($nameValue->val_text)->toBe('Reader One Name')
        ->and($statusValue->val_text)->toBe('published');

    // Verify both version snapshots exist
    $versions = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('id')
        ->get();

    expect($versions)->toHaveCount(2)
        ->and($versions[0]->snapshot)->toHaveKey('name', 'Initial Name')
        ->and($versions[0]->snapshot)->toHaveKey('status', 'draft')
        ->and($versions[1]->snapshot)->toHaveKey('name', 'Reader One Name')
        ->and($versions[1]->snapshot)->toHaveKey('status', 'draft');
});

it('preserves changes to different fields across sequential updates', function () {
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

    $priceValue = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->priceField->id,
        'val_text' => '10.00',
    ]);

    // Update 1: change only name (field A)
    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'New Name']);

    // Update 2: change only price (field B)
    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['price' => '25.00']);

    // Both changes should be reflected (last-write-wins on each field)
    $nameValue->refresh();
    $statusValue->refresh();
    $priceValue->refresh();

    expect($nameValue->val_text)->toBe('New Name')
        ->and($statusValue->val_text)->toBe('draft')
        ->and($priceValue->val_text)->toBe('25.00');
});

it('produces versions ordered by creation time on multiple rapid updates', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    $nameValue = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->nameField->id,
        'val_text' => 'v1',
    ]);

    $observer = new RecordVersioningObserver;

    $timestamps = [
        '2026-03-22 10:00:00',
        '2026-03-22 10:00:01',
        '2026-03-22 10:00:02',
        '2026-03-22 10:00:03',
    ];

    foreach ($timestamps as $i => $timestamp) {
        $this->travelTo(now()->parse($timestamp));

        $observer->updating($record);
        $nameValue->update(['val_text' => 'v'.($i + 2)]);
    }

    $versions = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('created_at')
        ->get();

    expect($versions)->toHaveCount(4);

    // Verify versions are chronologically ordered
    $previousTime = null;
    foreach ($versions as $version) {
        if ($previousTime !== null) {
            expect($version->created_at->gte($previousTime))->toBeTrue();
        }
        $previousTime = $version->created_at;
    }

    // Verify snapshot content matches the progression
    expect($versions[0]->snapshot['name'])->toBe('v1')
        ->and($versions[1]->snapshot['name'])->toBe('v2')
        ->and($versions[2]->snapshot['name'])->toBe('v3')
        ->and($versions[3]->snapshot['name'])->toBe('v4');
});
