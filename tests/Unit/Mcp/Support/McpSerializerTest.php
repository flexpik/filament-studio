<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Support\McpSerializer;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;

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

it('serializes a dashboard with panel count', function () {
    $dashboard = StudioDashboard::factory()->create(['name' => 'Sales']);

    $out = (new McpSerializer)->dashboard($dashboard);

    expect($out)
        ->toHaveKey('id')
        ->toHaveKey('name', 'Sales')
        ->toHaveKey('slug')
        ->toHaveKey('panels');
});

it('serializes a panel including grid + config', function () {
    $panel = StudioPanel::factory()->create([
        'panel_type' => 'metric',
        'config' => ['title' => 'Total'],
    ]);

    $out = (new McpSerializer)->panel($panel);

    expect($out)
        ->toHaveKey('panel_type', 'metric')
        ->toHaveKey('placement')
        ->toHaveKey('grid')
        ->toHaveKey('config');
});

it('serializes a saved filter without exposing tenant id', function () {
    $collection = StudioCollection::factory()->create();

    $filter = StudioSavedFilter::create([
        'collection_id' => $collection->id,
        'tenant_id' => null,
        'created_by' => null,
        'name' => 'Active Products',
        'is_shared' => false,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    $out = (new McpSerializer)->savedFilter($filter);

    expect($out)
        ->toHaveKey('id')
        ->toHaveKey('name', 'Active Products')
        ->toHaveKey('is_shared')
        ->toHaveKey('filter')
        ->not->toHaveKey('tenant_id');
});

it('serializes an api key with prefix only (never the secret)', function () {
    $key = StudioApiKey::factory()->create();

    $out = (new McpSerializer)->apiKey($key);

    expect($out)
        ->toHaveKey('id')
        ->toHaveKey('name')
        ->toHaveKey('is_active')
        ->toHaveKey('permissions')
        ->not->toHaveKey('key');
});
