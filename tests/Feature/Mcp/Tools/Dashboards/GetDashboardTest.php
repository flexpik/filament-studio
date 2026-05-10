<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\GetDashboardTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;

it('returns a dashboard with panels', function () {
    $dash = StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'sales']);
    StudioPanel::factory()->create([
        'tenant_id' => 1, 'dashboard_id' => $dash->id, 'panel_type' => 'metric',
    ]);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['read_schema']])->forTenant(1)->create();

    mcpCallTool($key, GetDashboardTool::class, ['slug' => 'sales'])
        ->assertSee('"slug"')
        ->assertSee('sales')
        ->assertSee('metric');
});

it('returns STUDIO_NOT_FOUND for unknown slug', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['read_schema']])->forTenant(1)->create();

    mcpCallTool($key, GetDashboardTool::class, ['slug' => 'missing'])
        ->assertSee('STUDIO_NOT_FOUND');
});
