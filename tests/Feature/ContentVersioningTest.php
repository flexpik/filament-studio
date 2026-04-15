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

it('captures all locales in version snapshot', function () {
    config(['filament-studio.locales.enabled' => true]);
    config(['filament-studio.locales.available' => ['en', 'fr']]);
    config(['filament-studio.locales.default' => 'en']);

    $collection = StudioCollection::factory()->create([
        'enable_versioning' => true,
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'en',
    ]);

    $titleField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    $record = EavQueryBuilder::for($collection)->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product']);

    EavQueryBuilder::for($collection)->tenant(1)
        ->locale('fr')
        ->update($record->id, ['title' => 'Produit']);

    // Update EN to trigger versioning
    EavQueryBuilder::for($collection)->tenant(1)
        ->locale('en')
        ->update($record->id, ['title' => 'Product v2']);

    $version = StudioRecordVersion::where('record_id', $record->id)
        ->orderByDesc('id')
        ->first();

    // Snapshot should have nested locale structure for translatable fields
    expect($version->snapshot['title'])->toBeArray()
        ->and($version->snapshot['title']['en'])->toBe('Product v2')
        ->and($version->snapshot['title']['fr'])->toBe('Produit');
});

it('restores multi-locale version snapshot correctly', function () {
    config(['filament-studio.locales.enabled' => true]);
    config(['filament-studio.locales.available' => ['en', 'fr']]);
    config(['filament-studio.locales.default' => 'en']);

    $collection = StudioCollection::factory()->create([
        'enable_versioning' => true,
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'en',
    ]);

    $titleField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    // Create record with EN
    $record = EavQueryBuilder::for($collection)->tenant(1)
        ->locale('en')
        ->create(['title' => 'Original EN']);

    // Add FR
    EavQueryBuilder::for($collection)->tenant(1)
        ->locale('fr')
        ->update($record->id, ['title' => 'Original FR']);

    // Update EN (creates version snapshot of "Original EN" + "Original FR")
    EavQueryBuilder::for($collection)->tenant(1)
        ->locale('en')
        ->update($record->id, ['title' => 'Updated EN']);

    // Get the version that captured "Original" state
    $version = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('created_at')
        ->first();

    // Restore that version
    EavQueryBuilder::for($collection)->tenant(1)
        ->restoreFromVersion($record->uuid, $version->id);

    // Verify both locales were restored
    $enData = EavQueryBuilder::for($collection)->locale('en')->getRecordData($record);
    $frData = EavQueryBuilder::for($collection)->locale('fr')->getRecordData($record);

    expect($enData['title'])->toBe('Original EN')
        ->and($frData['title'])->toBe('Original FR');
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

it('captures mixed translatable and non-translatable fields in snapshot', function () {
    config(['filament-studio.locales.enabled' => true]);
    config(['filament-studio.locales.available' => ['en', 'fr']]);
    config(['filament-studio.locales.default' => 'en']);

    $collection = StudioCollection::factory()->create([
        'enable_versioning' => true,
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'en',
    ]);

    $titleField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    $priceField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
        'is_translatable' => false,
    ]);

    $record = EavQueryBuilder::for($collection)->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    EavQueryBuilder::for($collection)->tenant(1)
        ->locale('fr')
        ->update($record->id, ['title' => 'Produit']);

    // Trigger version snapshot
    EavQueryBuilder::for($collection)->tenant(1)
        ->locale('en')
        ->update($record->id, ['title' => 'Product v2']);

    $version = StudioRecordVersion::where('record_id', $record->id)
        ->orderByDesc('id')
        ->first();

    // Translatable field has nested locale structure
    expect($version->snapshot['title'])->toBeArray()
        ->and($version->snapshot['title']['en'])->toBe('Product v2')
        ->and($version->snapshot['title']['fr'])->toBe('Produit');

    // Non-translatable field has flat value
    expect($version->snapshot['price'])->not->toBeArray();
});

it('skips removed fields during version restore', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->nameField->id,
        'val_text' => 'Current',
    ]);

    // Create a version with a field that no longer exists
    $version = StudioRecordVersion::create([
        'record_id' => $record->id,
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'snapshot' => [
            'name' => 'Original',
            'removed_field' => 'some value', // This field doesn't exist
        ],
        'created_at' => now(),
    ]);

    // Should not throw, should just skip the removed field
    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->restoreFromVersion($record->uuid, $version->id);

    $nameValue = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->nameField->id)
        ->first();

    expect($nameValue->val_text)->toBe('Original');
});

it('does not create duplicate version when snapshot is identical', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->nameField->id,
        'val_text' => 'Same Name',
    ]);

    $observer = new RecordVersioningObserver;

    $observer->updating($record);
    $observer->updating($record); // Same data, no changes between calls

    $versions = StudioRecordVersion::where('record_id', $record->id)->get();
    expect($versions)->toHaveCount(1);
});

it('does not create version for record with no values', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    // No StudioValue records exist

    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    expect(StudioRecordVersion::where('record_id', $record->id)->count())->toBe(0);
});

it('creates version of current state before restoring', function () {
    config(['filament-studio.locales.enabled' => true]);
    config(['filament-studio.locales.available' => ['en', 'fr']]);
    config(['filament-studio.locales.default' => 'en']);

    $collection = StudioCollection::factory()->create([
        'enable_versioning' => true,
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'en',
    ]);

    $titleField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    $record = EavQueryBuilder::for($collection)->tenant(1)
        ->locale('en')
        ->create(['title' => 'V1 EN']);

    EavQueryBuilder::for($collection)->tenant(1)
        ->locale('fr')
        ->update($record->id, ['title' => 'V1 FR']);

    // Update to create first version
    EavQueryBuilder::for($collection)->tenant(1)
        ->locale('en')
        ->update($record->id, ['title' => 'V2 EN']);

    $versionCountBefore = StudioRecordVersion::where('record_id', $record->id)->count();

    // Get first version and restore
    $firstVersion = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('created_at')
        ->first();

    EavQueryBuilder::for($collection)->tenant(1)
        ->restoreFromVersion($record->uuid, $firstVersion->id);

    $versionCountAfter = StudioRecordVersion::where('record_id', $record->id)->count();

    // Restore should have created a version of the state before restoring
    expect($versionCountAfter)->toBeGreaterThan($versionCountBefore);
});

it('captures non-text field types in snapshot correctly', function () {
    $collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'enable_versioning' => true,
    ]);

    $intField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'quantity',
        'field_type' => 'integer',
        'eav_cast' => 'integer',
    ]);

    $decimalField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
    ]);

    $boolField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'active',
        'field_type' => 'boolean',
        'eav_cast' => 'boolean',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $intField->id,
        'val_integer' => 42,
        'locale' => 'en',
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $decimalField->id,
        'val_decimal' => 19.99,
        'locale' => 'en',
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $boolField->id,
        'val_boolean' => true,
        'locale' => 'en',
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->snapshot['quantity'])->toBe(42)
        ->and((float) $version->snapshot['price'])->toBe(19.99)
        ->and($version->snapshot['active'])->toBeTrue()
        ->and($version->created_at)->not->toBeNull();
});

it('records created_at timestamp on version', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);

    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->nameField->id,
        'val_text' => 'Test',
        'locale' => 'en',
    ]);

    $observer = new RecordVersioningObserver;
    $observer->updating($record);

    $version = StudioRecordVersion::where('record_id', $record->id)->first();

    expect($version->created_at)->not->toBeNull()
        ->and($version->collection_id)->toBe($this->collection->id)
        ->and($version->tenant_id)->toBe(1);
});
