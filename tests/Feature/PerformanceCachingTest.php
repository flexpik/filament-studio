<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

it('caches collection metadata within a request using static cache', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'products']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $fields1 = EavQueryBuilder::getCachedFields($collection);
    $fields2 = EavQueryBuilder::getCachedFields($collection);

    expect($fields1)->toBe($fields2);
});

it('invalidates field cache when a field is created', function () {
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

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'status',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $cachedAfter = EavQueryBuilder::getCachedFields($collection, forceRefresh: true);
    expect($cachedAfter)->toHaveCount(2);
});

it('only fetches values for selected field IDs', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1]);
    $nameField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    $descField = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'description',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $record = StudioRecord::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $nameField->id,
        'val_text' => 'Widget',
    ]);
    StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $descField->id,
        'val_text' => 'A great widget',
    ]);

    $results = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->select(['name'])
        ->get();

    expect($results->first()->name)->toBe('Widget')
        ->and(property_exists($results->first(), 'description'))->toBeFalse();
});
