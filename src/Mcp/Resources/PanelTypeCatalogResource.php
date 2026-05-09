<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Resources;

use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('studio://panel-types')]
#[MimeType('application/json')]
#[Description('Catalog of all 9 panel types with key, label, description, and supported placements. For full config schema, read studio://panel-types/{key}.')]
class PanelTypeCatalogResource extends Resource
{
    // Fallback properties for laravel/mcp versions that do not resolve PHP 8 attributes on Resource.
    protected string $uri = 'studio://panel-types';

    protected string $mimeType = 'application/json';
    public function handle(Request $request): Response
    {
        $registry = app(PanelTypeRegistry::class);

        $entries = [];
        foreach ($registry->all() as $key => $class) {
            $entries[] = [
                'key' => $key,
                'label' => $class::$label,
                'description' => $class::$description ?? '',
                'icon' => $class::$icon ?? null,
                'placements' => array_map(
                    fn ($placement) => $placement->value,
                    $class::$supportedPlacements,
                ),
            ];
        }

        usort($entries, fn ($a, $b) => $a['key'] <=> $b['key']);

        return Response::text(json_encode([
            'panel_types' => $entries,
        ], JSON_PRETTY_PRINT));
    }
}
