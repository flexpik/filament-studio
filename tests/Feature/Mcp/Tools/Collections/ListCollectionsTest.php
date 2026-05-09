<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Collections\ListCollectionsTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;

it('returns only collections for the current tenant', function () {
    StudioCollection::factory()->create(['slug' => 'mine', 'tenant_id' => 1]);
    StudioCollection::factory()->create(['slug' => 'theirs', 'tenant_id' => 2]);

    $key = StudioApiKey::factory()->withPermissions(['_studio' => ['read_schema']])->forTenant(1)->create();

    mcpCallTool($key, ListCollectionsTool::class, [])
        ->assertSee('mine')
        ->assertDontSee('theirs');
});

it('filters by name_search', function () {
    StudioCollection::factory()->create(['name' => 'Products', 'slug' => 'products', 'tenant_id' => 1]);
    StudioCollection::factory()->create(['name' => 'Orders', 'slug' => 'orders', 'tenant_id' => 1]);

    $key = StudioApiKey::factory()->withPermissions(['_studio' => ['read_schema']])->forTenant(1)->create();

    mcpCallTool($key, ListCollectionsTool::class, ['name_search' => 'prod'])
        ->assertSee('products')
        ->assertDontSee('orders');
});

it('returns auth error when read_schema scope is missing', function () {
    $key = StudioApiKey::factory()->withPermissions([])->forTenant(1)->create();

    mcpCallTool($key, ListCollectionsTool::class, [])
        ->assertSee('STUDIO_UNAUTHORIZED');
});
