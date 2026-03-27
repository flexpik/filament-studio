<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create();

    $this->statusField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'status',
        'field_type' => 'select',
        'eav_cast' => EavCast::Text,
        'is_filterable' => true,
    ]);

    $this->tagsField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'tags',
        'field_type' => 'tags',
        'eav_cast' => EavCast::Json,
        'is_filterable' => true,
    ]);

    // Record 1: published, tags: [php, laravel]
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->statusField->id, 'val_text' => 'published']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->tagsField->id, 'val_json' => ['php', 'laravel']]);

    // Record 2: draft, tags: [python, django]
    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->statusField->id, 'val_text' => 'draft']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->tagsField->id, 'val_json' => ['python', 'django']]);

    // Record 3: published, tags: [php, python]
    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->statusField->id, 'val_text' => 'published']);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->tagsField->id, 'val_json' => ['php', 'python']]);

    EavQueryBuilder::invalidateFieldCache();
});

it('applies a complex filter: published AND tags contains_any [laravel, django]', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
            ['field' => 'tags', 'operator' => 'contains_any', 'value' => ['laravel', 'django']],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->status)->toBe('published');
});

it('saves and loads a filter correctly', function () {
    $user = User::forceCreate([
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $treeArray = [
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
        ],
    ];

    $saved = StudioSavedFilter::create([
        'collection_id' => $this->collection->id,
        'created_by' => $user->id,
        'name' => 'Published only',
        'filter_tree' => $treeArray,
    ]);

    $loaded = StudioSavedFilter::findOrFail($saved->id);
    $tree = $loaded->toFilterGroup();

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});

it('handles json contains_all operator', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'tags', 'operator' => 'contains_all', 'value' => ['php', 'python']],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
});

it('handles json contains_none operator', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'tags', 'operator' => 'contains_none', 'value' => ['python']],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
});

it('handles deeply nested filter tree', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            [
                'logic' => 'or',
                'rules' => [
                    ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
                    [
                        'logic' => 'and',
                        'rules' => [
                            ['field' => 'status', 'operator' => 'eq', 'value' => 'draft'],
                            ['field' => 'tags', 'operator' => 'contains_any', 'value' => ['python']],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    // published (2) OR (draft AND tags has python) (1) = 3
    expect($results)->toHaveCount(3);
});

it('works with paginate() and filter tree', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->paginate(25);

    expect($results->total())->toBe(2);
});

it('works with toEloquentQuery() and filter tree', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
        ],
    ]);

    $query = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->toEloquentQuery();

    expect($query->count())->toBe(2);
});
