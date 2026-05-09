<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\StudioMcpServer;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Contracts\Transport;

it('extends the Laravel MCP Server base class', function () {
    expect(is_subclass_of(StudioMcpServer::class, Server::class))->toBeTrue();
});

it('declares empty tools, resources, and prompts arrays initially', function () {
    $transport = Mockery::mock(Transport::class);
    $server = new StudioMcpServer($transport);
    $reflection = new ReflectionClass($server);

    foreach (['tools', 'resources', 'prompts'] as $prop) {
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        expect($property->getValue($server))->toBeArray();
    }
});

it('declares server identity attributes', function () {
    $reflection = new ReflectionClass(StudioMcpServer::class);

    $names = collect($reflection->getAttributes())->pluck('name');

    expect($names)->toContain(Name::class);
    expect($names)->toContain(Version::class);
    expect($names)->toContain(Instructions::class);
});
