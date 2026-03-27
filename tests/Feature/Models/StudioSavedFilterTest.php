<?php

use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;
use Illuminate\Foundation\Auth\User;

it('creates a saved filter', function () {
    $collection = StudioCollection::factory()->create();
    $user = User::forceCreate([
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $filter = StudioSavedFilter::create([
        'collection_id' => $collection->id,
        'tenant_id' => null,
        'created_by' => $user->id,
        'name' => 'Published articles',
        'is_shared' => false,
        'filter_tree' => [
            'logic' => 'and',
            'rules' => [
                ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
            ],
        ],
    ]);

    expect($filter->exists)->toBeTrue();
    expect($filter->name)->toBe('Published articles');
    expect($filter->filter_tree)->toBeArray();
});

it('casts filter_tree to array', function () {
    $collection = StudioCollection::factory()->create();

    $filter = StudioSavedFilter::create([
        'collection_id' => $collection->id,
        'name' => 'Test',
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    $fresh = $filter->fresh();
    expect($fresh->filter_tree)->toBeArray();
    expect($fresh->filter_tree['logic'])->toBe('and');
});

it('belongs to a collection', function () {
    $collection = StudioCollection::factory()->create();

    $filter = StudioSavedFilter::create([
        'collection_id' => $collection->id,
        'name' => 'Test',
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    expect($filter->collection)->toBeInstanceOf(StudioCollection::class);
    expect($filter->collection->id)->toBe($collection->id);
});

it('converts filter_tree to FilterGroup', function () {
    $collection = StudioCollection::factory()->create();

    $filter = StudioSavedFilter::create([
        'collection_id' => $collection->id,
        'name' => 'Test',
        'filter_tree' => [
            'logic' => 'and',
            'rules' => [
                ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
            ],
        ],
    ]);

    $group = $filter->toFilterGroup();
    expect($group)->toBeInstanceOf(FilterGroup::class);
    expect($group->children)->toHaveCount(1);
});

it('scopes to visible filters for a user', function () {
    $collection = StudioCollection::factory()->create();
    $user = User::forceCreate([
        'name' => 'User1',
        'email' => 'user1@example.com',
        'password' => bcrypt('password'),
    ]);
    $otherUser = User::forceCreate([
        'name' => 'User2',
        'email' => 'user2@example.com',
        'password' => bcrypt('password'),
    ]);

    // User's own filter
    StudioSavedFilter::create([
        'collection_id' => $collection->id,
        'created_by' => $user->id,
        'name' => 'My filter',
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    // Other user's private filter
    StudioSavedFilter::create([
        'collection_id' => $collection->id,
        'created_by' => $otherUser->id,
        'name' => 'Their filter',
        'is_shared' => false,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    // Shared filter
    StudioSavedFilter::create([
        'collection_id' => $collection->id,
        'created_by' => $otherUser->id,
        'name' => 'Shared filter',
        'is_shared' => true,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    $visible = StudioSavedFilter::visibleTo($user->id)
        ->where('collection_id', $collection->id)
        ->get();

    expect($visible)->toHaveCount(2);
});
