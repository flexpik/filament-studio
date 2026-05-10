<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Mcp\Tools\Records\QueryRecordsTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create([
        'slug' => 'products', 'tenant_id' => 1, 'api_enabled' => true,
    ]);
    StudioField::factory()->for($this->collection, 'collection')->create([
        'tenant_id' => 1, 'column_name' => 'name', 'field_type' => 'text', 'eav_cast' => 'text',
    ]);
    foreach (['Apples', 'Oranges', 'Bananas'] as $name) {
        EavQueryBuilder::for($this->collection)->tenant(1)->create(['name' => $name]);
    }
});

it('returns paginated records', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Index->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, QueryRecordsTool::class, [
        'collection_slug' => 'products',
        'per_page' => 2,
    ])
        ->assertSee('"page"')
        ->assertSee('"total"')
        ->assertSee('Apples');
});

it('respects filter tree', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['products' => [ApiAction::Index->value]])
        ->forTenant(1)->create();

    mcpCallTool($key, QueryRecordsTool::class, [
        'collection_slug' => 'products',
        'filter' => ['logic' => 'and', 'rules' => [
            ['field' => 'name', 'operator' => 'eq', 'value' => 'Apples'],
        ]],
    ])
        ->assertSee('Apples')
        ->assertDontSee('Oranges');
});

it('rejects callers without per-collection index scope', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['read_schema']])
        ->forTenant(1)->create();

    mcpCallTool($key, QueryRecordsTool::class, [
        'collection_slug' => 'products',
    ])->assertSee('STUDIO_UNAUTHORIZED');
});
