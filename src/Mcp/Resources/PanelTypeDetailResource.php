<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Resources;

use Flexpik\FilamentStudio\Mcp\Support\FilamentSchemaToJsonSchema;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Resource;

#[MimeType('application/json')]
#[Description('Full config schema for a single panel type, plus default config and supported placements.')]
class PanelTypeDetailResource extends Resource
{
    protected string $uri = 'studio://panel-types/{key}';

    public function handle(Request $request): Response
    {
        $key = $request->get('key');

        $registry = app(PanelTypeRegistry::class);
        $types = $registry->all();

        if (! isset($types[$key])) {
            return Response::text(json_encode([
                'error' => [
                    'code' => 'STUDIO_NOT_FOUND',
                    'message' => "Panel type '{$key}' is not registered.",
                ],
            ], JSON_PRETTY_PRINT));
        }

        $class = $types[$key];

        $payload = [
            'key' => $key,
            'label' => $class::$label,
            'description' => $class::$description ?? '',
            'icon' => $class::$icon ?? null,
            'placements' => array_map(
                fn ($placement) => $placement->value,
                $class::$supportedPlacements,
            ),
            'config_schema' => (new FilamentSchemaToJsonSchema)->translate($class::configSchema()),
            'default_config' => $class::defaultConfig(),
        ];

        return Response::text(json_encode($payload, JSON_PRETTY_PRINT));
    }
}
