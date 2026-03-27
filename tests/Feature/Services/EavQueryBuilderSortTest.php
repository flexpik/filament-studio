<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

mutates(EavQueryBuilder::class);

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'items',
        'slug' => 'items',
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

    $this->createdField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'publish_date',
        'label' => 'Publish Date',
        'field_type' => 'datetime',
        'eav_cast' => 'datetime',
    ]);

    // Record A: name=Cherry, price=30.00, date=2026-03-01
    $rA = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $rA->id, 'field_id' => $this->nameField->id, 'val_text' => 'Cherry']);
    StudioValue::factory()->create(['record_id' => $rA->id, 'field_id' => $this->priceField->id, 'val_decimal' => 30.00]);
    StudioValue::factory()->create(['record_id' => $rA->id, 'field_id' => $this->createdField->id, 'val_datetime' => '2026-03-01 00:00:00']);

    // Record B: name=Apple, price=10.00, date=2026-01-01
    $rB = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $rB->id, 'field_id' => $this->nameField->id, 'val_text' => 'Apple']);
    StudioValue::factory()->create(['record_id' => $rB->id, 'field_id' => $this->priceField->id, 'val_decimal' => 10.00]);
    StudioValue::factory()->create(['record_id' => $rB->id, 'field_id' => $this->createdField->id, 'val_datetime' => '2026-01-01 00:00:00']);

    // Record C: name=Banana, price=20.00, date=2026-02-01
    $rC = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $rC->id, 'field_id' => $this->nameField->id, 'val_text' => 'Banana']);
    StudioValue::factory()->create(['record_id' => $rC->id, 'field_id' => $this->priceField->id, 'val_decimal' => 20.00]);
    StudioValue::factory()->create(['record_id' => $rC->id, 'field_id' => $this->createdField->id, 'val_datetime' => '2026-02-01 00:00:00']);
});

it('sorts by text field ascending', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name'])
        ->orderBy('name', 'asc')
        ->paginate(25);

    $names = array_map(fn ($item) => $item->name, $results->items());

    expect($names)->toBe(['Apple', 'Banana', 'Cherry']);
});

it('sorts by text field descending', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name'])
        ->orderBy('name', 'desc')
        ->paginate(25);

    $names = array_map(fn ($item) => $item->name, $results->items());

    expect($names)->toBe(['Cherry', 'Banana', 'Apple']);
});

it('sorts by decimal field ascending', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name', 'price'])
        ->orderBy('price', 'asc')
        ->paginate(25);

    $names = array_map(fn ($item) => $item->name, $results->items());

    expect($names)->toBe(['Apple', 'Banana', 'Cherry']);
});

it('sorts by decimal field descending', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name', 'price'])
        ->orderBy('price', 'desc')
        ->paginate(25);

    $names = array_map(fn ($item) => $item->name, $results->items());

    expect($names)->toBe(['Cherry', 'Banana', 'Apple']);
});

it('sorts by datetime field ascending', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name', 'publish_date'])
        ->orderBy('publish_date', 'asc')
        ->paginate(25);

    $names = array_map(fn ($item) => $item->name, $results->items());

    expect($names)->toBe(['Apple', 'Banana', 'Cherry']);
});

it('sorts by datetime field descending', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name', 'publish_date'])
        ->orderBy('publish_date', 'desc')
        ->paginate(25);

    $names = array_map(fn ($item) => $item->name, $results->items());

    expect($names)->toBe(['Cherry', 'Banana', 'Apple']);
});

it('supports multiple orderBy clauses', function () {
    // Add a record with same name as Cherry but lower price
    $rD = StudioRecord::factory()->create(['collection_id' => $this->collection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $rD->id, 'field_id' => $this->nameField->id, 'val_text' => 'Cherry']);
    StudioValue::factory()->create(['record_id' => $rD->id, 'field_id' => $this->priceField->id, 'val_decimal' => 5.00]);

    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->select(['name', 'price'])
        ->orderBy('name', 'asc')
        ->orderBy('price', 'asc')
        ->paginate(25);

    $items = $results->items();

    expect($items[0]->name)->toBe('Apple')
        ->and($items[3]->name)->toBe('Cherry')
        ->and($items[2]->price)->toBeLessThan($items[3]->price);
});
