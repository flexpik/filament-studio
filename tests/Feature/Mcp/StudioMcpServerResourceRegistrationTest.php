<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Resources\FieldTypeCatalogResource;
use Flexpik\FilamentStudio\Mcp\Resources\FieldTypeDetailResource;
use Flexpik\FilamentStudio\Mcp\Resources\OperatorCatalogResource;
use Flexpik\FilamentStudio\Mcp\Resources\PanelTypeCatalogResource;
use Flexpik\FilamentStudio\Mcp\Resources\PanelTypeDetailResource;
use Flexpik\FilamentStudio\Mcp\Resources\ServerInfoResource;
use Flexpik\FilamentStudio\Mcp\StudioMcpServer;

it('registers all six capability resources', function () {
    $reflection = new ReflectionClass(StudioMcpServer::class);
    $property = $reflection->getProperty('resources');
    $property->setAccessible(true);

    $defaultValues = $reflection->getDefaultProperties();
    $resources = $defaultValues['resources'] ?? [];

    expect($resources)->toBe([
        ServerInfoResource::class,
        FieldTypeCatalogResource::class,
        FieldTypeDetailResource::class,
        PanelTypeCatalogResource::class,
        PanelTypeDetailResource::class,
        OperatorCatalogResource::class,
    ]);
});
