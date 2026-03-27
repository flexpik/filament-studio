<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    EavQueryBuilder::invalidateFieldCache();
});

it('isolates fields so tenant A fields are not visible when querying tenant B collection fields', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create(['slug' => 'products']);
    $collectionB = StudioCollection::factory()->forTenant(2)->create(['slug' => 'products']);

    StudioField::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    StudioField::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'field_type' => 'number',
        'eav_cast' => 'decimal',
    ]);

    StudioField::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $fieldsA = StudioField::where('collection_id', $collectionA->id)->get();
    $fieldsB = StudioField::where('collection_id', $collectionB->id)->get();

    expect($fieldsA)->toHaveCount(2)
        ->and($fieldsA->pluck('column_name')->toArray())->toEqualCanonicalizing(['name', 'price'])
        ->and($fieldsB)->toHaveCount(1)
        ->and($fieldsB->first()->column_name)->toBe('title');
});

it('isolates values so tenant A cannot read tenant B values even knowing the record id', function () {
    $collectionB = StudioCollection::factory()->forTenant(2)->create();
    $fieldB = StudioField::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
        'column_name' => 'secret',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $recordB = StudioRecord::factory()->forTenant(2)->create([
        'collection_id' => $collectionB->id,
    ]);
    StudioValue::factory()->create([
        'record_id' => $recordB->id,
        'field_id' => $fieldB->id,
        'val_text' => 'Secret Data',
    ]);

    // Tenant A tries to query tenant B's collection using tenant A's tenant_id
    $results = EavQueryBuilder::for($collectionB)
        ->tenant(1)
        ->select(['secret'])
        ->get();

    expect($results)->toHaveCount(0);

    // Also verify that querying records directly with forTenant scope excludes them
    $directRecords = StudioRecord::where('collection_id', $collectionB->id)
        ->forTenant(1)
        ->get();

    expect($directRecords)->toHaveCount(0);
});

it('isolates collection mutations so updating tenant A collection does not affect tenant B collection with same slug', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create([
        'slug' => 'products',
        'name' => 'Products',
        'description' => 'Tenant A products',
    ]);
    $collectionB = StudioCollection::factory()->forTenant(2)->create([
        'slug' => 'products',
        'name' => 'Products',
        'description' => 'Tenant B products',
    ]);

    $collectionA->update([
        'name' => 'Updated Products',
        'description' => 'Updated description for A',
    ]);

    $collectionB->refresh();

    expect($collectionA->fresh()->name)->toBe('Updated Products')
        ->and($collectionA->fresh()->description)->toBe('Updated description for A')
        ->and($collectionB->name)->toBe('Products')
        ->and($collectionB->description)->toBe('Tenant B products');
});

it('isolates record creation so a record in tenant A collection does not appear in tenant B queries', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create(['slug' => 'tasks']);
    $collectionB = StudioCollection::factory()->forTenant(2)->create(['slug' => 'tasks']);

    $fieldA = StudioField::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $fieldB = StudioField::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Create a record in tenant A's collection
    $recordA = StudioRecord::factory()->forTenant(1)->create([
        'collection_id' => $collectionA->id,
    ]);
    StudioValue::factory()->create([
        'record_id' => $recordA->id,
        'field_id' => $fieldA->id,
        'val_text' => 'Tenant A Task',
    ]);

    // Query tenant B's collection — should return nothing
    $resultsB = EavQueryBuilder::for($collectionB)
        ->tenant(2)
        ->select(['title'])
        ->get();

    expect($resultsB)->toHaveCount(0);

    // Query tenant A's collection — should return the record
    $resultsA = EavQueryBuilder::for($collectionA)
        ->tenant(1)
        ->select(['title'])
        ->get();

    expect($resultsA)->toHaveCount(1)
        ->and($resultsA->first()->title)->toBe('Tenant A Task');
});

it('isolates field cache across tenants using separate collection IDs', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create(['slug' => 'items']);
    $collectionB = StudioCollection::factory()->forTenant(2)->create(['slug' => 'items']);

    StudioField::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    StudioField::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'color',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    StudioField::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
        'column_name' => 'sku',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Invalidate to start fresh
    EavQueryBuilder::invalidateFieldCache();

    // Cache fields for collection A
    $cachedFieldsA = EavQueryBuilder::getCachedFields($collectionA);

    // Cache fields for collection B
    $cachedFieldsB = EavQueryBuilder::getCachedFields($collectionB);

    // Verify they are separate caches with correct counts
    expect($cachedFieldsA)->toHaveCount(2)
        ->and($cachedFieldsA->pluck('column_name')->toArray())->toEqualCanonicalizing(['name', 'color'])
        ->and($cachedFieldsB)->toHaveCount(1)
        ->and($cachedFieldsB->first()->column_name)->toBe('sku');

    // Invalidating one collection's cache should not affect the other
    EavQueryBuilder::invalidateFieldCache($collectionA->id);

    // Add a new field to collection A
    StudioField::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'size',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Re-fetch collection A's cache (should now include 3 fields)
    $refreshedA = EavQueryBuilder::getCachedFields($collectionA);

    // Collection B's cache should still have 1 field (unchanged)
    $stillCachedB = EavQueryBuilder::getCachedFields($collectionB);

    expect($refreshedA)->toHaveCount(3)
        ->and($stillCachedB)->toHaveCount(1);
});
