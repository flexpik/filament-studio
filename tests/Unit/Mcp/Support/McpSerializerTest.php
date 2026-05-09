<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

it('serializes a collection with its fields', function () {
    $collection = StudioCollection::factory()->create([
        'name' => 'Products',
        'slug' => 'products',
        'tenant_id' => 1,
    ]);
    StudioField::factory()->for($collection, 'collection')->create([
        'column_name' => 'status',
        'field_type' => 'select',
        'tenant_id' => 1,
    ]);

    $array = (new McpSerializer)->collection($collection->load('fields'));

    expect($array)->toMatchArray([
        'slug' => 'products',
        'name' => 'Products',
    ])
        ->and($array['fields'][0]['column_name'])->toBe('status');
});

it('serializes a field without nested collection reference', function () {
    $field = StudioField::factory()->create(['column_name' => 'sku', 'field_type' => 'text', 'tenant_id' => 1]);

    $array = (new McpSerializer)->field($field);

    expect($array)->toMatchArray([
        'column_name' => 'sku',
        'field_type' => 'text',
    ])->not->toHaveKey('collection');
});
