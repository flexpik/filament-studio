<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Resources;

use Flexpik\FilamentStudio\Enums\FilterOperator;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('studio://info')]
#[MimeType('application/json')]
#[Description('Filament Studio server identity, feature flags, locales, and capability counts. Read first to discover the catalog of capabilities.')]
class ServerInfoResource extends Resource
{
    public function handle(Request $request): Response
    {
        $key = app(StudioApiKeyContext::class)->current();

        $payload = [
            'package' => 'flexpik/filament-studio',
            'version' => $this->packageVersion(),
            'mcp_protocol_version' => '2024-11-05',
            'features' => [
                'api' => (bool) config('filament-studio.api.enabled', false),
                'versioning' => true,
                'locales' => (bool) config('filament-studio.locales.enabled', false),
            ],
            'locales' => [
                'available' => config('filament-studio.locales.available', ['en']),
                'default' => config('filament-studio.locales.default', 'en'),
            ],
            'tenant_id' => $key?->tenant_id,
            'capabilities' => [
                'field_types' => count(app(FieldTypeRegistry::class)->all()),
                'panel_types' => count(app(PanelTypeRegistry::class)->all()),
                'operators' => count(FilterOperator::cases()),
            ],
        ];

        return Response::text(json_encode($payload, JSON_PRETTY_PRINT));
    }

    protected function packageVersion(): string
    {
        return config('filament-studio.version', '0.0.0-dev');
    }
}
