<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\ListDashboardsTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioDashboard;

it('lists dashboards for the current tenant', function () {
    StudioDashboard::factory()->create(['tenant_id' => 1, 'name' => 'Sales']);
    StudioDashboard::factory()->create(['tenant_id' => 2, 'name' => 'OtherTenant']);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['read_schema']])
        ->forTenant(1)->create();

    mcpCallTool($key, ListDashboardsTool::class, [])
        ->assertSee('Sales')
        ->assertDontSee('OtherTenant');
});

it('rejects callers without read_schema scope', function () {
    $key = StudioApiKey::factory()->forTenant(1)->create(['permissions' => []]);

    mcpCallTool($key, ListDashboardsTool::class, [])
        ->assertSee('STUDIO_UNAUTHORIZED');
});
