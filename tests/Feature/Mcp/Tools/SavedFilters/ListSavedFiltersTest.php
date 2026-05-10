<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\SavedFilters\ListSavedFiltersTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;

it('lists saved filters for a collection', function () {
    $col = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioSavedFilter::create([
        'tenant_id' => 1, 'collection_id' => $col->id, 'name' => 'Active', 'is_shared' => true,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_filters']])->forTenant(1)->create();

    mcpCallTool($key, ListSavedFiltersTool::class, ['collection_slug' => 'products'])
        ->assertSee('Active');
});

it('excludes filters from other tenants', function () {
    $col1 = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $col2 = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 2]);
    StudioSavedFilter::create([
        'tenant_id' => 1, 'collection_id' => $col1->id, 'name' => 'Mine', 'is_shared' => false,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);
    StudioSavedFilter::create([
        'tenant_id' => 2, 'collection_id' => $col2->id, 'name' => 'Theirs', 'is_shared' => true,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_filters']])->forTenant(1)->create();

    mcpCallTool($key, ListSavedFiltersTool::class, ['collection_slug' => 'products'])
        ->assertSee('Mine')
        ->assertDontSee('Theirs');
});

it('returns STUDIO_NOT_FOUND for unknown collection slug', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_filters']])->forTenant(1)->create();

    mcpCallTool($key, ListSavedFiltersTool::class, ['collection_slug' => 'nope'])
        ->assertSee('STUDIO_NOT_FOUND');
});

it('rejects callers without manage_filters scope', function () {
    $key = StudioApiKey::factory()->forTenant(1)->create(['permissions' => []]);

    mcpCallTool($key, ListSavedFiltersTool::class, ['collection_slug' => 'products'])
        ->assertSee('STUDIO_UNAUTHORIZED');
});
