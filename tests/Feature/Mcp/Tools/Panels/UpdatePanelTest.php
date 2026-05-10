<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Panels\UpdatePanelTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioPanel;

it('updates panel config', function () {
    $panel = StudioPanel::factory()->create([
        'tenant_id' => 1, 'panel_type' => 'metric', 'config' => ['title' => 'Old'],
    ]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, UpdatePanelTool::class, [
        'id' => $panel->id,
        'config' => ['title' => 'New'],
    ])->assertSee('New');

    expect($panel->fresh()->config['title'])->toBe('New');
});
