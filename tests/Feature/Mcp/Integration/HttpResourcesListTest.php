<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\StudioMcpServer;
use Flexpik\FilamentStudio\Mcp\Support\ResolveStudioApiKey;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

beforeEach(function () {
    config()->set('filament-studio.mcp.enabled', true);
    config()->set('filament-studio.mcp.http.enabled', true);
    config()->set('filament-studio.mcp.http.prefix', 'ai/studio');

    // Register the MCP HTTP route directly (can't use require_once as testbench
    // creates a fresh app per test, discarding routes from previous test runs).
    Route::middleware(['api', 'throttle:studio-mcp', ResolveStudioApiKey::class])
        ->group(function () {
            Mcp::web('/'.config('filament-studio.mcp.http.prefix'), StudioMcpServer::class);
        });
});

it('rejects HTTP MCP request without an API key', function () {
    $response = $this->postJson('/ai/studio', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'resources/list',
    ]);

    $response->assertStatus(401);
    $response->assertJsonPath('error.code', 'STUDIO_UNAUTHENTICATED');
});

it('returns resources via resources/list with a valid API key', function () {
    $plain = 'sk_smoke';
    StudioApiKey::factory()->create([
        'key' => hash('sha256', $plain),
        'is_active' => true,
        'permissions' => ['_studio' => ['read_schema']],
    ]);

    $response = $this->postJson('/ai/studio', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'resources/list',
    ], ['X-Api-Key' => $plain]);

    $response->assertStatus(200);

    $payload = $response->json();

    expect($payload['jsonrpc'])->toBe('2.0');
    expect($payload['result']['resources'])->toBeArray();

    $uris = array_column($payload['result']['resources'], 'uri');

    expect($uris)->toContain(
        'studio://info',
        'studio://field-types',
        'studio://panel-types',
        'studio://operators',
    );
});

it('reads studio://info via resources/read with a valid API key', function () {
    $plain = 'sk_read';
    StudioApiKey::factory()->create([
        'key' => hash('sha256', $plain),
        'is_active' => true,
        'tenant_id' => 99,
        'permissions' => ['_studio' => ['read_schema']],
    ]);

    $response = $this->postJson('/ai/studio', [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'resources/read',
        'params' => ['uri' => 'studio://info'],
    ], ['X-Api-Key' => $plain]);

    $response->assertStatus(200);

    $payload = $response->json();
    $contents = $payload['result']['contents'][0]['text'] ?? '';
    $info = json_decode($contents, true);

    expect($info['package'])->toBe('flexpik/filament-studio');
    expect($info['tenant_id'])->toBe(99);
    expect($info['capabilities']['field_types'])->toBe(33);
});
