<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\ApiKeys\CreateApiKeyTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;

it('creates an API key and returns the plaintext secret once', function () {
    $caller = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_api_keys']])->forTenant(1)->create();

    $resp = mcpCallTool($caller, CreateApiKeyTool::class, [
        'name' => 'Mobile',
        'permissions' => ['_studio' => ['read_schema'], 'products' => ['index']],
    ]);

    $resp->assertSee('Mobile')->assertSee('sk_live_');
    expect(StudioApiKey::where('name', 'Mobile')->count())->toBe(1);
});

it('rejects without manage_api_keys scope', function () {
    $caller = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])->forTenant(1)->create();

    mcpCallTool($caller, CreateApiKeyTool::class, ['name' => 'X'])
        ->assertSee('STUDIO_UNAUTHORIZED');
});
