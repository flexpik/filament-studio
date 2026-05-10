<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\SavedFilters\DeleteSavedFilterTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;

it('deletes a saved filter', function () {
    $col = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $filter = StudioSavedFilter::create([
        'tenant_id' => 1, 'collection_id' => $col->id, 'name' => 'X',
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_filters']])->forTenant(1)->create();

    mcpCallTool($key, DeleteSavedFilterTool::class, ['id' => $filter->id])
        ->assertSee('"deleted"')->assertSee((string) $filter->id);

    expect(StudioSavedFilter::find($filter->id))->toBeNull();
});

it('returns STUDIO_NOT_FOUND when filter belongs to another tenant', function () {
    $col = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 2]);
    $filter = StudioSavedFilter::create([
        'tenant_id' => 2, 'collection_id' => $col->id, 'name' => 'Theirs',
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_filters']])->forTenant(1)->create();

    mcpCallTool($key, DeleteSavedFilterTool::class, ['id' => $filter->id])
        ->assertSee('STUDIO_NOT_FOUND');

    expect(StudioSavedFilter::find($filter->id))->not->toBeNull();
});

it('rejects callers without manage_filters scope', function () {
    $key = StudioApiKey::factory()->forTenant(1)->create(['permissions' => []]);

    mcpCallTool($key, DeleteSavedFilterTool::class, ['id' => 1])
        ->assertSee('STUDIO_UNAUTHORIZED');
});
