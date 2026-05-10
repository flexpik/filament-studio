<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\ApiKeys\ListApiKeysTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;

it('lists API keys for the current tenant without revealing secrets', function () {
    StudioApiKey::factory()->forTenant(1)->create(['name' => 'Mobile']);
    StudioApiKey::factory()->forTenant(2)->create(['name' => 'OtherTenantKey']);
    $caller = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_api_keys']])->forTenant(1)->create();

    $resp = mcpCallTool($caller, ListApiKeysTool::class, []);
    $resp->assertSee('Mobile')->assertDontSee('OtherTenantKey');
    $resp->assertDontSee('"key":');
});

it('rejects without manage_api_keys scope', function () {
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['read_schema']])->forTenant(1)->create();

    mcpCallTool($key, ListApiKeysTool::class, [])->assertSee('STUDIO_UNAUTHORIZED');
});
