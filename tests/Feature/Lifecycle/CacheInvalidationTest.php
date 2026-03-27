<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    EavQueryBuilder::invalidateFieldCache();
});

// ── 1. Collection updated → cache invalidated ──────────────────────

it('invalidates field cache when a collection is updated', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'products']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $cachedBefore = EavQueryBuilder::getCachedFields($collection);
    expect($cachedBefore)->toHaveCount(1);

    // Add another field without going through the model (bypasses boot hooks)
    StudioField::query()->insert([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'sku',
        'label' => 'SKU',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 2,
        'settings' => '{}',
    ]);

    // Cache still shows 1 field (stale)
    $stillCached = EavQueryBuilder::getCachedFields($collection);
    expect($stillCached)->toHaveCount(1);

    // Updating the collection triggers invalidation
    $collection->update(['name' => 'Updated Products']);

    // After invalidation, getCachedFields should re-fetch and see 2 fields
    $cachedAfter = EavQueryBuilder::getCachedFields($collection);
    expect($cachedAfter)->toHaveCount(2);
});

// ── 2. Collection deleted → cache invalidated ──────────────────────

it('invalidates field cache when a collection is deleted', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'temp']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Prime the cache
    $cachedBefore = EavQueryBuilder::getCachedFields($collection);
    expect($cachedBefore)->toHaveCount(1);

    $collectionId = $collection->id;

    // Delete triggers invalidation
    $collection->delete();

    // After deletion, the cache for this collection should be cleared.
    // Re-creating and querying should return fresh results (0 fields since collection was deleted).
    $freshCollection = new StudioCollection(['id' => $collectionId]);
    $freshCollection->id = $collectionId;
    $cachedAfter = EavQueryBuilder::getCachedFields($freshCollection);
    expect($cachedAfter)->toHaveCount(0);
});

// ── 3. Field created → cache invalidated ────────────────────────────

it('invalidates field cache when a field is created', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'articles']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $cachedBefore = EavQueryBuilder::getCachedFields($collection);
    expect($cachedBefore)->toHaveCount(1);

    // Creating a new field triggers invalidation via boot hook
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'body',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $cachedAfter = EavQueryBuilder::getCachedFields($collection);
    expect($cachedAfter)->toHaveCount(2);
});

// ── 4. Field updated → cache invalidated ────────────────────────────

it('invalidates field cache when a field is updated', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'orders']);
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'amount',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $cachedBefore = EavQueryBuilder::getCachedFields($collection);
    expect($cachedBefore)->toHaveCount(1);
    expect($cachedBefore->first()->column_name)->toBe('amount');

    // Insert a second field via query builder (bypasses boot hooks, won't invalidate)
    StudioField::query()->insert([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'currency',
        'label' => 'Currency',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 2,
        'settings' => '{}',
    ]);

    // Cache is stale — still shows 1
    $stillCached = EavQueryBuilder::getCachedFields($collection);
    expect($stillCached)->toHaveCount(1);

    // Updating an existing field triggers invalidation
    $field->update(['column_name' => 'total_amount']);

    // Cache should now reflect both fields
    $cachedAfter = EavQueryBuilder::getCachedFields($collection);
    expect($cachedAfter)->toHaveCount(2);
});

// ── 5. Field deleted → cache invalidated ────────────────────────────

it('invalidates field cache when a field is deleted', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'inventory']);
    $field1 = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'quantity',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'warehouse',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $cachedBefore = EavQueryBuilder::getCachedFields($collection);
    expect($cachedBefore)->toHaveCount(2);

    // Deleting a field triggers invalidation
    $field1->delete();

    $cachedAfter = EavQueryBuilder::getCachedFields($collection);
    expect($cachedAfter)->toHaveCount(1);
});

// ── 6. Multiple collections → isolated cache invalidation ──────────

it('does not affect another collections cache when invalidating one', function () {
    $collectionA = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'collection-a']);
    $collectionB = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'collection-b']);

    StudioField::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'field_a',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    StudioField::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 1,
        'column_name' => 'field_b',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Clear cache from field creation hooks, then prime both caches
    EavQueryBuilder::invalidateFieldCache();

    $cachedA = EavQueryBuilder::getCachedFields($collectionA);
    $cachedB = EavQueryBuilder::getCachedFields($collectionB);
    expect($cachedA)->toHaveCount(1);
    expect($cachedB)->toHaveCount(1);

    // Insert a field into collection A via raw query (bypasses boot hooks)
    StudioField::query()->insert([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'field_a2',
        'label' => 'Field A2',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 2,
        'settings' => '{}',
    ]);

    // Invalidate only collection A's cache
    EavQueryBuilder::invalidateFieldCache($collectionA->id);

    // Collection A re-fetches and sees 2 fields
    $cachedAAfter = EavQueryBuilder::getCachedFields($collectionA);
    expect($cachedAAfter)->toHaveCount(2);

    // Collection B still returns from cache (1 field), proving it was not affected
    $cachedBAfter = EavQueryBuilder::getCachedFields($collectionB);
    expect($cachedBAfter)->toHaveCount(1);
    expect($cachedBAfter)->toBe($cachedB);
});
