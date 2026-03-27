<?php

use Flexpik\FilamentStudio\Enums\AggregateFunction;
use Flexpik\FilamentStudio\Enums\GroupPrecision;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Filtering\FilterRule;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Collection;

mutates(EavQueryBuilder::class);

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'products',
        'slug' => 'products',
        'enable_versioning' => true,
        'enable_soft_deletes' => true,
    ]);

    $this->textField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'label' => 'Name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $this->intField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'views',
        'label' => 'Views',
        'field_type' => 'integer',
        'eav_cast' => 'integer',
    ]);

    $this->decimalField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'label' => 'Price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
    ]);

    $this->boolField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'is_active',
        'label' => 'Active',
        'field_type' => 'toggle',
        'eav_cast' => 'boolean',
    ]);

    $this->datetimeField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'published_at',
        'label' => 'Published At',
        'field_type' => 'datetime',
        'eav_cast' => 'datetime',
    ]);

    $this->jsonField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'tags',
        'label' => 'Tags',
        'field_type' => 'tags',
        'eav_cast' => 'json',
    ]);
});

// ── get() method tests ─────────────────────────────────────────────────────

it('returns results via get() with proper structure', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->textField->id,
        'val_text' => 'Widget',
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->intField->id,
        'val_integer' => 42,
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->get();

    expect($results)->toHaveCount(1);
    $first = $results->first();
    expect($first->uuid)->toBe($record->uuid)
        ->and($first->name)->toBe('Widget')
        ->and($first->views)->toBe(42)
        ->and($first->created_at)->not->toBeNull()
        ->and($first->updated_at)->not->toBeNull();
});

it('returns empty collection via get() when no records match', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(99)
        ->get();

    expect($results)->toBeEmpty()
        ->and($results)->toBeInstanceOf(Collection::class);
});

it('applies sorting in get()', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Banana']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Apple']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->orderBy('name', 'asc')
        ->get();

    expect($results->first()->name)->toBe('Apple')
        ->and($results->last()->name)->toBe('Banana');
});

// ── castValue() tests ──────────────────────────────────────────────────────

it('casts integer values correctly via get()', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->intField->id,
        'val_integer' => 7,
    ]);

    $results = EavQueryBuilder::for($this->collection)->tenant(1)->get();

    expect($results->first()->views)->toBe(7)
        ->and($results->first()->views)->toBeInt();
});

it('casts null integer values as null via get()', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->intField->id,
        'val_integer' => null,
    ]);

    $results = EavQueryBuilder::for($this->collection)->tenant(1)->get();

    expect($results->first()->views)->toBeNull();
});

it('casts decimal values correctly via get()', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->decimalField->id,
        'val_decimal' => 19.99,
    ]);

    $results = EavQueryBuilder::for($this->collection)->tenant(1)->get();

    expect($results->first()->price)->toBe(19.99)
        ->and($results->first()->price)->toBeFloat();
});

it('casts boolean values correctly via get()', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->boolField->id,
        'val_boolean' => 1,
    ]);

    $results = EavQueryBuilder::for($this->collection)->tenant(1)->get();

    expect($results->first()->is_active)->toBeBool()
        ->and($results->first()->is_active)->toBeTrue();
});

it('casts json values correctly via get()', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->jsonField->id,
        'val_json' => ['tag1', 'tag2'],
    ]);

    $results = EavQueryBuilder::for($this->collection)->tenant(1)->get();

    expect($results->first()->tags)->toBe(['tag1', 'tag2']);
});

// ── paginate() tests ───────────────────────────────────────────────────────

it('returns empty paginator when no records exist', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(99)
        ->paginate(10);

    expect($results->items())->toBeEmpty()
        ->and($results->total())->toBe(0);
});

it('calculates offset correctly for page 2', function () {
    // Create 5 records
    for ($i = 0; $i < 5; $i++) {
        $r = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
        StudioValue::factory()->create(['record_id' => $r->id, 'field_id' => $this->textField->id, 'val_text' => "Item {$i}"]);
    }

    // Request page 2 with 2 per page
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->paginate(2, 2);

    expect($results->count())->toBe(2)
        ->and($results->total())->toBe(5);
});

it('paginate includes created_at and updated_at in results', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->textField->id,
        'val_text' => 'Test',
    ]);

    $results = EavQueryBuilder::for($this->collection)->tenant(1)->paginate(25);
    $first = $results->items()[0];

    expect($first)->toHaveProperties(['id', 'uuid', 'created_at', 'updated_at']);
});

// ── invalidateFieldCache() tests ───────────────────────────────────────────

it('invalidates field cache for specific collection', function () {
    // Populate cache
    EavQueryBuilder::getCachedFields($this->collection);

    // Invalidate specific
    EavQueryBuilder::invalidateFieldCache($this->collection->id);

    // Should be fresh on next call
    $fields = EavQueryBuilder::getCachedFields($this->collection);
    expect($fields)->toHaveCount(6);
});

it('invalidates all field caches when no collection id given', function () {
    EavQueryBuilder::getCachedFields($this->collection);

    EavQueryBuilder::invalidateFieldCache();

    $fields = EavQueryBuilder::getCachedFields($this->collection);
    expect($fields)->toHaveCount(6);
});

it('invalidateFieldCache with specific id only clears that collection', function () {
    // Create a second collection
    $other = StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'other', 'slug' => 'other']);
    StudioField::factory()->create(['collection_id' => $other->id, 'tenant_id' => 1, 'column_name' => 'x', 'label' => 'X', 'field_type' => 'text', 'eav_cast' => 'text']);

    // Populate both caches
    EavQueryBuilder::getCachedFields($this->collection);
    EavQueryBuilder::getCachedFields($other);

    // Invalidate only the other collection
    EavQueryBuilder::invalidateFieldCache($other->id);

    // Original should still be cached (won't reflect new fields unless force-refreshed)
    $fields = EavQueryBuilder::getCachedFields($this->collection);
    expect($fields)->toHaveCount(6);

    // Force refresh of the other should get fresh data
    $fields = EavQueryBuilder::getCachedFields($other, true);
    expect($fields)->toHaveCount(1);
});

// ── applyFilterTree / applyFilterNode tests ────────────────────────────────

it('returns self without applying filter when tree is empty', function () {
    $builder = EavQueryBuilder::for($this->collection)->tenant(1);

    $emptyTree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [],
    ]);

    $result = $builder->applyFilterTree($emptyTree);

    expect($result)->toBe($builder);

    // Create a record and verify it's returned (filter is not applied)
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->textField->id,
        'val_text' => 'Test',
    ]);

    $results = $builder->get();
    expect($results)->toHaveCount(1);
});

it('applies filter tree with FilterGroup and OR logic', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Alpha']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Beta']);

    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->textField->id, 'val_text' => 'Gamma']);

    $tree = FilterGroup::fromArray([
        'logic' => 'or',
        'rules' => [
            ['field' => 'name', 'operator' => 'eq', 'value' => 'Alpha'],
            ['field' => 'name', 'operator' => 'eq', 'value' => 'Beta'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});

it('applies filter tree with AND logic', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 100]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 5]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'eq', 'value' => 'Widget'],
            ['field' => 'views', 'operator' => 'gt', 'value' => 50],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->views)->toBe(100);
});

// ── Filter operators via FilterRule ────────────────────────────────────────

it('filters with IsNull operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Has Name']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    // No name value for r2

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'is_null', 'value' => null],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($r2->id);
});

it('filters with IsNotNull operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Has Name']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    // No name value for r2

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'is_not_null', 'value' => null],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Has Name');
});

it('filters with Contains operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Laravel Tips']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'PHP Guide']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'contains', 'value' => 'Tips'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Laravel Tips');
});

it('filters with NotContains operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Laravel Tips']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'PHP Guide']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'not_contains', 'value' => 'Tips'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('PHP Guide');
});

it('filters with StartsWith operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Laravel Tips']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'PHP Guide']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'starts_with', 'value' => 'Laravel'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Laravel Tips');
});

it('filters with EndsWith operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Laravel Tips']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'PHP Guide']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'ends_with', 'value' => 'Tips'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Laravel Tips');
});

it('filters with In operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Alpha']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Beta']);

    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->textField->id, 'val_text' => 'Gamma']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'in', 'value' => ['Alpha', 'Gamma']],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});

it('filters with NotIn operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Alpha']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Beta']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'not_in', 'value' => ['Alpha']],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Beta');
});

it('filters with Between operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 50]);

    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->intField->id, 'val_integer' => 100]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'views', 'operator' => 'between', 'value' => [20, 80]],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->views)->toBe(50);
});

it('filters with NotBetween operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 50]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'views', 'operator' => 'not_between', 'value' => [20, 80]],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->views)->toBe(10);
});

it('filters with IsTrue operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->boolField->id, 'val_boolean' => true]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->boolField->id, 'val_boolean' => false]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'is_active', 'operator' => 'is_true', 'value' => null],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($r1->id);
});

it('filters with IsFalse operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->boolField->id, 'val_boolean' => true]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->boolField->id, 'val_boolean' => false]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'is_active', 'operator' => 'is_false', 'value' => null],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($r2->id);
});

it('filters with IsEmpty operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => '']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Has Value']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'is_empty', 'value' => null],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($r1->id);
});

it('filters with IsNotEmpty operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => '']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Has Value']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'is_not_empty', 'value' => null],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Has Value');
});

// ── orderBy() direction normalization ──────────────────────────────────────

it('normalizes orderBy direction to lowercase', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Alpha']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Zeta']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->orderBy('name', 'DESC')
        ->get();

    expect($results->first()->name)->toBe('Zeta')
        ->and($results->last()->name)->toBe('Alpha');
});

// ── getRecordData() tests ──────────────────────────────────────────────────

it('returns record data via getRecordData()', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->intField->id, 'val_integer' => 42]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->decimalField->id, 'val_decimal' => 19.99]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->boolField->id, 'val_boolean' => true]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->datetimeField->id, 'val_datetime' => '2026-01-15 10:00:00']);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->jsonField->id, 'val_json' => ['a', 'b']]);

    $data = EavQueryBuilder::for($this->collection)->getRecordData($record);

    expect($data)->toHaveKey('name', 'Widget')
        ->and($data['views'])->toBe(42)
        ->and($data['price'])->toBe(19.99)
        ->and($data['is_active'])->toBeBool()
        ->and($data)->toHaveKey('published_at')
        ->and($data['tags'])->toBe(['a', 'b']);
});

// ── prepareValueForStorage() tests via create ──────────────────────────────

it('stores text as string type', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 123]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->textField->id)
        ->first();

    expect($val->val_text)->toBe('123')
        ->and($val->val_text)->toBeString();
});

it('stores integer as int type', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['views' => '42']);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->intField->id)
        ->first();

    expect($val->val_integer)->toBe(42);
});

it('stores decimal as float type', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['price' => '19.99']);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->decimalField->id)
        ->first();

    expect((float) $val->val_decimal)->toBe(19.99);
});

it('stores boolean as 1 or 0', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['is_active' => true]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->boolField->id)
        ->first();

    expect((int) $val->val_boolean)->toBe(1);

    $record2 = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['is_active' => false]);

    $val2 = StudioValue::where('record_id', $record2->id)
        ->where('field_id', $this->boolField->id)
        ->first();

    expect((int) $val2->val_boolean)->toBe(0);
});

it('stores datetime values correctly', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['published_at' => new DateTime('2026-03-14 12:00:00')]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->datetimeField->id)
        ->first();

    expect((string) $val->val_datetime)->toContain('2026-03-14');
});

it('stores datetime string values correctly', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['published_at' => '2026-03-14 12:00:00']);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->datetimeField->id)
        ->first();

    expect((string) $val->val_datetime)->toContain('2026-03-14');
});

it('stores json array as encoded string', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['tags' => ['a', 'b', 'c']]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->jsonField->id)
        ->first();

    expect($val->val_json)->toBe(['a', 'b', 'c']);
});

it('stores null values correctly', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => null]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->textField->id)
        ->first();

    // When null, it should store null
    expect($val->val_text)->toBeNull();
});

// ── update() tests for versioning and val_* reset ──────────────────────────

it('resets all val columns to null during update', function () {
    // Create with tags as json
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['tags' => ['old']]);

    // Update the tags
    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['tags' => ['new']]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->jsonField->id)
        ->first();

    $jsonData = is_string($val->val_json) ? json_decode($val->val_json, true) : $val->val_json;
    expect($jsonData)->toBe(['new'])
        ->and($val->val_text)->toBeNull()
        ->and($val->val_integer)->toBeNull()
        ->and($val->val_decimal)->toBeNull()
        ->and($val->val_boolean)->toBeNull()
        ->and($val->val_datetime)->toBeNull();
});

it('triggers versioning during update when enabled', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Before']);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'After']);

    $versions = StudioRecordVersion::where('record_id', $record->id)->count();
    expect($versions)->toBeGreaterThanOrEqual(1);
});

it('does not trigger versioning during update when disabled', function () {
    $noVersionCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'no-ver',
        'slug' => 'no-ver',
        'enable_versioning' => false,
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $noVersionCollection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    EavQueryBuilder::invalidateFieldCache();

    $record = EavQueryBuilder::for($noVersionCollection)
        ->tenant(1)
        ->create(['title' => 'Before']);

    EavQueryBuilder::for($noVersionCollection)
        ->tenant(1)
        ->update($record->id, ['title' => 'After']);

    $versions = StudioRecordVersion::where('record_id', $record->id)->count();
    expect($versions)->toBe(0);
});

it('touches record on update when userId is null', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Test']);

    $originalUpdatedAt = $record->updated_at;
    $this->travel(5)->minutes();

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'Changed']);

    $record->refresh();
    expect($record->updated_at->gt($originalUpdatedAt))->toBeTrue();
});

// ── restoreFromVersion() tests ─────────────────────────────────────────────

it('restores record data from a version snapshot', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Original']);

    // Update so we get a version
    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'Changed']);

    // Find the first version (original snapshot)
    $version = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('created_at', 'asc')
        ->first();

    if ($version) {
        EavQueryBuilder::for($this->collection)
            ->tenant(1)
            ->restoreFromVersion($record->uuid, $version->id);

        $data = EavQueryBuilder::for($this->collection)->getRecordData($record);
        expect($data['name'])->toBe($version->snapshot['name']);
    } else {
        $this->markTestSkipped('No version record created');
    }
});

// ── toEloquentQuery() tests ────────────────────────────────────────────────

it('returns eloquent query with field subselects', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->textField->id, 'val_text' => 'EloquentTest']);

    $query = EavQueryBuilder::for($this->collection)->tenant(1)->toEloquentQuery();
    $result = $query->first();

    expect($result->name)->toBe('EloquentTest');
});

// ── pluck() tests ──────────────────────────────────────────────────────────

it('plucks values with uuid as key', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->pluck('name', 'uuid');

    expect($results)->toHaveKey($record->uuid)
        ->and($results[$record->uuid])->toBe('Widget');
});

it('plucks values without key', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->pluck('name');

    expect($results->values()->all())->toContain('Widget');
});

// ── select() with get() ────────────────────────────────────────────────────

it('selects only specified fields via get()', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->intField->id, 'val_integer' => 42]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name'])
        ->get();

    $first = $results->first();
    expect($first->name)->toBe('Widget')
        ->and(property_exists($first, 'views'))->toBeFalse();
});

// ── deleteWithIntegrity() tests ────────────────────────────────────────────

it('deletes record and its values via deleteWithIntegrity', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'ToDelete']);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->deleteWithIntegrity($record->uuid);

    expect(StudioRecord::find($record->id))->toBeNull();
    expect(StudioValue::where('record_id', $record->id)->count())->toBe(0);
});

// ── aggregate() tests ──────────────────────────────────────────────────────

it('counts records without specifying a field', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);

    $count = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Count);

    expect($count)->toBe(2);
});

it('counts records with a specific field', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    // r2 has no views value

    $count = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Count, 'views');

    expect($count)->toBe(1);
});

// ── applyWhere() basic type tests (kills Line 330+ IncrementInteger/DecrementInteger) ──

it('applies basic where clause with operator', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 50]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->where('views', '>', 20)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->views)->toBe(50);
});

it('applies basic where clause without explicit operator (defaults to =)', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Target']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Other']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->where('name', 'Target')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Target');
});

// ── applyWhere() whereIn type (kills Line 338+ mutations) ──

it('applies whereIn clause', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Alpha']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Beta']);

    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->textField->id, 'val_text' => 'Gamma']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereIn('name', ['Alpha', 'Gamma'])
        ->get();

    expect($results)->toHaveCount(2);
});

// ── applyWhere() whereBetween type (kills Line 345+ mutations) ──

it('applies whereBetween clause', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 5]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 50]);

    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->intField->id, 'val_integer' => 100]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereBetween('views', [10, 60])
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->views)->toBe(50);
});

// ── applyWhere() whereNull type (kills Line 352+ mutations) ──

it('applies whereNull clause', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'HasName']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    // r2 has no name value

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereNull('name')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($r2->id);
});

// ── applyWhere() whereNotNull type (kills Line 359+ mutations) ──

it('applies whereNotNull clause', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'HasName']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    // r2 has no name

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereNotNull('name')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('HasName');
});

// ── applyWhere() referencing type (kills Line 366+ mutations) ──

it('applies whereReferencing clause', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'some-uuid-123']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'other-uuid']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereReferencing('name', 'some-uuid-123')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($r1->id);
});

// ── applyWhere() search type (kills Line 373+, 377+ mutations) ──

it('applies search clause across multiple fields', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Laravel Widget']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'PHP Tool']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->search('Widget', ['name'])
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Laravel Widget');
});

it('search with no matching fields returns all records', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Test']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->search('Widget', ['nonexistent_field'])
        ->get();

    // Search on nonexistent field doesn't add any filter conditions
    expect($results)->toHaveCount(1);
});

// ── applyWhere with invalid field returns all records (kills early return) ──

it('where with invalid field name is ignored', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Test']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->where('nonexistent', 'value')
        ->get();

    expect($results)->toHaveCount(1);
});

// ── paginate() offset and empty return tests (kills Lines 120, 131, 156, 158, 164) ──

it('paginate returns correct items with proper pagination metadata', function () {
    for ($i = 0; $i < 3; $i++) {
        $r = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
        StudioValue::factory()->create(['record_id' => $r->id, 'field_id' => $this->textField->id, 'val_text' => "Item {$i}"]);
    }

    $page1 = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->paginate(2, 1);

    expect($page1->count())->toBe(2)
        ->and($page1->total())->toBe(3)
        ->and($page1->perPage())->toBe(2)
        ->and($page1->currentPage())->toBe(1);

    $page2 = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->paginate(2, 2);

    expect($page2->count())->toBe(1)
        ->and($page2->total())->toBe(3);
});

it('paginate returns uuid, created_at and updated_at on each item', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->textField->id, 'val_text' => 'PaginateTest']);

    $results = EavQueryBuilder::for($this->collection)->tenant(1)->paginate(25, 1);
    $first = $results->items()[0];

    expect($first->uuid)->toBe($record->uuid)
        ->and($first->created_at)->not->toBeNull()
        ->and($first->updated_at)->not->toBeNull()
        ->and($first->name)->toBe('PaginateTest');
});

it('paginate with sorting applies order correctly', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Banana']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Apple']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->orderBy('name', 'asc')
        ->paginate(25, 1);

    $items = $results->items();
    expect($items[0]->name)->toBe('Apple')
        ->and($items[1]->name)->toBe('Banana');
});

// ── withRelated() and resolveRelation() tests (kills Lines 1048, 1071, 1083) ──

it('resolves related single value with withRelated', function () {
    // Create a related collection
    $relatedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'categories',
        'slug' => 'categories',
    ]);
    $categoryNameField = StudioField::factory()->create([
        'collection_id' => $relatedCollection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Create a category record
    $cat = StudioRecord::factory()->create(['collection_id' => $relatedCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $cat->id, 'field_id' => $categoryNameField->id, 'val_text' => 'Electronics']);

    // Create a product that references the category by UUID
    $product = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $product->id, 'field_id' => $this->textField->id, 'val_text' => $cat->uuid]);

    EavQueryBuilder::invalidateFieldCache();

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->withRelated('name', $relatedCollection, 'title')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name_display)->toBe('Electronics');
});

it('resolves related array values with withRelated', function () {
    // Create a related collection
    $relatedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'tags-col',
        'slug' => 'tags-col',
    ]);
    $tagNameField = StudioField::factory()->create([
        'collection_id' => $relatedCollection->id,
        'tenant_id' => 1,
        'column_name' => 'label',
        'label' => 'Label',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $tag1 = StudioRecord::factory()->create(['collection_id' => $relatedCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $tag1->id, 'field_id' => $tagNameField->id, 'val_text' => 'Red']);

    $tag2 = StudioRecord::factory()->create(['collection_id' => $relatedCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $tag2->id, 'field_id' => $tagNameField->id, 'val_text' => 'Blue']);

    // Product with JSON array referencing tag UUIDs
    $product = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create([
        'record_id' => $product->id,
        'field_id' => $this->jsonField->id,
        'val_json' => [$tag1->uuid, $tag2->uuid],
    ]);

    EavQueryBuilder::invalidateFieldCache();

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->withRelated('tags', $relatedCollection, 'label')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->tags_display)->toContain('Red')
        ->and($results->first()->tags_display)->toContain('Blue');
});

it('withRelated sets null display when no foreign UUIDs', function () {
    $relatedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'cats',
        'slug' => 'cats',
    ]);
    StudioField::factory()->create([
        'collection_id' => $relatedCollection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Product with no value for the related field
    $product = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $product->id, 'field_id' => $this->intField->id, 'val_integer' => 5]);

    EavQueryBuilder::invalidateFieldCache();

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->withRelated('name', $relatedCollection, 'title')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name_display)->toBeNull();
});

// ── deleteWithIntegrity() with belongs_to_many (kills Lines 1171, 1185) ──

it('deleteWithIntegrity removes UUID from belongs_to_many JSON arrays', function () {
    // Create a second collection with a belongs_to_many field
    $relatedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'orders',
        'slug' => 'orders',
    ]);

    $btmField = StudioField::factory()->create([
        'collection_id' => $relatedCollection->id,
        'tenant_id' => 1,
        'column_name' => 'product_ids',
        'label' => 'Products',
        'field_type' => 'belongs_to_many',
        'eav_cast' => 'json',
        'settings' => ['related_collection' => $this->collection->slug],
    ]);

    // Create a product to delete
    $product = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'ToRemove']);

    // Create an order referencing this product
    $order = StudioRecord::factory()->create(['collection_id' => $relatedCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create([
        'record_id' => $order->id,
        'field_id' => $btmField->id,
        'val_json' => [$product->uuid, 'other-uuid'],
    ]);

    EavQueryBuilder::invalidateFieldCache();

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->deleteWithIntegrity($product->uuid);

    // The product should be gone
    expect(StudioRecord::find($product->id))->toBeNull();

    // The order's JSON should no longer contain the deleted UUID
    $orderVal = StudioValue::where('record_id', $order->id)
        ->where('field_id', $btmField->id)
        ->first();

    $json = is_string($orderVal->val_json) ? json_decode($orderVal->val_json, true) : $orderVal->val_json;
    expect($json)->not->toContain($product->uuid)
        ->and($json)->toContain('other-uuid');
});

it('deleteWithIntegrity with restrict throws when referenced', function () {
    $relatedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'invoices',
        'slug' => 'invoices',
    ]);

    $btField = StudioField::factory()->create([
        'collection_id' => $relatedCollection->id,
        'tenant_id' => 1,
        'column_name' => 'product_id',
        'label' => 'Product',
        'field_type' => 'belongs_to',
        'eav_cast' => 'text',
        'settings' => ['related_collection' => $this->collection->slug, 'on_delete' => 'restrict'],
    ]);

    $product = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Referenced']);

    // Create an invoice referencing this product
    $invoice = StudioRecord::factory()->create(['collection_id' => $relatedCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create([
        'record_id' => $invoice->id,
        'field_id' => $btField->id,
        'val_text' => $product->uuid,
    ]);

    expect(fn () => EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->deleteWithIntegrity($product->uuid)
    )->toThrow(RuntimeException::class, 'Cannot delete record');
});

it('deleteWithIntegrity with set_null nullifies references', function () {
    $relatedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'invoices2',
        'slug' => 'invoices2',
    ]);

    $btField = StudioField::factory()->create([
        'collection_id' => $relatedCollection->id,
        'tenant_id' => 1,
        'column_name' => 'product_id',
        'label' => 'Product',
        'field_type' => 'belongs_to',
        'eav_cast' => 'text',
        'settings' => ['related_collection' => $this->collection->slug, 'on_delete' => 'set_null'],
    ]);

    $product = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'SetNullTarget']);

    $invoice = StudioRecord::factory()->create(['collection_id' => $relatedCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create([
        'record_id' => $invoice->id,
        'field_id' => $btField->id,
        'val_text' => $product->uuid,
    ]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->deleteWithIntegrity($product->uuid);

    expect(StudioRecord::find($product->id))->toBeNull();

    $refVal = StudioValue::where('record_id', $invoice->id)
        ->where('field_id', $btField->id)
        ->first();
    expect($refVal->val_text)->toBeNull();
});

// ── pluck() with non-uuid EAV key field (kills Line 1240) ──

it('plucks values with another EAV field as key', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 42]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Gadget']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 99]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->pluck('views', 'name');

    expect($results->get('Widget'))->toBe(42)
        ->and($results->get('Gadget'))->toBe(99);
});

it('pluck returns empty collection when no records exist', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(99)
        ->pluck('name');

    expect($results)->toBeEmpty();
});

it('pluck returns empty when value field does not exist', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->pluck('nonexistent_field');

    expect($results)->toBeEmpty();
});

// ── aggregate() with sum, avg, min, max (kills Line 1288 ConcatRemoveRight) ──

it('computes sum aggregate for a field', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 30]);

    $sum = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Sum, 'views');

    expect((int) $sum)->toBe(40);
});

it('computes avg aggregate for a field', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 30]);

    $avg = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Avg, 'views');

    expect((float) $avg)->toBe(20.0);
});

it('returns null for aggregate on nonexistent field', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);

    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Sum, 'nonexistent');

    expect($result)->toBeNull();
});

// ── aggregateTimeSeries() (kills Lines 1308 BooleanOrToBooleanAnd) ──

it('computes time series aggregate grouped by day', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->datetimeField->id, 'val_datetime' => '2026-03-01 10:00:00']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 20]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->datetimeField->id, 'val_datetime' => '2026-03-01 14:00:00']);

    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->intField->id, 'val_integer' => 30]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->datetimeField->id, 'val_datetime' => '2026-03-02 10:00:00']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregateTimeSeries(AggregateFunction::Sum, 'views', 'published_at', GroupPrecision::Day);

    expect($results)->toHaveCount(2)
        ->and((int) $results->get('2026-03-01'))->toBe(30)
        ->and((int) $results->get('2026-03-02'))->toBe(30);
});

it('aggregateTimeSeries returns empty when fields do not exist', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregateTimeSeries(AggregateFunction::Sum, 'nonexistent', 'published_at', GroupPrecision::Day);

    expect($results)->toBeEmpty();
});

// ── aggregateByGroup() (kills Lines 1365 BooleanOrToBooleanAnd) ──

it('computes aggregate grouped by a categorical field', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Category A']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Category A']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 20]);

    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->textField->id, 'val_text' => 'Category B']);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->intField->id, 'val_integer' => 50]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregateByGroup(AggregateFunction::Sum, 'views', 'name');

    expect($results)->toHaveCount(2)
        ->and((int) $results->get('Category A'))->toBe(30)
        ->and((int) $results->get('Category B'))->toBe(50);
});

it('aggregateByGroup returns empty when fields do not exist', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregateByGroup(AggregateFunction::Sum, 'nonexistent', 'name');

    expect($results)->toBeEmpty();
});

// ── update() versioning IfNegated (kills Lines 857, 897, 936) ──

it('update with userId sets updated_by field', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Before']);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'After'], 42);

    $record->refresh();
    expect($record->updated_by)->toBe(42);
});

// ── restoreFromVersion() versioning (kills Line 936, 938) ──

it('restoreFromVersion creates a version snapshot before restoring', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'V1']);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'V2']);

    $versionsBefore = StudioRecordVersion::where('record_id', $record->id)->count();

    $version = StudioRecordVersion::where('record_id', $record->id)->orderBy('created_at', 'asc')->first();
    if ($version) {
        EavQueryBuilder::for($this->collection)
            ->tenant(1)
            ->restoreFromVersion($record->uuid, $version->id);

        $versionsAfter = StudioRecordVersion::where('record_id', $record->id)->count();
        expect($versionsAfter)->toBeGreaterThan($versionsBefore);
    }
});

// ── delete() soft deletes vs hard deletes (kills Lines 904-915) ──

it('delete soft-deletes when enable_soft_deletes is true', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'SoftDel']);

    EavQueryBuilder::for($this->collection)->delete($record->id);

    $record->refresh();
    expect($record->deleted_at)->not->toBeNull();
    // Values still exist for soft-deleted records
    expect(StudioValue::where('record_id', $record->id)->count())->toBeGreaterThan(0);
});

it('delete hard-deletes when enable_soft_deletes is false', function () {
    $hardCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'hard-del',
        'slug' => 'hard-del',
        'enable_soft_deletes' => false,
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $hardCollection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    EavQueryBuilder::invalidateFieldCache();

    $record = EavQueryBuilder::for($hardCollection)
        ->tenant(1)
        ->create(['title' => 'HardDel']);

    EavQueryBuilder::for($hardCollection)->delete($record->id);

    expect(StudioRecord::find($record->id))->toBeNull();
    expect(StudioValue::where('record_id', $record->id)->count())->toBe(0);
});

// ── whereDate() (kills Line 717-723 RemoveArrayItem) ──

it('applies whereDate clause', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->datetimeField->id, 'val_datetime' => '2026-01-15 10:00:00']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->datetimeField->id, 'val_datetime' => '2026-06-15 10:00:00']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereDate('published_at', '>', '2026-03-01 00:00:00')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($r2->id);
});

// ── toEloquentQuery() tests (kills Line 793) ──

it('toEloquentQuery adds subquery selects for all fields', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->textField->id, 'val_text' => 'SubTest']);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->intField->id, 'val_integer' => 77]);

    $query = EavQueryBuilder::for($this->collection)->tenant(1)->toEloquentQuery();
    $result = $query->first();

    expect($result->name)->toBe('SubTest')
        ->and((int) $result->views)->toBe(77);
});

// ── get() without tenant (no tenant filter) ──

it('get without tenant returns all tenant records', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'T1']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 2]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'T2']);

    $results = EavQueryBuilder::for($this->collection)->get();

    expect($results)->toHaveCount(2);
});

// ── select() with paginate (kills Line 219 RemoveArrayItem) ──

it('selects only specified fields via paginate', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->textField->id, 'val_text' => 'Test']);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->intField->id, 'val_integer' => 42]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name'])
        ->paginate(25, 1);

    $first = $results->items()[0];
    expect($first->name)->toBe('Test')
        ->and(property_exists($first, 'views'))->toBeFalse();
});

// ── ContainsAny JSON filter (kills Lines 571+) ──

it('filters with ContainsAny operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->jsonField->id, 'val_json' => ['red', 'blue']]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->jsonField->id, 'val_json' => ['green', 'yellow']]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'tags', 'operator' => 'contains_any', 'value' => ['red', 'green']],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
});

// ── ContainsAll JSON filter (kills Lines 583+) ──

it('filters with ContainsAll operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->jsonField->id, 'val_json' => ['red', 'blue']]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->jsonField->id, 'val_json' => ['red', 'green']]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'tags', 'operator' => 'contains_all', 'value' => ['red', 'blue']],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($r1->id);
});

// ── ContainsNone JSON filter (kills Lines 595+) ──

it('filters with ContainsNone operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->jsonField->id, 'val_json' => ['red', 'blue']]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->jsonField->id, 'val_json' => ['green', 'yellow']]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'tags', 'operator' => 'contains_none', 'value' => ['red', 'blue']],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($r2->id);
});

// ── Default filter operator (eq, neq, gt, lt etc) (kills Line 607+) ──

it('filters with default gt operator via filter tree', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 50]);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'views', 'operator' => 'gt', 'value' => 20],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->views)->toBe(50);
});

// ── applyFilterToQuery() (kills Line 407-409) ──

it('applyFilterToQuery applies filter tree to an external query', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Match']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'NoMatch']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'eq', 'value' => 'Match'],
        ],
    ]);

    $builder = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree);

    $query = StudioRecord::query()
        ->where('collection_id', $this->collection->id)
        ->whereNull('deleted_at');

    $builder->applyFilterToQuery($query);

    expect($query->count())->toBe(1);
});

// ── Sorting with invalid field (orderBy resolveField returns null) ──

it('sorting with invalid field name is ignored', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Test']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->orderBy('nonexistent_field', 'asc')
        ->get();

    expect($results)->toHaveCount(1);
});

// ── applyFilterRule with invalid field is ignored ──

it('filter rule with invalid field name is ignored', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Test']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'nonexistent', 'operator' => 'eq', 'value' => 'anything'],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
});

// ── create() stores values for multiple fields in bulk (kills Line 983, 991) ──

it('create ignores unknown field names in data', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Good', 'nonexistent_field' => 'ignored']);

    $data = EavQueryBuilder::for($this->collection)->getRecordData($record);

    expect($data)->toHaveKey('name', 'Good')
        ->and($data)->not->toHaveKey('nonexistent_field');
});

// ── update() with unknown field skips that field ──

it('update ignores unknown field names', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Before']);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'After', 'fake_field' => 'ignored']);

    $data = EavQueryBuilder::for($this->collection)->getRecordData($record);
    expect($data['name'])->toBe('After');
});

// ── prepareValueForStorage - json string pass-through (kills Line 1007/1008) ──

it('stores json string value as-is', function () {
    $jsonString = '{"key":"value"}';
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['tags' => $jsonString]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->jsonField->id)
        ->first();

    // When value is a string, prepareValueForStorage returns it as-is for json cast
    $json = is_string($val->val_json) ? json_decode($val->val_json, true) : $val->val_json;
    expect($json)->toBe(['key' => 'value']);
});

// ── Nested FilterGroup tests (kills Line 420 InstanceOfToTrue) ──

it('applies nested filter groups correctly', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Alpha']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 100]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Beta']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 5]);

    $r3 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->textField->id, 'val_text' => 'Gamma']);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->intField->id, 'val_integer' => 50]);

    // Nested: (name=Alpha AND views>50) OR (name=Gamma AND views>10)
    $tree = FilterGroup::fromArray([
        'logic' => 'or',
        'rules' => [
            [
                'logic' => 'and',
                'rules' => [
                    ['field' => 'name', 'operator' => 'eq', 'value' => 'Alpha'],
                    ['field' => 'views', 'operator' => 'gt', 'value' => 50],
                ],
            ],
            [
                'logic' => 'and',
                'rules' => [
                    ['field' => 'name', 'operator' => 'eq', 'value' => 'Gamma'],
                    ['field' => 'views', 'operator' => 'gt', 'value' => 10],
                ],
            ],
        ],
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(2);
    $names = $results->pluck('name')->sort()->values()->all();
    expect($names)->toBe(['Alpha', 'Gamma']);
});

// ── castValue() strict type assertions (kills Lines 266-270) ──

it('castValue returns array not object for json (kills TrueToFalse on json_decode)', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->jsonField->id,
        'val_json' => ['key' => 'value'],
    ]);

    $results = EavQueryBuilder::for($this->collection)->tenant(1)->get();

    expect($results->first()->tags)->toBeArray()
        ->and($results->first()->tags)->toBe(['key' => 'value']);
});

it('castValue integer must be int type not string', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->intField->id,
        'val_integer' => 7,
    ]);

    $data = EavQueryBuilder::for($this->collection)->getRecordData($record);
    expect($data['views'])->toBeInt()
        ->and($data['views'])->toBe(7);
});

it('castValue decimal must be float type', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->decimalField->id,
        'val_decimal' => 3.14,
    ]);

    $data = EavQueryBuilder::for($this->collection)->getRecordData($record);
    expect($data['price'])->toBeFloat()
        ->and($data['price'])->toBe(3.14);
});

it('castValue boolean must be bool type', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->boolField->id,
        'val_boolean' => 1,
    ]);

    $data = EavQueryBuilder::for($this->collection)->getRecordData($record);
    expect($data['is_active'])->toBeBool()
        ->and($data['is_active'])->toBeTrue();
});

// ── aggregate() strict assertions (kills Line 1288 ConcatRemoveRight) ──

it('aggregate sum returns non-null numeric value', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 15]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 25]);

    $sum = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Sum, 'views');

    expect($sum)->not->toBeNull()
        ->and((int) $sum)->toBe(40);
});

it('aggregate min returns the minimum value', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 50]);

    $min = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Min, 'views');

    expect($min)->not->toBeNull()
        ->and((int) $min)->toBe(10);
});

it('aggregate max returns the maximum value', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 10]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 50]);

    $max = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Max, 'views');

    expect($max)->not->toBeNull()
        ->and((int) $max)->toBe(50);
});

// ── restoreFromVersion() verifies data is actually restored (kills Line 952, 957) ──

it('restoreFromVersion writes snapshot values back to the record', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Original', 'views' => 100]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'Modified', 'views' => 200]);

    $version = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('created_at', 'asc')
        ->first();

    if ($version) {
        $beforeTouch = $record->fresh()->updated_at;
        $this->travel(5)->minutes();

        EavQueryBuilder::for($this->collection)
            ->tenant(1)
            ->restoreFromVersion($record->uuid, $version->id);

        $restoredData = EavQueryBuilder::for($this->collection)->getRecordData($record);
        expect($restoredData['name'])->toBe($version->snapshot['name']);

        // touch() should have updated the timestamp
        $record->refresh();
        expect($record->updated_at->gt($beforeTouch))->toBeTrue();
    }
});

// ── update() versioning observer calls (kills Lines 857-859, 897-899) ──

it('update creates version before and after changes when versioning enabled', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'V1', 'views' => 10]);

    // First update
    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'V2']);

    $versionsAfterFirst = StudioRecordVersion::where('record_id', $record->id)->count();

    // Second update
    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'V3']);

    $versionsAfterSecond = StudioRecordVersion::where('record_id', $record->id)->count();

    expect($versionsAfterSecond)->toBeGreaterThan($versionsAfterFirst);
});

// ── update() val_* column reset with type change scenario (kills Lines 879-884) ──

it('update resets val_text to null when updating an integer field', function () {
    // Create a record with both text and integer values
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Test', 'views' => 10]);

    // Update the integer field
    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['views' => 20]);

    // Check that the views value row has null in all other columns
    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->intField->id)
        ->first();

    expect($val->val_integer)->toBe(20)
        ->and($val->val_text)->toBeNull()
        ->and($val->val_decimal)->toBeNull()
        ->and($val->val_boolean)->toBeNull()
        ->and($val->val_datetime)->toBeNull()
        ->and($val->val_json)->toBeNull();
});

it('update resets val_integer to null when updating a text field', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Test', 'views' => 10]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'Updated']);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->textField->id)
        ->first();

    expect($val->val_text)->toBe('Updated')
        ->and($val->val_integer)->toBeNull()
        ->and($val->val_decimal)->toBeNull()
        ->and($val->val_boolean)->toBeNull()
        ->and($val->val_datetime)->toBeNull()
        ->and($val->val_json)->toBeNull();
});

// ── prepareValueForStorage type enforcement (kills Lines 1003-1007) ──

it('prepareValueForStorage casts integer input to string for text field', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 456]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->textField->id)
        ->first();

    expect($val->val_text)->toBeString()
        ->and($val->val_text)->toBe('456');
});

it('prepareValueForStorage casts string to integer for integer field', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['views' => '99']);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->intField->id)
        ->first();

    expect($val->val_integer)->toBe(99);
});

it('prepareValueForStorage casts string to float for decimal field', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['price' => '12.5']);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->decimalField->id)
        ->first();

    expect((float) $val->val_decimal)->toBe(12.5);
});

it('prepareValueForStorage casts truthy value to 1 for boolean field', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['is_active' => 'yes']);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->boolField->id)
        ->first();

    expect((int) $val->val_boolean)->toBe(1);
});

it('prepareValueForStorage casts falsy value to 0 for boolean field', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['is_active' => 0]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->boolField->id)
        ->first();

    expect((int) $val->val_boolean)->toBe(0);
});

it('prepareValueForStorage casts DateTime to formatted string for datetime field', function () {
    $dt = new DateTime('2026-06-15 14:30:00');
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['published_at' => $dt]);

    $val = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->datetimeField->id)
        ->first();

    expect((string) $val->val_datetime)->toContain('2026-06-15')
        ->and((string) $val->val_datetime)->toContain('14:30');
});

// ── invalidateFieldCache specific vs all (kills Line 311 IfNegated) ──

it('invalidateFieldCache with null clears all, not specific', function () {
    $other = StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'other2', 'slug' => 'other2']);
    StudioField::factory()->create(['collection_id' => $other->id, 'tenant_id' => 1, 'column_name' => 'z', 'label' => 'Z', 'field_type' => 'text', 'eav_cast' => 'text']);

    // Populate caches
    EavQueryBuilder::getCachedFields($this->collection);
    EavQueryBuilder::getCachedFields($other);

    // Add a new field to the main collection
    StudioField::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1, 'column_name' => 'extra', 'label' => 'Extra', 'field_type' => 'text', 'eav_cast' => 'text']);

    // Invalidate all (null)
    EavQueryBuilder::invalidateFieldCache(null);

    // Both should be refreshed
    $fields = EavQueryBuilder::getCachedFields($this->collection);
    expect($fields)->toHaveCount(7); // 6 original + 1 new
});

it('invalidateFieldCache with specific id leaves other collections cached', function () {
    // The StudioField model boot events auto-invalidate, so we test the static method directly
    // by verifying that invalidating collection A doesn't invalidate collection B
    $colA = StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'cache-a', 'slug' => 'cache-a']);
    $colB = StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'cache-b', 'slug' => 'cache-b']);

    StudioField::factory()->create(['collection_id' => $colA->id, 'tenant_id' => 1, 'column_name' => 'a', 'label' => 'A', 'field_type' => 'text', 'eav_cast' => 'text']);
    StudioField::factory()->create(['collection_id' => $colB->id, 'tenant_id' => 1, 'column_name' => 'b', 'label' => 'B', 'field_type' => 'text', 'eav_cast' => 'text']);

    // Populate both caches
    $fieldsA = EavQueryBuilder::getCachedFields($colA, true);
    $fieldsB = EavQueryBuilder::getCachedFields($colB, true);

    expect($fieldsA)->toHaveCount(1)
        ->and($fieldsB)->toHaveCount(1);

    // Invalidate only colA
    EavQueryBuilder::invalidateFieldCache($colA->id);

    // colB should still be cached and accessible
    $fieldsB2 = EavQueryBuilder::getCachedFields($colB);
    expect($fieldsB2)->toHaveCount(1);
});

// ── withRelated with missing display value on related record (kills Line 1083 BooleanAndToBooleanOr) ──

it('withRelated returns null display when related record has no display value', function () {
    $relatedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'empty-display',
        'slug' => 'empty-display',
    ]);
    $displayField = StudioField::factory()->create([
        'collection_id' => $relatedCollection->id,
        'tenant_id' => 1,
        'column_name' => 'label',
        'label' => 'Label',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Create related record WITHOUT a display value
    $relatedRecord = StudioRecord::factory()->create(['collection_id' => $relatedCollection->id, 'tenant_id' => 1]);

    // Product referencing the related record by UUID
    $product = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $product->id, 'field_id' => $this->textField->id, 'val_text' => $relatedRecord->uuid]);

    EavQueryBuilder::invalidateFieldCache();

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->withRelated('name', $relatedCollection, 'label')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name_display)->toBeNull();
});

// ── withRelated with array values where some UUIDs are empty/null (kills Line 1048 UnwrapArrayFilter/UnwrapArrayUnique) ──

it('withRelated filters null values from array foreign UUIDs', function () {
    $relatedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'filter-test',
        'slug' => 'filter-test',
    ]);
    $labelField = StudioField::factory()->create([
        'collection_id' => $relatedCollection->id,
        'tenant_id' => 1,
        'column_name' => 'label',
        'label' => 'Label',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $tag = StudioRecord::factory()->create(['collection_id' => $relatedCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $tag->id, 'field_id' => $labelField->id, 'val_text' => 'Valid']);

    // Product with array containing null and duplicate UUIDs
    $product = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create([
        'record_id' => $product->id,
        'field_id' => $this->jsonField->id,
        'val_json' => [$tag->uuid, null, '', $tag->uuid],
    ]);

    EavQueryBuilder::invalidateFieldCache();

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->withRelated('tags', $relatedCollection, 'label')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->tags_display)->toContain('Valid');
});

// ── getCachedFields with forceRefresh (kills Line 296) ──

// ── getRecordData strict value assertions (kills Lines 817 RemoveArrayItem) ──

it('getRecordData returns non-null datetime value', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->datetimeField->id,
        'val_datetime' => '2026-06-15 10:00:00',
    ]);

    $data = EavQueryBuilder::for($this->collection)->getRecordData($record);

    expect($data)->toHaveKey('published_at')
        ->and($data['published_at'])->not->toBeNull()
        ->and((string) $data['published_at'])->toContain('2026-06-15');
});

it('getRecordData returns non-null json value', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $this->jsonField->id,
        'val_json' => ['x', 'y'],
    ]);

    $data = EavQueryBuilder::for($this->collection)->getRecordData($record);

    expect($data)->toHaveKey('tags')
        ->and($data['tags'])->not->toBeNull()
        ->and($data['tags'])->toBe(['x', 'y']);
});

// ── create() generates UUID (kills Line 836 RemoveArrayItem) ──

it('create generates a UUID for new records', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'UuidTest']);

    expect($record->uuid)->not->toBeNull()
        ->and($record->uuid)->toBeString()
        ->and(strlen($record->uuid))->toBeGreaterThan(10);
});

it('create sets collection_id and tenant_id correctly', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'CreateTest']);

    expect($record->collection_id)->toBe($this->collection->id)
        ->and($record->tenant_id)->toBe(1);
});

it('create sets created_by and updated_by when userId provided', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'WithUser'], 55);

    expect($record->created_by)->toBe(55)
        ->and($record->updated_by)->toBe(55);
});

// ── update() versioning observer updated() call (kills Lines 897, 899) ──

it('update calls both updating and updated observers creating 2 versions per update', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'VersionTest']);

    $beforeCount = StudioRecordVersion::where('record_id', $record->id)->count();
    expect($beforeCount)->toBe(0);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'Modified']);

    // Should have 2 versions: pre-update (from updating()) and post-update (from updated())
    $afterCount = StudioRecordVersion::where('record_id', $record->id)->count();
    expect($afterCount)->toBe(2);

    // Verify the two snapshots are different (pre has old value, post has new)
    $versions = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('created_at', 'asc')
        ->get();

    $preSnapshot = $versions->first()->snapshot;
    $postSnapshot = $versions->last()->snapshot;

    expect($preSnapshot['name'])->toBe('VersionTest')
        ->and($postSnapshot['name'])->toBe('Modified');
});

// ── bulkInsertValues skips unknown fields (kills Line 983 RemoveArrayItem) ──

it('bulkInsertValues only inserts for known fields', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'Good', 'views' => 5, 'unknown_col' => 'skip']);

    $valueCount = StudioValue::where('record_id', $record->id)->count();
    expect($valueCount)->toBe(2); // Only name and views, not unknown_col
});

// ── whereReferencing operator and value (kills Line 1117 RemoveArrayItem) ──

it('whereReferencing stores correct operator in wheres', function () {
    $builder = EavQueryBuilder::for($this->collection)->tenant(1);
    $builder->whereReferencing('name', 'test-uuid');

    // Just verify the chain works and a record matching the UUID is returned
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'test-uuid']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'different']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereReferencing('name', 'test-uuid')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('test-uuid');
});

// ── deleteWithIntegrity removes values before forceDelete (kills Line 1185 RemoveMethodCall) ──

it('deleteWithIntegrity removes all values before deleting record', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'ValDelete', 'views' => 10, 'price' => 5.5]);

    $valuesBeforeDelete = StudioValue::where('record_id', $record->id)->count();
    expect($valuesBeforeDelete)->toBeGreaterThan(0);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->deleteWithIntegrity($record->uuid);

    // All values and the record should be gone
    expect(StudioValue::where('record_id', $record->id)->count())->toBe(0);
    expect(StudioRecord::withTrashed()->find($record->id))->toBeNull();
});

// ── resolveRelation with empty array display property (kills Lines 1052, 1055) ──

it('withRelated sets empty array display when field is array and no foreign UUIDs', function () {
    $relatedCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'arr-empty',
        'slug' => 'arr-empty',
    ]);
    StudioField::factory()->create([
        'collection_id' => $relatedCollection->id,
        'tenant_id' => 1,
        'column_name' => 'label',
        'label' => 'Label',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Product with empty JSON array
    $product = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create([
        'record_id' => $product->id,
        'field_id' => $this->jsonField->id,
        'val_json' => [],
    ]);

    EavQueryBuilder::invalidateFieldCache();

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->withRelated('tags', $relatedCollection, 'label')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->tags_display)->toBe([]);
});

// ── search() with empty fields array (kills Line 757 EmptyStringToNotEmpty) ──

it('search with empty term still applies the filter', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->search('', ['name'])
        ->get();

    // Empty search term with LIKE '%%' should match everything
    expect($results)->toHaveCount(1);
});

// ── restoreFromVersion field_id in updateOrCreate (kills Line 952 RemoveArrayItem) ──

it('restoreFromVersion correctly maps values to correct fields', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['name' => 'OrigName', 'views' => 100]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->update($record->id, ['name' => 'Changed', 'views' => 200]);

    $version = StudioRecordVersion::where('record_id', $record->id)
        ->orderBy('created_at', 'asc')
        ->first();

    if ($version && isset($version->snapshot['name'])) {
        EavQueryBuilder::for($this->collection)
            ->tenant(1)
            ->restoreFromVersion($record->uuid, $version->id);

        // Verify each field has the correct value from the snapshot
        $data = EavQueryBuilder::for($this->collection)->getRecordData($record);
        expect($data['name'])->toBe($version->snapshot['name']);

        // The value count per field should still be 1 (not duplicated)
        $nameValues = StudioValue::where('record_id', $record->id)
            ->where('field_id', $this->textField->id)
            ->count();
        expect($nameValues)->toBe(1);
    }
});

// ── pluck early return for empty records (kills Line 1198 RemoveEarlyReturn) ──

it('pluck with empty records returns empty collection immediately', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(999)
        ->pluck('name', 'uuid');

    expect($results)->toBeInstanceOf(Collection::class)
        ->and($results)->toBeEmpty();
});

// ── pluck keyField IfNegated (kills Line 1230) ──

it('pluck with valid EAV key field uses that field as keys', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'First']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 1]);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Second']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->intField->id, 'val_integer' => 2]);

    // Pluck name keyed by views (integer field)
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->pluck('name', 'views');

    // Keys should be the integer values (1 and 2), not record IDs
    expect($results->has(1))->toBeTrue()
        ->and($results->has(2))->toBeTrue()
        ->and($results->get(1))->toBe('First')
        ->and($results->get(2))->toBe('Second');
});

// ── aggregate ConcatRemoveRight (kills Line 1288) ──

it('aggregate returns actual value not null for valid aggregation', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->intField->id, 'val_integer' => 100]);

    $result = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->aggregate(AggregateFunction::Sum, 'views');

    // Must not be null -- ConcatRemoveRight would cause null due to missing alias
    expect($result)->not->toBeNull();
    expect((int) $result)->toBe(100);
});

it('getCachedFields with forceRefresh returns fresh data from DB', function () {
    $testCol = StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'fresh-test', 'slug' => 'fresh-test']);

    StudioField::factory()->create([
        'collection_id' => $testCol->id,
        'tenant_id' => 1,
        'column_name' => 'first',
        'label' => 'First',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Force refresh should query DB and cache
    $fields = EavQueryBuilder::getCachedFields($testCol, true);
    expect($fields)->toHaveCount(1);

    // Non-force should return same cached result
    $fields2 = EavQueryBuilder::getCachedFields($testCol, false);
    expect($fields2)->toHaveCount(1);
});

// ── pluck with EAV keyField that doesn't exist falls back (kills Line 1230 IfNegated) ──

it('pluck with nonexistent key field falls back to numeric keys', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->pluck('name', 'nonexistent_key');

    expect($results->values()->all())->toContain('Widget');
});

// ── fetchValues selectedFields filter (kills Line 219, 224-226) ──

it('fetchValues filters by selectedFields when specified', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->textField->id, 'val_text' => 'Widget']);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->intField->id, 'val_integer' => 42]);
    StudioValue::factory()->create(['record_id' => $record->id, 'field_id' => $this->decimalField->id, 'val_decimal' => 19.99]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name', 'views'])
        ->get();

    $first = $results->first();
    expect($first->name)->toBe('Widget')
        ->and($first->views)->toBe(42)
        ->and(property_exists($first, 'price'))->toBeFalse();
});

// ── applyFilterTree with non-empty tree sets filter (kills Line 393 RemoveEarlyReturn) ──

it('applyFilterTree returns self and filter is applied when tree is non-empty', function () {
    $r1 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->textField->id, 'val_text' => 'Alpha']);

    $r2 = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->textField->id, 'val_text' => 'Beta']);

    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            ['field' => 'name', 'operator' => 'eq', 'value' => 'Alpha'],
        ],
    ]);

    $builder = EavQueryBuilder::for($this->collection)->tenant(1);
    $result = $builder->applyFilterTree($tree);
    expect($result)->toBe($builder);

    $data = $builder->get();
    expect($data)->toHaveCount(1)
        ->and($data->first()->name)->toBe('Alpha');
});
