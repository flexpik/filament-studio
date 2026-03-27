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
        'name' => 'articles',
        'slug' => 'articles',
    ]);

    $this->titleField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $this->statusField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'status',
        'label' => 'Status',
        'field_type' => 'select',
        'eav_cast' => 'text',
    ]);

    $this->viewsField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'views',
        'label' => 'Views',
        'field_type' => 'integer',
        'eav_cast' => 'integer',
    ]);

    $this->priceField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'label' => 'Price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
    ]);

    $this->publishedAtField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'published_at',
        'label' => 'Published At',
        'field_type' => 'datetime',
        'eav_cast' => 'datetime',
    ]);

    $this->categoryField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'category',
        'label' => 'Category',
        'field_type' => 'select',
        'eav_cast' => 'text',
    ]);

    $this->optionalField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'subtitle',
        'label' => 'Subtitle',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Record 1: published, 100 views, $19.99, tech, has subtitle
    $r1 = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->titleField->id, 'val_text' => 'Laravel Tips']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->statusField->id, 'val_text' => 'published']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->viewsField->id, 'val_integer' => 100]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->priceField->id, 'val_decimal' => 19.99]);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->publishedAtField->id, 'val_datetime' => '2026-01-15 10:00:00']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->categoryField->id, 'val_text' => 'tech']);
    StudioValue::factory()->create(['record_id' => $r1->id, 'field_id' => $this->optionalField->id, 'val_text' => 'A deep dive']);

    // Record 2: draft, 50 views, $29.99, science, no subtitle
    $r2 = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->titleField->id, 'val_text' => 'PHP Internals']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->statusField->id, 'val_text' => 'draft']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->viewsField->id, 'val_integer' => 50]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->priceField->id, 'val_decimal' => 29.99]);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->publishedAtField->id, 'val_datetime' => '2026-03-01 08:00:00']);
    StudioValue::factory()->create(['record_id' => $r2->id, 'field_id' => $this->categoryField->id, 'val_text' => 'science']);

    // Record 3: published, 200 views, $9.99, tech, no subtitle
    $r3 = StudioRecord::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->titleField->id, 'val_text' => 'Filament Guide']);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->statusField->id, 'val_text' => 'published']);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->viewsField->id, 'val_integer' => 200]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->priceField->id, 'val_decimal' => 9.99]);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->publishedAtField->id, 'val_datetime' => '2026-02-10 12:00:00']);
    StudioValue::factory()->create(['record_id' => $r3->id, 'field_id' => $this->categoryField->id, 'val_text' => 'tech']);
});

it('filters with where() on text field', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->where('status', 'published')
        ->paginate(25);

    expect($results->total())->toBe(2);
});

it('filters with where() using operator', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->where('views', '>', 75)
        ->paginate(25);

    expect($results->total())->toBe(2);
});

it('filters with whereIn()', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereIn('category', ['tech', 'science'])
        ->paginate(25);

    expect($results->total())->toBe(3);
});

it('filters with whereIn() narrowing results', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereIn('category', ['science'])
        ->paginate(25);

    expect($results->total())->toBe(1);
});

it('filters with whereBetween() on decimal field', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereBetween('price', [10, 25])
        ->paginate(25);

    expect($results->total())->toBe(1); // Only $19.99
});

it('filters with whereBetween() on integer field', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereBetween('views', [50, 150])
        ->paginate(25);

    expect($results->total())->toBe(2); // 100 and 50
});

it('filters with whereDate()', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereDate('published_at', '>', '2026-02-01')
        ->paginate(25);

    expect($results->total())->toBe(2); // Feb 10 and Mar 1
});

it('filters with whereNull()', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereNull('subtitle')
        ->paginate(25);

    expect($results->total())->toBe(2); // Records 2 and 3 have no subtitle
});

it('filters with whereNotNull()', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->whereNotNull('subtitle')
        ->paginate(25);

    expect($results->total())->toBe(1); // Only record 1
});

it('searches with search() across multiple text fields', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->search('Laravel', ['title', 'subtitle'])
        ->paginate(25);

    expect($results->total())->toBe(1);
});

it('searches with search() using partial match', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->search('Guide', ['title'])
        ->paginate(25);

    expect($results->total())->toBe(1);
});

it('combines multiple where clauses', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->where('status', 'published')
        ->where('views', '>', 150)
        ->paginate(25);

    expect($results->total())->toBe(1); // Only record 3 with 200 views
});

it('combines where and search', function () {
    $results = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->where('status', 'published')
        ->search('Tips', ['title'])
        ->paginate(25);

    expect($results->total())->toBe(1);
});
