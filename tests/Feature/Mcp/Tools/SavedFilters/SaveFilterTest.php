<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\SavedFilters\SaveFilterTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;

it('creates a new saved filter when id is absent', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_filters']])->forTenant(1)->create();

    mcpCallTool($key, SaveFilterTool::class, [
        'collection_slug' => 'products',
        'name' => 'Active',
        'filter' => ['logic' => 'and', 'rules' => []],
        'is_shared' => true,
    ])->assertSee('Active');

    expect(StudioSavedFilter::count())->toBe(1);
});

it('updates an existing saved filter by id', function () {
    $col = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $filter = StudioSavedFilter::create([
        'tenant_id' => 1, 'collection_id' => $col->id, 'name' => 'Old', 'is_shared' => false,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_filters']])->forTenant(1)->create();

    mcpCallTool($key, SaveFilterTool::class, [
        'id' => $filter->id,
        'collection_slug' => 'products',
        'name' => 'New',
        'filter' => ['logic' => 'and', 'rules' => []],
    ])->assertSee('New');

    expect($filter->fresh()->name)->toBe('New');
});

it('returns STUDIO_NOT_FOUND when collection does not exist', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_filters']])->forTenant(1)->create();

    mcpCallTool($key, SaveFilterTool::class, [
        'collection_slug' => 'nope',
        'name' => 'X',
        'filter' => ['logic' => 'and', 'rules' => []],
    ])->assertSee('STUDIO_NOT_FOUND');
});

it('returns STUDIO_NOT_FOUND when filter id does not belong to tenant', function () {
    $col1 = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $col2 = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 2]);
    $filter = StudioSavedFilter::create([
        'tenant_id' => 2, 'collection_id' => $col2->id, 'name' => 'Other',
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_filters']])->forTenant(1)->create();

    mcpCallTool($key, SaveFilterTool::class, [
        'id' => $filter->id,
        'collection_slug' => 'products',
        'name' => 'Hijack',
        'filter' => ['logic' => 'and', 'rules' => []],
    ])->assertSee('STUDIO_NOT_FOUND');
});

it('rejects callers without manage_filters scope', function () {
    $key = StudioApiKey::factory()->forTenant(1)->create(['permissions' => []]);

    mcpCallTool($key, SaveFilterTool::class, [
        'collection_slug' => 'products',
        'name' => 'X',
        'filter' => ['logic' => 'and', 'rules' => []],
    ])->assertSee('STUDIO_UNAUTHORIZED');
});
