<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Spatie\Permission\Models\Permission;

it('creates per-collection permissions when a collection is created', function () {
    $collection = StudioCollection::factory()->create(['slug' => 'articles']);

    $permissions = Permission::where('name', 'like', 'studio.collection.articles.%')->pluck('name')->toArray();

    expect($permissions)->toHaveCount(4)
        ->toContain('studio.collection.articles.viewRecords')
        ->toContain('studio.collection.articles.createRecord')
        ->toContain('studio.collection.articles.updateRecord')
        ->toContain('studio.collection.articles.deleteRecord');
});

it('updates permission names when a collection slug changes', function () {
    $collection = StudioCollection::factory()->create(['slug' => 'articles', 'name' => 'articles']);

    expect(Permission::where('name', 'like', 'studio.collection.articles.%')->count())->toBe(4);

    $collection->update(['slug' => 'blog-articles', 'name' => 'blog-articles']);

    expect(Permission::where('name', 'like', 'studio.collection.articles.%')->count())->toBe(0);
    expect(Permission::where('name', 'like', 'studio.collection.blog-articles.%')->count())->toBe(4);
});

it('removes per-collection permissions when a collection is deleted', function () {
    $collection = StudioCollection::factory()->create(['slug' => 'articles']);

    expect(Permission::where('name', 'like', 'studio.collection.articles.%')->count())->toBe(4);

    $collection->delete();

    expect(Permission::where('name', 'like', 'studio.collection.articles.%')->count())->toBe(0);
});
