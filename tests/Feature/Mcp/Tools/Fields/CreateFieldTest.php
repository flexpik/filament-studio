<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Fields\CreateFieldTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

it('creates a field via tool', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, CreateFieldTool::class, [
        'collection_slug' => 'products',
        'column_name' => 'sku',
        'field_type' => 'text',
    ])->assertSee('sku');

    expect(StudioField::count())->toBe(1);
});

it('returns STUDIO_VALIDATION_FAILED for unknown field_type', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, CreateFieldTool::class, [
        'collection_slug' => 'products',
        'column_name' => 'x',
        'field_type' => 'no_such_type',
    ])->assertSee('STUDIO_VALIDATION_FAILED');
});
