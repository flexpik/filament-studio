<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Resources;

use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\Mcp\Support\FilamentSchemaToJsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Resource;

#[MimeType('application/json')]
#[Description('Full schema for a single field type: settings_schema (JSON Schema), eav_cast, supported operators.')]
class FieldTypeDetailResource extends Resource
{
    protected string $uri = 'studio://field-types/{key}';

    public function handle(Request $request): Response
    {
        $key = $request->get('key');

        $registry = app(FieldTypeRegistry::class);
        $types = $registry->all();

        if (! isset($types[$key])) {
            return Response::text(json_encode([
                'error' => [
                    'code' => 'STUDIO_NOT_FOUND',
                    'message' => "Field type '{$key}' is not registered.",
                ],
            ], JSON_PRETTY_PRINT));
        }

        $class = $types[$key];

        $payload = [
            'key' => $key,
            'label' => $class::$label,
            'category' => $class::$category,
            'eav_cast' => $class::$eavCast->value,
            'icon' => $class::$icon ?? null,
            'settings_schema' => (new FilamentSchemaToJsonSchema)->translate($class::settingsSchema()),
        ];

        return Response::text(json_encode($payload, JSON_PRETTY_PRINT));
    }
}
