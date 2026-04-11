<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Support\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

it('syncs per-collection permissions for all collections', function () {
    StudioCollection::factory()->create(['slug' => 'products']);
    StudioCollection::factory()->create(['slug' => 'blog-posts']);

    PermissionRegistrar::syncCollectionPermissions();

    $permissions = Permission::where('name', 'like', 'studio.collection.%')->pluck('name')->sort()->values()->toArray();

    expect($permissions)->toContain('studio.collection.products.viewRecords')
        ->toContain('studio.collection.products.createRecord')
        ->toContain('studio.collection.products.updateRecord')
        ->toContain('studio.collection.products.deleteRecord')
        ->toContain('studio.collection.blog-posts.viewRecords')
        ->toContain('studio.collection.blog-posts.createRecord')
        ->toContain('studio.collection.blog-posts.updateRecord')
        ->toContain('studio.collection.blog-posts.deleteRecord');
});

it('removes permissions for deleted collections', function () {
    $collection = StudioCollection::factory()->create(['slug' => 'products']);

    PermissionRegistrar::syncCollectionPermissions();

    expect(Permission::where('name', 'like', 'studio.collection.products.%')->count())->toBe(4);

    $collection->delete();

    PermissionRegistrar::syncCollectionPermissions();

    expect(Permission::where('name', 'like', 'studio.collection.products.%')->count())->toBe(0);
});

it('syncs permissions for a single collection', function () {
    $collection = StudioCollection::factory()->create(['slug' => 'products']);

    PermissionRegistrar::syncForCollection($collection);

    $permissions = Permission::where('name', 'like', 'studio.collection.products.%')->pluck('name')->sort()->values()->toArray();

    expect($permissions)->toHaveCount(4)
        ->toContain('studio.collection.products.viewRecords');
});

it('removes permissions for a single collection', function () {
    $collection = StudioCollection::factory()->create(['slug' => 'products']);

    PermissionRegistrar::syncForCollection($collection);
    expect(Permission::where('name', 'like', 'studio.collection.products.%')->count())->toBe(4);

    PermissionRegistrar::removeForCollection($collection);
    expect(Permission::where('name', 'like', 'studio.collection.products.%')->count())->toBe(0);
});
