<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\PreviewDeleteDashboardTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;

it('returns panel count and a ct_ token', function () {
    $dash = StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'sales']);
    StudioPanel::factory()->count(3)->create(['tenant_id' => 1, 'dashboard_id' => $dash->id]);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, PreviewDeleteDashboardTool::class, ['slug' => 'sales'])
        ->assertSee('"panel_count"')
        ->assertSee('ct_');
});
