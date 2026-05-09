<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Fields\UpdateFieldTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

it('updates a field label via tool', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create([
        'column_name' => 'sku', 'label' => 'Old', 'tenant_id' => 1,
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, UpdateFieldTool::class, [
        'collection_slug' => 'products',
        'column_name' => 'sku',
        'label' => 'New SKU',
    ])->assertSee('New SKU');
});
