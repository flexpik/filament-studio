<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    $this->field = null;
});

/**
 * Helper to create a collection with a text field.
 *
 * @return array{collection: StudioCollection, field: StudioField}
 */
function createCollectionWithField(bool $singleton = false, int $tenantId = 1): array
{
    $factory = StudioCollection::factory()->forTenant($tenantId);

    if ($singleton) {
        $factory = $factory->singleton();
    }

    $collection = $factory->create();

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => $tenantId,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    return ['collection' => $collection, 'field' => $field];
}

// -----------------------------------------------------------------
// GAP DOCUMENTATION:
// EavQueryBuilder::create() does NOT enforce singleton constraints.
// The is_singleton flag on StudioCollection is not checked during
// record creation at the query-builder level. Multiple records can
// be created for a singleton collection. Enforcement likely happens
// at the Filament UI / resource layer instead.
// -----------------------------------------------------------------

it('creates the first record in a singleton collection successfully', function () {
    ['collection' => $collection, 'field' => $field] = createCollectionWithField(singleton: true);

    $record = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->create(['title' => 'Only Record']);

    expect($record)->toBeInstanceOf(StudioRecord::class)
        ->and($record->collection_id)->toBe($collection->id)
        ->and($record->tenant_id)->toBe(1);

    // Verify the value was stored
    $data = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->getRecordData($record);

    expect($data['title'])->toBe('Only Record');
});

it('does not enforce singleton at query-builder level — multiple records allowed', function () {
    // GAP: EavQueryBuilder::create() does not check is_singleton.
    // This test documents the current behavior where a second record
    // is successfully created even though the collection is a singleton.
    ['collection' => $collection, 'field' => $field] = createCollectionWithField(singleton: true);

    $first = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->create(['title' => 'First']);

    $second = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->create(['title' => 'Second']);

    expect($first->id)->not->toBe($second->id);

    $count = StudioRecord::where('collection_id', $collection->id)
        ->where('tenant_id', 1)
        ->count();

    expect($count)->toBe(2);
});

it('allows multiple records in a non-singleton collection', function () {
    ['collection' => $collection, 'field' => $field] = createCollectionWithField(singleton: false);

    $records = [];
    for ($i = 1; $i <= 3; $i++) {
        $records[] = EavQueryBuilder::for($collection)
            ->tenant(1)
            ->create(['title' => "Record {$i}"]);
    }

    $count = StudioRecord::where('collection_id', $collection->id)
        ->where('tenant_id', 1)
        ->count();

    expect($count)->toBe(3)
        ->and($records[0]->id)->not->toBe($records[1]->id)
        ->and($records[1]->id)->not->toBe($records[2]->id);
});

it('allows re-creation in a singleton collection after the sole record is deleted', function () {
    ['collection' => $collection, 'field' => $field] = createCollectionWithField(singleton: true);

    $first = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->create(['title' => 'Original']);

    // Delete the record
    EavQueryBuilder::for($collection)
        ->tenant(1)
        ->delete($first->id);

    // Verify record is gone
    $count = StudioRecord::where('collection_id', $collection->id)
        ->where('tenant_id', 1)
        ->count();

    expect($count)->toBe(0);

    // Re-create
    $second = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->create(['title' => 'Replacement']);

    expect($second)->toBeInstanceOf(StudioRecord::class);

    $data = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->getRecordData($second);

    expect($data['title'])->toBe('Replacement');

    $count = StudioRecord::where('collection_id', $collection->id)
        ->where('tenant_id', 1)
        ->count();

    expect($count)->toBe(1);
});

it('isolates singleton enforcement per tenant — different tenants can each have a record', function () {
    // Even if singleton enforcement existed, each tenant should have
    // its own independent singleton scope.
    $collectionT1 = StudioCollection::factory()->forTenant(1)->singleton()->create([
        'slug' => 'settings',
    ]);

    $collectionT2 = StudioCollection::factory()->forTenant(2)->singleton()->create([
        'slug' => 'settings-t2',
    ]);

    StudioField::factory()->create([
        'collection_id' => $collectionT1->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    StudioField::factory()->create([
        'collection_id' => $collectionT2->id,
        'tenant_id' => 2,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $recordT1 = EavQueryBuilder::for($collectionT1)
        ->tenant(1)
        ->create(['title' => 'Tenant 1 Settings']);

    $recordT2 = EavQueryBuilder::for($collectionT2)
        ->tenant(2)
        ->create(['title' => 'Tenant 2 Settings']);

    expect($recordT1->tenant_id)->toBe(1)
        ->and($recordT2->tenant_id)->toBe(2)
        ->and($recordT1->id)->not->toBe($recordT2->id);
});

it('stores correct values when creating records in a singleton collection', function () {
    $collection = StudioCollection::factory()->forTenant(1)->singleton()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'site_name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'maintenance_mode',
        'field_type' => 'toggle',
        'eav_cast' => 'boolean',
    ]);

    $record = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->create([
            'site_name' => 'My Application',
            'maintenance_mode' => true,
        ]);

    $data = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->getRecordData($record);

    expect($data['site_name'])->toBe('My Application')
        ->and($data['maintenance_mode'])->toBe(true);
});
