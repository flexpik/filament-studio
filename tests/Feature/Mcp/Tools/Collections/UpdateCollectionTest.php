<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Collections\UpdateCollectionTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;

it('updates a collection via tool', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1, 'name' => 'Old']);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, UpdateCollectionTool::class, ['slug' => 'products', 'name' => 'New'])
        ->assertSee('New');

    expect(StudioCollection::first()->name)->toBe('New');
});

it('returns STUDIO_NOT_FOUND for unknown slug', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, UpdateCollectionTool::class, ['slug' => 'ghost', 'name' => 'X'])
        ->assertSee('STUDIO_NOT_FOUND');
});
