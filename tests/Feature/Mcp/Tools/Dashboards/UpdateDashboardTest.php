<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\UpdateDashboardTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioDashboard;

it('updates a dashboard', function () {
    $dash = StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'sales', 'name' => 'Old']);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, UpdateDashboardTool::class, [
        'slug' => 'sales',
        'name' => 'New Name',
    ])->assertSee('New Name');

    expect($dash->fresh()->name)->toBe('New Name');
});
