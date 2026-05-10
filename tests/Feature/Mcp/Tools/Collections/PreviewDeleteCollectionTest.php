<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Collections\PreviewDeleteCollectionTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;

it('returns a preview with confirm_token', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->count(2)->create(['tenant_id' => 1]);
    StudioRecord::factory()->for($c, 'collection')->count(3)->create(['tenant_id' => 1]);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, PreviewDeleteCollectionTool::class, ['slug' => 'products'])
        ->assertSee('ct_')
        ->assertSee('"record_count"')
        ->assertSee('3');
});

it('returns STUDIO_NOT_FOUND for unknown slug', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, PreviewDeleteCollectionTool::class, ['slug' => 'ghost'])
        ->assertSee('STUDIO_NOT_FOUND');
});
