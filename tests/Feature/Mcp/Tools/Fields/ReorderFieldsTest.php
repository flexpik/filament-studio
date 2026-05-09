<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Fields\ReorderFieldsTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

it('reorders fields via tool', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'a', 'sort_order' => 0, 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'b', 'sort_order' => 1, 'tenant_id' => 1]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, ReorderFieldsTool::class, [
        'collection_slug' => 'products',
        'column_names' => ['b', 'a'],
    ])->assertSee('"reordered"');

    expect(StudioField::query()->where('column_name', 'b')->value('sort_order'))->toBe(0);
});
