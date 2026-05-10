<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\StudioMcpServer;
use Flexpik\FilamentStudio\Mcp\Tools\Collections\ListCollectionsTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Laravel\Mcp\Server\Transport\FakeTransporter;

it('exposes the expected tool count and reaches studio_list_collections via the in-process MCP harness', function () {
    config()->set('filament-studio.mcp.enabled', true);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['read_schema']])
        ->forTenant(1)
        ->create();

    // 1. tools count reflects the registered surface
    $server = app(StudioMcpServer::class, ['transport' => new FakeTransporter]);
    $reflection = new ReflectionClass($server);
    $tools = $reflection->getProperty('tools')->getValue($server);
    expect(count($tools))->toBe(34);

    // 2. studio_list_collections returns successfully through the test transport
    mcpCallTool($key, ListCollectionsTool::class, [])
        ->assertSee('"collections"');
});
