<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\Panels\DeletePanelTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioPanel;

it('deletes a panel', function () {
    $panel = StudioPanel::factory()->create(['tenant_id' => 1]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, DeletePanelTool::class, ['id' => $panel->id])
        ->assertSee('"deleted"')->assertSee((string) $panel->id);

    expect(StudioPanel::find($panel->id))->toBeNull();
});
