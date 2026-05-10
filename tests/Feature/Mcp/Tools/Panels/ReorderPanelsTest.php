<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Panels\ReorderPanelsTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;

it('reorders panels by id list', function () {
    $dash = StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'sales']);
    $a = StudioPanel::factory()->create(['tenant_id' => 1, 'dashboard_id' => $dash->id, 'grid_order' => 0]);
    $b = StudioPanel::factory()->create(['tenant_id' => 1, 'dashboard_id' => $dash->id, 'grid_order' => 1]);
    $c = StudioPanel::factory()->create(['tenant_id' => 1, 'dashboard_id' => $dash->id, 'grid_order' => 2]);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, ReorderPanelsTool::class, [
        'dashboard_slug' => 'sales',
        'panel_ids' => [$c->id, $a->id, $b->id],
    ])->assertSee('"reordered"')->assertSee('"count"');

    expect($c->fresh()->grid_order)->toBe(0);
    expect($a->fresh()->grid_order)->toBe(1);
    expect($b->fresh()->grid_order)->toBe(2);
});
