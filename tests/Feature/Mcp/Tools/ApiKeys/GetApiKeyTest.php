<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\ApiKeys\GetApiKeyTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;

it('returns API key meta by id', function () {
    $target = StudioApiKey::factory()->forTenant(1)->create(['name' => 'Mobile']);
    $caller = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_api_keys']])->forTenant(1)->create();

    mcpCallTool($caller, GetApiKeyTool::class, ['id' => $target->id])
        ->assertSee('Mobile');
});

it('does not leak keys from another tenant', function () {
    $target = StudioApiKey::factory()->forTenant(2)->create(['name' => 'OtherTenantKey']);
    $caller = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_api_keys']])->forTenant(1)->create();

    mcpCallTool($caller, GetApiKeyTool::class, ['id' => $target->id])
        ->assertSee('STUDIO_NOT_FOUND');
});
