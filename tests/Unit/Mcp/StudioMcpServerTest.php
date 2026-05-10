<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\StudioMcpServer;
use Laravel\Mcp\Server;
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

it('declares server identity via properties', function () {
    $transport = Mockery::mock(Transport::class);
    $server = new StudioMcpServer($transport);
    $reflection = new ReflectionClass($server);

    $name = $reflection->getProperty('name');
    $name->setAccessible(true);

    $version = $reflection->getProperty('version');
    $version->setAccessible(true);

    $instructions = $reflection->getProperty('instructions');
    $instructions->setAccessible(true);

    expect($name->getValue($server))->toBe('Filament Studio');
    expect($version->getValue($server))->toBe('0.1.0');
    expect($instructions->getValue($server))->toContain('dynamic data model manager');
});
