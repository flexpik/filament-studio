<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Resources\ServerInfoResource;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Laravel\Mcp\Request;

it('returns server info as JSON text response', function () {
    $key = StudioApiKey::factory()->create([
        'tenant_id' => 7,
        'is_active' => true,
        'permissions' => ['_studio' => ['read_schema']],
    ]);
    app(StudioApiKeyContext::class)->set($key);

    $response = (new ServerInfoResource)->handle(new Request);

    $payload = json_decode((string) $response->content(), true);

    expect($payload)->toMatchArray([
        'package' => 'flexpik/filament-studio',
        'mcp_protocol_version' => '2024-11-05',
        'tenant_id' => 7,
    ]);

    expect($payload)->toHaveKeys(['version', 'features', 'locales', 'capabilities']);
    expect($payload['capabilities'])->toMatchArray([
        'field_types' => 33,
        'panel_types' => 9,
        'operators' => 23,
    ]);
});
