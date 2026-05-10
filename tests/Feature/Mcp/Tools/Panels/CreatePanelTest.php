<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Panels\CreatePanelTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;

it('creates a dashboard panel', function () {
    $dash = StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'sales']);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, CreatePanelTool::class, [
        'dashboard_slug' => 'sales',
        'panel_type' => 'metric',
        'placement' => 'dashboard',
        'grid' => ['col_span' => 6, 'row_span' => 2, 'order' => 0],
        'config' => ['title' => 'Total Sales'],
    ])->assertSee('metric');

    expect(StudioPanel::where('dashboard_id', $dash->id)->count())->toBe(1);
});

it('rejects unknown panel_type', function () {
    StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'sales']);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, CreatePanelTool::class, [
        'dashboard_slug' => 'sales',
        'panel_type' => 'no_such_type',
        'placement' => 'dashboard',
    ])->assertSee('STUDIO_VALIDATION_FAILED');
});
