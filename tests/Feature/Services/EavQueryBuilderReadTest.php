<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;

mutates(EavQueryBuilder::class);

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'products',
        'slug' => 'products',
    ]);

    $this->nameField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'label' => 'Name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $this->priceField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'label' => 'Price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
    ]);

    $this->statusField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'status',
        'label' => 'Status',
        'field_type' => 'select',
        'eav_cast' => 'text',
    ]);

    // Create 3 records with values
    foreach (['Widget A', 'Widget B', 'Widget C'] as $i => $name) {
        $record = StudioRecord::factory()->create([
            'collection_id' => $this->collection->id,
            'tenant_id' => 1,
        ]);

        StudioValue::factory()->create([
            'record_id' => $record->id,
            'field_id' => $this->nameField->id,
            'val_text' => $name,
        ]);

        StudioValue::factory()->create([
            'record_id' => $record->id,
            'field_id' => $this->priceField->id,
            'val_decimal' => ($i + 1) * 10.50,
        ]);

        StudioValue::factory()->create([
            'record_id' => $record->id,
            'field_id' => $this->statusField->id,
            'val_text' => $i === 2 ? 'draft' : 'published',
        ]);
    }
});

it('creates a builder with ::for()', function () {
    $builder = EavQueryBuilder::for($this->collection);

    expect($builder)->toBeInstanceOf(EavQueryBuilder::class);
});

it('scopes by tenant', function () {
    // Create a record for a different tenant
    $otherRecord = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 99,
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->paginate(25);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->total())->toBe(3);
});

it('returns all records without tenant scope when tenant is null', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->paginate(25);

    expect($results->total())->toBe(3);
});

it('returns records as stdClass objects with field values as properties', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name', 'price', 'status'])
        ->paginate(25);

    $first = $results->items()[0];

    expect($first)->toBeInstanceOf(stdClass::class)
        ->and($first)->toHaveProperties(['id', 'uuid', 'name', 'price', 'status']);
});

it('selects only specified fields', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name'])
        ->paginate(25);

    $first = $results->items()[0];

    expect($first)->toHaveProperty('name')
        ->and(property_exists($first, 'price'))->toBeFalse();
});

it('returns all fields when no select is specified', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->paginate(25);

    $first = $results->items()[0];

    expect($first)->toHaveProperties(['name', 'price', 'status']);
});

it('casts values to correct PHP types', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name', 'price'])
        ->paginate(25);

    $first = $results->items()[0];

    expect($first->name)->toBeString()
        ->and($first->price)->toBeNumeric();
});

it('paginates results correctly', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->paginate(2);

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->count())->toBe(2)
        ->and($results->total())->toBe(3)
        ->and($results->lastPage())->toBe(2);
});

it('includes record id, uuid, created_at, and updated_at on results', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->paginate(25);

    $first = $results->items()[0];

    expect($first)->toHaveProperties(['id', 'uuid', 'created_at', 'updated_at']);
});

it('excludes soft-deleted records by default', function () {
    $record = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'deleted_at' => now(),
    ]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->paginate(25);

    expect($results->total())->toBe(3);
});
