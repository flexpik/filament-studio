<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Fields\PreviewDeleteFieldTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;

it('returns preview with confirm_token for field delete', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $f = StudioField::factory()->for($c, 'collection')->create(['column_name' => 'sku', 'tenant_id' => 1]);
    $records = StudioRecord::factory()->for($c, 'collection')->count(3)->create(['tenant_id' => 1]);
    foreach ($records as $r) {
        StudioValue::factory()->for($r, 'record')->for($f, 'field')->create();
    }

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, PreviewDeleteFieldTool::class, [
        'collection_slug' => 'products',
        'column_name' => 'sku',
    ])->assertSee('ct_')
        ->assertSee('"value_count"')
        ->assertSee('3');
});
