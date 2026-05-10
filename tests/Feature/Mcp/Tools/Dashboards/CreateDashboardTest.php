<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\CreateDashboardTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioDashboard;

it('creates a dashboard', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, CreateDashboardTool::class, ['name' => 'Sales Overview'])
        ->assertSee('Sales Overview');

    expect(StudioDashboard::count())->toBe(1);
});

it('rejects without manage_dashboards scope', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['read_schema']])->forTenant(1)->create();

    mcpCallTool($key, CreateDashboardTool::class, ['name' => 'X'])
        ->assertSee('STUDIO_UNAUTHORIZED');
});

it('returns STUDIO_VALIDATION_FAILED when name missing', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, CreateDashboardTool::class, [])
        ->assertSee('STUDIO_VALIDATION_FAILED');
});
