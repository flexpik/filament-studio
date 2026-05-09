<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Collections\GetCollectionTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

it('returns collection with fields', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'sku', 'tenant_id' => 1]);

    $key = StudioApiKey::factory()->withPermissions(['_studio' => ['read_schema']])->forTenant(1)->create();

    mcpCallTool($key, GetCollectionTool::class, ['slug' => 'products'])
        ->assertSee('products')
        ->assertSee('sku');
});

it('returns STUDIO_NOT_FOUND for unknown slug', function () {
    $key = StudioApiKey::factory()->withPermissions(['_studio' => ['read_schema']])->forTenant(1)->create();

    mcpCallTool($key, GetCollectionTool::class, ['slug' => 'ghost'])
        ->assertSee('STUDIO_NOT_FOUND');
});
