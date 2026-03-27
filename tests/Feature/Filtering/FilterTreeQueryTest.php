<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create();

    $this->statusField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'status',
        'field_type' => 'select',
        'eav_cast' => EavCast::Text,
    ]);

    $this->priorityField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'priority',
        'field_type' => 'integer',
        'eav_cast' => EavCast::Integer,
    ]);

    $this->publishDateField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'publish_date',
        'field_type' => 'datetime',
        'eav_cast' => EavCast::Datetime,
    ]);

    // Record 1: published, priority 5, recent
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->statusField->id, 'val_text' => 'published']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->priorityField->id, 'val_integer' => 5]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->publishDateField->id, 'val_datetime' => now()->subDays(2)]);

    // Record 2: draft, priority 1, old
    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->statusField->id, 'val_text' => 'draft']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->priorityField->id, 'val_integer' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->publishDateField->id, 'val_datetime' => now()->subMonths(3)]);

    // Record 3: published, priority 3, recent
    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->statusField->id, 'val_text' => 'published']);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->priorityField->id, 'val_integer' => 3]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->publishDateField->id, 'val_datetime' => now()->subDays(1)]);

    EavQueryBuilder::invalidateFieldCache();
});

it('filters with a simple eq rule', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});

it('filters with AND logic combining multiple rules', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
            ['field' => 'priority', 'operator' => 'gt', 'value' => 3],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
});

it('filters with OR logic', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'or',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'draft'],
            ['field' => 'priority', 'operator' => 'gte', 'value' => 5],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});

it('handles nested groups', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
            [
                'logic' => 'or',
                'rules' => [
                    ['field' => 'priority', 'operator' => 'gte', 'value' => 5],
                    ['field' => 'priority', 'operator' => 'lte', 'value' => 2],
                ],
            ],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    // published AND (priority >= 5 OR priority <= 2) → only record 1
    expect($results)->toHaveCount(1);
});

it('handles contains operator for text fields', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'contains', 'value' => 'pub'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});

it('handles is_null operator', function () {
    // Create a record with no status value
    $r4 = StudioRecord::factory()->create(['collection_id' => $this->collection->id]);
    StudioValue::factory()->create(['record_id' => $r4->id, 'field_id' => $this->priorityField->id, 'val_integer' => 1]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'is_null'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
});

it('handles between operator for numeric fields', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'priority', 'operator' => 'between', 'value' => [2, 4]],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
});

it('skips empty filter trees', function () {
    $tree = FilterGroup::empty();

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(3);
});

it('handles neq operator', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'neq', 'value' => 'published'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
});

it('handles in operator', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'priority', 'operator' => 'in', 'value' => [1, 5]],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});

it('handles starts_with operator', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'status', 'operator' => 'starts_with', 'value' => 'pub'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});

it('resolves dynamic $NOW variable in filter rules', function () {
    $this->travelTo(now());

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'publish_date', 'operator' => 'gte', 'value' => '$NOW(-7 days)'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});
